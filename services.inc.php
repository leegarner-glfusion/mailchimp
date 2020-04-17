<?php
/**
 * Service functions for the Mailchimp plugin.
 * This file provides functions to be called by other plugins, such
 * as the PayPal plugin.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2012 Lee Garner <lee@leegarner.com>
 * @package     mailchimp
 * @version     v0.0.1
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *             GNU Public License v2 or later
 * @filesource
 */

if (!defined ('GVERSION')) {
    die ('This file can not be used on its own!');
}


/**
 * Subscribe a user to the specifiec or default mailing list
 *
 * @param   array   $A          Elements for subscription (uid, listid, email)
 * @param   array   &$output    Not used
 * @param   array   &$svc_msg   Not used
 * @return  integer             PLG_RET_status
 */
function service_subscribe_mailchimp($A, &$output, &$svc_msg)
{
    global $_USER;

    unset($A['gl_svc']);    // not used

    $output = '';
    $retval = PLG_RET_OK;       // assume response will be OK
    if (!MAILCHIMP_ACTIVE) {
        return $retval;
    }

    $uid = isset($A['uid']) ? (int)$A['uid'] : $_USER['uid'];
    $list = isset($A['list']) ? $A['list'] : $_CONF_MLCH['def_list'];
    $email = isset($A['email']) ? $A['email'] : '';
    $dbl_optin = isset($A['dbl_optin']) ? $A['dbl_optin'] : null;

    $status = \Mailchimp\Subscriber::getInstance($uid)->subscribe($email, $list, $dbl_optin);
    if (!$status) {
        $retval = PLG_RET_ERROR;
    }
    return $retval;
}


/**
 * Unsubscribe a user from a mailing list.
 *
 * @param   array   $A      Arguments
 * @param   mixed   $output Variable to receive output
 * @param   string  $svc_msg    Not used
 * @return      Plugin return code
 */
function service_unsubscribe_mailchimp($A, &$output, &$svc_msg)
{
    global $_USER;

    $retval = PLG_RET_OK;
    unset($A['gl_svc']);
    $uid = isset($A['uid']) ? (int)$A['uid'] : $_USER['uid'];
    $list = isset($A['list']) ? $A['list'] : $_CONF_MLCH['def_list'];
    $email = isset($A['email']) ? $A['email'] : '';
    //$status = MLCH_unsubscribe($uid, $email, $list);
    $status = \Mailchimp\Subscriber::unsubscribe($uid, $email, $list);
    if (!$status) $retval = PLG_RET_ERROR;
    return $retval;
}


/**
 * Push a user update from glFusion to Mailchimp.
 *
 * @param   array   $A      Arguments
 * @param   mixed   $output Variable to receive output
 * @param   string  $svc_msg    Not used
 * @return      Plugin return code
 */
function service_updateuser_mailchimp($A, &$output, &$svc_msg)
{
    global $_USER;

    unset($A['gl_svc']);
    $retval = PLG_RET_OK;
    $uid = isset($A['uid']) ? (int)$A['uid'] : $_USER['uid'];
    $Sub = Mailchimp\Subscriber::getInstance($uid);
    // sort of a hack, this is to get the existing email addr. for Mailchimp
    // in case the address is changed in user editing.
    if (isset($_POST['mailchimp_old_email'])) {
        $email = $_POST['mailchimp_old_email'];
    } else {
        $email = $Sub->getEmail();
    }
    $status = $Sub->updateMailchimp($A['params'], $email);
    if (!$status) {
        $retval = PLG_RET_ERROR;
    }
    return $retval;
}


/**
 * Check if a user is subscribed to a mailing list.
 *
 * @param   array   $A      Arguments
 * @param   mixed   $output Variable to receive output
 * @param   string  $svc_msg    Not used
 * @return      Plugin return code
 */
function service_issubscribed_mailchimp($A, &$output, &$svc_msg)
{
    global $_CONF_MLCH;

    if (!is_array($A)) {
        $A = array('uid' => $A);
    }
    if (!isset($A['list'])) $A['list'] = $_CONF_MLCH['def_list'];
    $output = \Mailchimp\Subscriber::getInstance($A['uid'])->isSubscribed($A['list']);
    return PLG_RET_OK;
}

?>
