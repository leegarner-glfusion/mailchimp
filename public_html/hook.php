<?php
/**
*   Webhook url for notifications from Mailchimp
*   Updates the Mailchimp plugin table with subscriptions and removals,
*   and also updates the Users table with email address changes.
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2013-2017 Lee Garner <lee@leegarner.com>
*   @package    mailchimp
*   @version    0.1.0
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/

require_once dirname(__FILE__) . '/../lib-common.php';

if (!in_array('mailchimp', $_PLUGINS) || !MAILCHIMP_ACTIVE) {
    COM_404();
    exit;
}

// If the admin has set a key value to be used by the webhook calls, and
// that key isn't present or doesn't match, then do nothing
if (!empty($_CONF_MLCH['webhook_key']) &&
    ( !isset($_GET['key']) || $_GET['key'] != $_CONF_MLCH['webhook_key'] ) ) {
    MLCH_auditLog('Webhook: Invalid key received: ' . $_GET['key']);
    exit;
}

// MAIN
$action = isset($_POST['type']) ? $_POST['type'] : '';
$list_id = isset($_POST['data']['id']) ? $_POST['data']['id'] : '';
if (empty($list_id)) {
    MLCH_auditLog('Webhook: invalid or missing list ID');
    exit;
}

switch ($action) {
case 'subscribe':
    if ($_CONF_MLCH['webhook_handlesub']) {
        // Get the email and make sure it's not empty. While we're here, get the
        // user ID also since that's what gets passed to MLCH_updateCache
        $email = isset($_POST['data']['email']) ? $_POST['data']['email'] : '';
        if (empty($email)) {
            MLCH_auditLog("Webhook $action: Empty email address received.");
            exit;
        }

        // Update the mailing list segment, if possible.
        // This wouldn't be updated by subscriptions via Mailchimp form.
        $merge_vars = array();
        $memstatus = '';
        $status = LGLIB_invokeService('membership', 'mailingSegment',
                 array('email'=>$email), $segment, $msg);
        if ($status == PLG_RET_OK) {
            $memstatus = $segment;
            $merge_vars['MEMSTATUS'] = $memstatus;  // always update
        }
        USES_mailchimp_class_api();
        $api = new Mailchimp($_CONF_MLCH['api_key']);
        $params = array(
            'email' => $email,
            'merge_fields' => $merge_vars,
        );
        $status = $api->updateMember($email, $list_id, $params);
        //MLCH_auditLog(print_r($status,true));
        if (!$api->success()) {
            MLCH_auditLog("Failed to update member status for $email. Error: " .
            $api->getLastError());
        }
        MLCH_updateCache($uid, $list_id);
        MLCH_auditlog("Webhook $action: $email subscribed to $list_id");
    }
    break;

case 'unsubscribe':
case 'cleaned':
    if ($_CONF_MLCH['webhook_handleunsub']) {
        // Get the email and make sure it's not empty. While we're here, get the
        // user ID also since that's what gets passed to MLCH_updateCache
        $email = isset($_POST['data']['email']) ? $_POST['data']['email'] : '';
        if (empty($email)) {
            MLCH_auditLog("Webhook $action: Empty email address received.");
            exit;
        }
        $uid = (int)DB_getItem($_TABLES['users'], 'uid', "email='" .
                DB_escapeString($email) . "'");
        if ($uid < 2) {
            MLCH_auditLog("Webhook $action: Invalid user ID $uid from email $email");
            exit;
        }
        if (!empty($email) && !empty($list_id)) {
            DB_delete($_TABLES['mailchimp_cache'],
                    array('uid', 'list'),
                    array($uid, $list_id));
            MLCH_auditLog("Webhook $action: $email unsubscribed from $list_id");
        }
    }
    break;

case 'upemail':
    if ($_CONF_MLCH['handle_upemail']) {
        // Handle email address changes.
        if (empty($_POST['data']['old_email'])) {
            MLCH_auditLog('Webhook: Missing old_email');
            exit;
        }
        if (empty($_POST['data']['new_email'])) {
            MLCH_auditLog('Webhook: Missing old_email');
            exit;
        }
        $old_email = DB_escapeString($_POST['data']['old_email']);
        $new_email = DB_escapeString($_POST['data']['new_email']);

        // Check that the new address isn't in use already
        $uid = (int)DB_getItem($_TABLES['users'], 'uid', "email='$new_email'");
        if ($uid > 0) {
            MLCH_auditLog("Webhook: new address $new_email already used by $uid");
            exit;
        }

        // Get the user ID belonging to the old address
        $uid = (int)DB_getItem($_TABLES['users'], 'uid', "email='$old_email'");
        if ($uid < 2) {
            MLCH_auditLog("Webhook: old address $new_email not found");
            exit;
        }

        // Perform the update
        DB_query("UPDATE {$_TABLES['users']} SET email = '$new_email'
                WHERE uid = $uid");
        MLCH_auditLog("Webhook: updated user $uid email from $old_email to $new_email");
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
    COM_errorLog(print_r($groups, true));
    break;

}

// This page has no output
exit;

?>
