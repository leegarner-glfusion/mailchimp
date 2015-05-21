<?php
//  $Id: english_utf-8.php 33 2014-08-18 20:23:20Z root $
/**
*   English language strings for the MailChimp plugin
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2012 Lee Garner <lee@leegarner.com>
*   @package    mailchimp
*   @version    0.0.1
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/


global $LANG32;

$LANG_MLCH = array(
    'adminhome' => 'Admin Home',
    'sync_cache' => 'Sync Cache',
    'import_users' => 'Import Users',
    'subscribe_to_list' => 'Subscribe to Mailing List',
    'hlp_sub_checkbox' => 'Check here to subscribe to our mailing list',
    'block_title' => 'Mailing List',
    'block_button_text' => 'Sign Up',
    'add_success' => 'Your email address has been successfully added to our list. Watch your email for a confirmation message and a url that you\'ll need to visit to confirm your subscription.',
    'add_error' => 'There was an error adding your address.',
    'adding_msg' => 'Adding email address...',
    'block_text_small' => 'Join our mailing list!',
    'block_text' => 'Enter your email address below and we&apos;ll keep you up-to-date on our news, events &amp; other specials.',
    'confirm_needed' => 'Watch your email for a confirmation message and a url that you\'ll need to visit to confirm your subscription.',
    'dbl_optin_required' => 'Double-opt-in required',
    'no_dbl_optin' => 'No Doublie-opt-in',
    'instr_admin' => 'Sync Cache: update all local users in the cache table with MailChimp status.<br />Import Users: subscribe all local users to the default list, double-optin required.',
);


// Messages for the plugin upgrade
$PLG_mailchimp_MESSAGE3001 = 'Plugin upgrade not supported.';
$PLG_mailchimp_MESSAGE3002 = $LANG32[9];
$PLG_mailchimp_MESSAGE1   = 'Your email address has been successfully added to our list.';
$PLG_mailchimp_MESSAGE2   = 'You are now subscribed to our announcement list.';

//$LANG_MYACCOUNT['pe_mailchimpprefs'] = 'Mailing Lists';

// Localization of the Admin Configuration UI
$LANG_configsections['mailchimp'] = array(
    'label' => 'MailChimp',
    'title' => 'MailChimp Configuration'
);

$LANG_confignames['mailchimp'] = array(
    'api_key'   => 'API Key',
    'def_list'  => 'Default List',
    'sub_register' => 'Subscription option on signup form?',
    'del_user_unsub' => 'Unsubscribe deleted users?',
    'dbl_optin_members' => 'Require double-opt-in for user profile changes?',
    'sync_at_login' => 'Sync uses to MailChimp at login?',
    'webhook_key' => 'Mailchimp Webhook key value',
    'webhook_handlesub' => 'Webhook handles subscriptions?',
    'webhook_handleunsub' => 'Webhook handles removals?',
    'webhook_handleupemail' => 'Update user email when Mailchimp email changes?',
    'debug' => 'Enable debugging?',
);

$LANG_configsubgroups['mailchimp'] = array(
    'sg_main' => 'Main Settings',
);

$LANG_fs['mailchimp'] = array(
    'fs_main' => 'Main Settings',
    'fs_webhook' => 'Webhook Options',
);

// Note: entries 0, 1, 9, and 12 are the same as in $LANG_configselects['Core']
$LANG_configselects['mailchimp'] = array(
    0 => array('Yes' => 1, 'No' => 0),
    4 => array('No' => 0, 'No- Subscribe Automatically' => 3, 'Yes- Checked (not recommended)' => 1, 'Yes- Unchecked' => 2),
);

?>
