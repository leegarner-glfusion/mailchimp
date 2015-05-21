<?php
/**
*   Default items for the MailChimp plugin
*
*   Initial Installation Defaults used when loading the online configuration
*   records. These settings are only used during the initial installation
*   and not referenced any more once the plugin is installed, except when
*   upgrading
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2012 Lee Garner <lee@leegarner.com>
*   @package    mailchimp
*   @version    0.0.1
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*              GNU Public License v2 or later
*   @filesource
*/

if (!defined ('GVERSION')) {
    die('This file can not be used on its own!');
}

global $_MLCH_DEFAULT;
$_MLCH_DEFAULT = array(
    'api_key'   =>  '',     // Mailchimp API key
    'def_list'  =>  '',     // Default list ID
    'sub_register' => 0,    // Offer subscription during registration
    'del_user_unsub' => 0,  // deleted users are unsubscribed
    'dbl_optin_members' => 1,    // Require doubld-opt-in for profile changes
    'sync_at_login' => 1,   // Sync with MC at user login?
    'webhook_key' => '',    // Key value for webhook. Must be "?key=" in url
    'webhook_handlesub' => 1,   // handle Mailchimp subscriptions
    'webhook_handleunsub' => 1, // handle Mailchimp unsubscribe
    'webhook_handleupemail' => 0,  // Update email address in 'users' table
    'debug' => 0,           // 1 to enable webhook debugging
);


/**
* Initialize MailChimp plugin configuration
*
* Creates the database entries for the configuation if they don't already
* exist. Initial values will be taken from $_CONF_MLCH if available (e.g. from
* an old config.php), uses $_MLCH_DEFAULT otherwise.
*
* @return   boolean     true: success; false: an error occurred
*
*/
function plugin_initconfig_mailchimp()
{
    global $_CONF_MLCH, $_MLCH_DEFAULT;

    if (is_array($_CONF_MLCH) && (count($_CONF_MLCH) > 1)) {
        $_MLCH_DEFAULT = array_merge($_MLCH_DEFAULT, $_CONF_MLCH);
    }

    $c = config::get_instance();
    if (!$c->group_exists($_CONF_MLCH['pi_name'])) {

        $c->add('sg_main', NULL, 'subgroup',
                0, 0, NULL, 0, true, $_CONF_MLCH['pi_name']);
        $c->add('fs_main', NULL, 'fieldset',
                0, 0, NULL, 0, true, $_CONF_MLCH['pi_name']);

        $c->add('api_key', $_MLCH_DEFAULT['api_key'], 'text',
                0, 0, 0, 10, true, $_CONF_MLCH['pi_name']);
        $c->add('def_list', $_MLCH_DEFAULT['def_list'], 'select',
                0, 0, 0, 20, true, $_CONF_MLCH['pi_name']);
        $c->add('sub_register', $_MLCH_DEFAULT['sub_register'], 'select',
                0, 0, 4, 30, true, $_CONF_MLCH['pi_name']);
        $c->add('del_user_unsub', $_MLCH_DEFAULT['del_user_unsub'], 'select',
                0, 0, 0, 40, true, $_CONF_MLCH['pi_name']);
        $c->add('dbl_optin_members', $_MLCH_DEFAULT['dbl_optin_members'], 'select',
                0, 0, 0, 50, true, $_CONF_MLCH['pi_name']);
        $c->add('sync_at_login', $_MLCH_DEFAULT['sync_at_login'], 'select',
                0, 0, 0, 50, true, $_CONF_MLCH['pi_name']);

        // Mailchimp webhook integrations
        $c->add('fs_webhook', NULL, 'fieldset',
                0, 10, NULL, 0, true, $_CONF_MLCH['pi_name']);

        $c->add('webhook_key', $_MLCH_DEFAULT['webhook_key'],
                'text', 0, 10, 0, 100, true, $_CONF_MLCH['pi_name']);
        $c->add('webhook_handlesub', $_MLCH_DEFAULT['webhook_handlesub'],
                'select', 0, 10, 0, 110, true, $_CONF_MLCH['pi_name']);
        $c->add('webhook_handleunsub', $_MLCH_DEFAULT['webhook_handleunsub'],
                'select', 0, 10, 0, 120, true, $_CONF_MLCH['pi_name']);
        $c->add('webhook_handleupemail', $_MLCH_DEFAULT['webhook_handleupemail'],
                'select', 0, 10, 0, 130, true, $_CONF_MLCH['pi_name']);
        $c->add('debug', $_MLCH_DEFAULT['debug'],
                'select', 0, 10, 0, 140, true, $_CONF_MLCH['pi_name']);

        return true;
    } else {
        return false;
    }
}

?>
