<?php
/**
 * Webhook url for notifications from Mailchimp.
 * Updates the Mailchimp plugin table with subscriptions and removals,
 * and also updates the Users table with email address changes.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2013-2017 Lee Garner <lee@leegarner.com>
 * @package     mailchimp
 * @version     v0.1.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

require_once __DIR__ . '/../lib-common.php';

if (!in_array('mailchimp', $_PLUGINS) || !MAILCHIMP_ACTIVE) {
    COM_404();
    exit;
}

// If the admin has set a key value to be used by the webhook calls, and
// that key isn't present or doesn't match, then do nothing
if (!empty($_CONF_MLCH['webhook_key'])) {
    if (!isset($_GET['key'])) {
        Mailchimp\Logger::Audit('Webhook: Missing Key.');
        exit;
    } elseif ($_GET['key'] != $_CONF_MLCH['webhook_key']) {
        Mailchimp\Logger::Audit('Webhook: Invalid key received.' . $_GET['key']);
        exit;
    }
}

// MAIN
$action = isset($_POST['type']) ? $_POST['type'] : '';
$list_id = isset($_POST['data']['id']) ? $_POST['data']['id'] : '';
if (empty($list_id)) {
    Mailchimp\Logger::Audit('Webhook: invalid or missing list ID');
    exit;
}
Mailchimp\Logger::System(var_export($_POST,true));

switch ($action) {
case 'subscribe':
    if ($_CONF_MLCH['webhook_handlesub']) {
        // Get the email and make sure it's not empty. While we're here, get the
        // user ID also since that's what gets passed to MLCH_updateCache
        $email = isset($_POST['data']['email']) ? $_POST['data']['email'] : '';
        if (empty($email)) {
            Mailchimp\Logger::Audit("Webhook $action: Empty email address received.");
            exit;
        }

        // Update the mailing list segment, if possible.
        // This wouldn't be updated by subscriptions via Mailchimp form.
        $status = LGLIB_invokeService(
            'membership', 'mailingSegment',
            array(
                'email' => $email,
            ),
            $segment,
            $msg
        );
        if ($status == PLG_RET_OK) {
            Mailchimp\MergeFields::setMemStatus($segment);
        }
        $api = Mailchimp\API::getInstance();
        $params = array(
            'email' => $email,
            'merge_fields' => Mailchimp\MergeFields::get(),
        );
        $status = $api->updateMember($email, $list_id, $params);
        //Mailchimp\Logger::Audit(print_r($status,true));
        if (!$api->success()) {
            Mailchimp\Logger::Audit("Failed to update member status for $email. Error: " .
            $api->getLastError());
        }
        $uid = Mailchimp\Subscriber::getUid($email);
        if ($uid > 1) {
            Mailchimp\Subscriber::getInstance($uid)->updateCache();
        }
        Mailchimp\Logger::Audit("Webhook $action: $email subscribed to $list_id");
    }
    break;

case 'unsubscribe':
case 'cleaned':
    if ($_CONF_MLCH['webhook_handleunsub']) {
        // Get the email and make sure it's not empty. While we're here, get the
        // user ID also since that's what gets passed to MLCH_updateCache
        $email = isset($_POST['data']['email']) ? $_POST['data']['email'] : '';
        if (empty($email)) {
            Mailchimp\Logger::Audit("Webhook $action: Empty email address received.");
            exit;
        }
        $uid = Mailchimp\Subscriber::getUid($email);
        if ($uid < 2) {
            Mailchimp\Logger::Audit("Webhook $action: Invalid user ID $uid from email $email");
            exit;
        }
        if (!empty($email) && !empty($list_id)) {
            DB_delete(
                $_TABLES['mailchimp_cache'],
                array('uid', 'list'),
                array($uid, $list_id)
            );
            Mailchimp\Logger::Audit("Webhook $action: $email unsubscribed from $list_id");
        }
    }
    break;

case 'upemail':
    if ($_CONF_MLCH['handle_upemail']) {
        // Handle email address changes.
        if (empty($_POST['data']['old_email'])) {
            Mailchimp\Logger::Audit('Webhook: Missing old_email');
            exit;
        }
        if (empty($_POST['data']['new_email'])) {
            Mailchimp\Logger::Audit('Webhook: Missing old_email');
            exit;
        }
        $old_email = DB_escapeString($_POST['data']['old_email']);
        $new_email = DB_escapeString($_POST['data']['new_email']);

        // Check that the new address isn't in use already
        $uid = Mailchimp\Subscriber::getUid($new_email);
        if ($uid > 0) {
            Mailchimp\Logger::Audit("Webhook: new address $new_email already used by $uid");
            exit;
        }

        // Get the user ID belonging to the old address
        $uid = Mailchimp\Subscriber::getUid($old_email);
        if ($uid < 2) {
            Mailchimp\Logger::Audit("Webhook: old address $new_email not found");
            exit;
        }

        // Perform the update
        DB_query("UPDATE {$_TABLES['users']} SET email = '$new_email'
                WHERE uid = $uid");
        Mailchimp\Logger::Audit("Webhook: updated user $uid email from $old_email to $new_email");
    }
    break;

case 'Xprofile':
    // Before enabling profile updates, check to make sure that callbacks to
    // Mailchimp won't create a loop.
    $groups = array();
    if (is_array($_POST['data'])) {
        if (is_array($_POST['data']['merges'])) {
            if (is_array($_POST['data']['merges']['GROUPINGS'])) {
                foreach ($_POST['data']['merges']['GROUPINGS'] as $id=>$data) {
                    $groups[$data['name']] = explode(', ', $data['groups']);
                }
            }
        }
    }
    //Mailchimp\Logger::System(print_r($groups, true));
    break;

}

// This page has no output
exit;

?>
