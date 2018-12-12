<?php
/**
 * Automatic installation routines for the Mailchimp plugin.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2012 Lee Garner <lee@leegarner.com>
 * @package     mailchimp
 * @version     v0.0.1
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

if (!defined ('GVERSION')) {
    die ('This file can not be used on its own.');
}

/** @global string $_DB_dbms */
global $_DB_dbms;

$pi_path = dirname(__FILE__);

/** Include plugin functions manually, since it's not installed yet */
require_once $pi_path . '/functions.inc';
/** Include database definitions */
require_once $pi_path . '/sql/'. $_DB_dbms. '_install.php';

/** Plugin installation options.
 * @global array $INSTALL_plugin['mailchimp']
 */
$INSTALL_plugin['mailchimp'] = array(
    'installer' => array(
        'type' => 'installer',
        'version' => '1',
        'mode' => 'install',
    ),

    'plugin' => array(
        'type' => 'plugin',
        'name'      => $_CONF_MLCH['pi_name'],
        'ver'       => $_CONF_MLCH['pi_version'],
        'gl_ver'    => $_CONF_MLCH['gl_version'],
        'url'       => $_CONF_MLCH['pi_url'],
        'display'   => $_CONF_MLCH['pi_display_name'],
    ),

    array(
        'type' => 'table',
        'table'     => $_TABLES['mailchimp_cache'],
        'sql'       => $_SQL['mailchimp_cache'],
    ),

    array(
        'type' => 'group',
        'group' => 'mailchimp Admin',
        'desc' => 'Users in this group can administer the Mailchimp plugin',
        'variable' => 'admin_group_id',
        'admin' => true,
        'addroot' => true,
    ),

    array(
        'type' => 'feature',
        'feature' => 'mailchimp.admin',
        'desc' => 'Mailer Administration access',
        'variable' => 'admin_feature_id',
    ),

    array(
        'type' => 'block',
        'name' => 'mailchimp_subscribe',
        'title' => $LANG_MLCH['block_title'],
        'phpblockfn' => 'phpblock_mailchimp_sub',
        'block_type' => 'phpblock',
        'group_id' => 'admin_group_id',
    ),

    array(
        'type' => 'mapping',
        'group' => 'admin_group_id',
        'feature' => 'admin_feature_id',
        'log' => 'Adding admin feature to the Mailchimp Admingroup',
    ),
);

/**
 * Puts the datastructures for this plugin into the glFusion database.
 * Note: Corresponding uninstall routine is in functions.inc.
 *
 * @return  boolean     True if successful False otherwise
 */
function plugin_install_mailchimp()
{
    global $INSTALL_plugin, $_CONF_MLCH;

    COM_errorLog("Attempting to install the {$_CONF_MLCH['pi_display_name']} plugin", 1);

    $ret = INSTALLER_install($INSTALL_plugin[$_CONF_MLCH['pi_name']]);
    if ($ret > 0) {
        return false;
    }

    return true;
}


/**
 * Load plugin configuration into the database.
 *
 * @see     plugin_initconfig_mailchimp
 * @return  boolean     true on success, otherwise false
 */
function plugin_load_configuration_mailchimp()
{
    global $_CONF;

    require_once $_CONF['path_system'] . 'classes/config.class.php';
    require_once __DIR__ . '/install_defaults.php';

    return plugin_initconfig_mailchimp();
}

?>
