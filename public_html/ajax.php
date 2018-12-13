<?php
/**
 * Ajax functions for the Mailchimp plugin.
 * Allows visitors to subscribe or unsubscribe from any mailing lists.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2012-2013 Lee Garner <lee@leegarner.com>
 * @package     mailchimp
 * @version     v0.1.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

require_once '../lib-common.php';

if (!in_array('mailchimp', $_PLUGINS) || !MAILCHIMP_ACTIVE) {
    COM_404();
    exit;
}

// MAIN
$action = '';
$expected = array(
    // Actions to perform
    'action', 'sublists',
);
foreach($expected as $provided) {
    // Get requested action and page from GET or POST variables.
    // Most could come in either way.  They are not sanitized, so they must
    // only be used in switch or other conditions.
    if (isset($_POST[$provided])) {
        $action = $provided;
        $actionval = $_POST[$provided];
        break;
    } elseif (isset($_GET[$provided])) {
        $action = $provided;
        $actionval = $_GET[$provided];
        break;
    }
}
if ($action == 'action') $action = $actionval;
$success = false;
switch ($action) {
case 'add':
    $address = $_POST['email'];
    if (empty($address)) {
        $msg = $LANG_MLCH['email_missing'];
    } elseif (!preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*$/i", $address)) {
        $msg = $LANG_MLCH['add_error'];
    } else {
        // Basic checks passed, now try to add the address
        $success = MLCH_subscribe(0, $address);
        switch ($success) {
        case  true:
            $msg = $_CONF_MLCH['dbl_optin_members'] ?
                    $LANG_MLCH['confirm_needed'] : $LANG_MLCH['add_success'];
            break;
        default;
            $msg = $LANG_MLCH['add_error'];
            break;
        }
    }
    $content = array(
        'msg' => $msg,
        'status' => $success,
    );
    echo json_encode($content);
    exit;
    break;

default:
    exit;
}

?>
