<?php
/**
 * Guest-facing entry for the MailChimp plugin.
 * Allows visitors to subscribe or unsubscribe from any mailing lists.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2012-2018 Lee Garner <lee@leegarner.com>
 * @package     mailchimp
 * @version     v0.0.1
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

require_once '../lib-common.php';

if (!in_array('mailchimp', $_PLUGINS) || !MAILCHIMP_ACTIVE) {
    COM_404();
    exit;
}

/**
 * Save an email address.
 * Gets the address directoy from $_GET.
 *
 * @param   string  $address    E-mail address
 * @return  integer     Numeric message ID to display
 */
function MLCH_storeAddress($address)
{
    global $LANG_MLCH, $_CONF_MLCH;

    $message = '&nbsp;';

    if (empty($address)) {
        //$message = $LANG_MLCH['email_missing'];
        return '10';
    }

    if (!preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*$/i", $address)) {
        //$message = $LANG_MLCH['email_format_error'];
        return '9';
    }

    // Basic checks passed, now try to add the address
    $success = MLCH_subscribe(0, $address);
    switch ($success) {
    case true:
    case MLCH_ADD_SUCCESS:
        //$message = $LANG_MLCH['email_success'];
        $message = '1';
        break;
    case MLCH_ADD_MISSING:
        //$message = $LANG_MLCH['email_missing'];
        $message = '5';
        break;
    case MLCH_ADD_EXISTS:
        //$message = $LANG_MLCH['email_exists'];
        $message = '6';
        break;
    case MLCH_ADD_BLACKLIST:
        //$message = $LANG_MLCH['email_blacklisted'];
        $message = '7';
        break;
    default:
    case MLCH_ADD_ERROR:
        //$message = $LANG_MLCH['email_store_error'];
        $message = '8';
        break;
    }
    return $message;
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
$content = '';

switch ($action) {
case 'add':
    $content = MLCH_storeAddress($_GET['email']);
    $content = $PLG_mailchimp_MESSAGE{$content};
    echo $content;
    exit;
    break;

default:
    // This plugin currently has no default page. Maybe add a page
    // to allow subscription to different lists later on.
    echo COM_refresh($_CONF['site_url']);
    break;
}

?>
