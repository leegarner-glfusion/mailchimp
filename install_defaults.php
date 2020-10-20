<?php
/**
 * Default items for the MailChimp plugin.
 *
 * Initial Installation Defaults used when loading the online configuration
 * records. These settings are only used during the initial installation
 * and not referenced any more once the plugin is installed, except when
 * upgrading
 *
 * @author     Lee Garner <lee@leegarner.com>
 * @copyright  Copyright (c) 2012-2018 Lee Garner <lee@leegarner.com>
 * @package    mailchimp
 * @version    0.1.0
 * @license    http://opensource.org/licenses/gpl-2.0.php
 *             GNU Public License v2 or later
 * @filesource
 */

if (!defined ('GVERSION')) {
    die('This file can not be used on its own!');
}

/**
 * Mailchimp default settings
 *
 * Initial Installation Defaults used when loading the online configuration
 * records. These settings are only used during the initial installation
 * and not referenced any more once the plugin is installed
 *
 * @global  array
 */
$mailchimpConfigData = array(
    array(
        'name' => 'sg_main',
        'default_value' => NULL,
        'type' => 'subgroup',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => NULL,
        'sort' => 0,
        'set' => true,
        'group' => 'mailchimp',
    ),
    array(
        'name' => 'fs_main',
        'default_value' => NULL,
        'type' => 'fieldset',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => NULL,
        'sort' => 0,
        'set' => true,
        'group' => 'mailchimp',
    ),
    array(
        'name' => 'api_key',
        'default_value' => '',
        'type' => 'text',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 0,
        'sort' => 10,
        'set' => true,
        'group' => 'mailchimp',
    ),
    array(
        'name' => 'def_list',
        'default_value' => '',
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 0,
        'sort' => 20,
        'set' => true,
        'group' => 'mailchimp',
    ),
    array(
        'name' => 'sub_register',
        'default_value' => 0,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 4,
        'sort' => 30,
        'set' => true,
        'group' => 'mailchimp',
    ),
    array(
        'name' => 'del_user_unsub',
        'default_value' => 0,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 0,
        'sort' => 40,
        'set' => true,
        'group' => 'mailchimp',
    ),
    array(
        'name' => 'dbl_optin_members',
        'default_value' => 1,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 0,
        'sort' => 50,
        'set' => true,
        'group' => 'mailchimp',
    ),
    array(
        'name' => 'sync_at_login',
        'default_value' => 1,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 0,
        'sort' => 60,
        'set' => true,
        'group' => 'mailchimp',
    ),
    array(
        'name' => 'log_level',
        'default_value' => '200',
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 0,
        'selection_array' => 18,
        'sort' => 70,
        'set' => true,
        'group' => 'mailchimp',
    ),

    array(
        'name' => 'fs_webhook',
        'default_value' => NULL,
        'type' => 'fieldset',
        'subgroup' => 0,
        'fieldset' => 10,
        'selection_array' => NULL,
        'sort' => 10,
        'set' => true,
        'group' => 'mailchimp',
    ),
    array(
        'name' => 'webhook_key',
        'default_value' => '',
        'type' => 'text',
        'subgroup' => 0,
        'fieldset' => 10,
        'selection_array' => 0,
        'sort' => 10,
        'set' => true,
        'group' => 'mailchimp',
    ),
    array(
        'name' => 'webhook_handlesub',
        'default_value' => 1,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 10,
        'selection_array' => 0,
        'sort' => 20,
        'set' => true,
        'group' => 'mailchimp',
    ),
    array(
        'name' => 'webhook_handleunsub',
        'default_value' => 1,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 10,
        'selection_array' => 0,
        'sort' => 30,
        'set' => true,
        'group' => 'mailchimp',
    ),
    array(
        'name' => 'webhook_handleupemail',
        'default_value' => 0,
        'type' => 'select',
        'subgroup' => 0,
        'fieldset' => 10,
        'selection_array' => 0,
        'sort' => 40,
        'set' => true,
        'group' => 'mailchimp',
    ),
);


/**
 * Initialize MailChimp plugin configuration.
 *
 * Creates the database entries for the configuation if they don't already
 * exist. Initial values will be taken from $_CONF_MLCH if available (e.g. from
 * an old config.php), uses $_MLCH_DEFAULT otherwise.
 *
 * @return  boolean     true: success; false: an error occurred
 */
function plugin_initconfig_mailchimp()
{
    global $mailchimpConfigData;

    $c = config::get_instance();
    if (!$c->group_exists('mailchimp')) {
        USES_lib_install();
        foreach ($mailchimpConfigData AS $cfgItem) {
            _addConfigItem($cfgItem);
        }
    }
    return true;
}

?>
