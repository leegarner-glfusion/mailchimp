<?php
/**
 * Upgrade routines for the Mailchimp plugin.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2012 Lee Garner <lee@leegarner.com>
 * @package     mailchimp
 * @version     v0.0.1
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

// Required to get the config values
global $_CONF, $_MLCH_CONF, $_DB_dbms;

/**
 * Perform the upgrade starting at the current version.
 *
 * @param   boolean $dvlp   True for development update, ignore errors
 * @return  integer                 Error code, 0 for success
 */
function MLCH_do_upgrade($dvlp=false)
{
    global $_MLCH_CONF;

    $pi_name = $_MLCH_CONF['pi_name'];
    if (isset($_PLUGIN_INFO[$pi_name])) {
        if (is_array($_PLUGIN_INFO[$pi_name])) {
            // glFusion >= 1.6.6
            $current_ver = $_PLUGIN_INFO[$pi_name]['pi_version'];
        } else {
            // legacy
            $current_ver = $_PLUGIN_INFO[$pi_name];
        }
    } else {
        return false;
    }
    $installed_ver = plugin_chkVersion_mailchimp();

    if (!COM_checkVersion($current_ver, '0.0.3')) {
        // upgrade to 0.0.3
        $current_ver = '0.0.3';
        if (!MLCH_upgrade_0_0_3()) return false;
    }

    // Update the plugin configuration
    USES_lib_install();
    global $mailchimpConfigData;
    require_once __DIR__ . '/install_defaults.php';
    _update_config('mailchimp', $mailchimpConfigData);
    if (!COM_checkVersion($current_ver, $installed_ver)) {
        if (!MLCH_do_set_version($installed_ver)) return false;
    }

    return $error;
}


/**
 * Upgrade to version 0.3.3.
 *
 * @return  boolean     True on success, False on error
 */
function MLCH_upgrade_0_0_3()
{
    global $_TABLES;

    // Install administration feature and map to existing admin group
    $ft_name = 'mailchimp.admin';
    $ft_desc = 'Mailchimp Administration';
    DB_query("INSERT INTO {$_TABLES['features']} (ft_name, ft_descr)
            VALUES ('$ft_name', '$ft_desc')", 1);
    if (DB_error()) {
        COM_errorLog("Upgrade: Mailchimp feature creation failed!");
        return 1;
    }
    $ft_id = DB_insertId();
    $grp_id = (int)DB_getItem($_TABLES['groups'],'grp_id',
            "grp_name = 'mailchimp Admin'");
    if ($grp_id > 0) {
        DB_query("INSERT INTO {$_TABLES['access']} (acc_ft_id, acc_grp_id)
            VALUES ($ft_id, $grp_id)", 1);
    }
    if (DB_error()) {
        COM_errorLog("upgrade: Mailchimp feature mapping failed!");
        return 1;
    }

    $status = MLCH_do_upgrade_sql('0.0.3');
    if ($status) {
        // If upgrade went ok, sync our cache to the current Mailchimp list
        MLCH_sync_cache();
    }
    return $status;
}


/**
 * Actually perform any sql updates.
 * If there are no SQL statements, then SUCCESS is returned.
 *
 * @param   string  $version    Version being upgraded TO
 * @param   boolean $dvlp       True to ignore errors
 * @return  boolean     True on success, False on failure
 */
function MLCH_do_upgrade_sql($version = 'Undefined', $dvlp=false
{
    global $_TABLES, $_CONF_MLCH, $_MLCH_UPGRADE_SQL;

    require_once __DIR__  . '/sql/mysql_install.php';

    // Execute a single SQL query, if supplied.
    if (!empty($sql)) {
        DB_query($sql, 1);
        return DB_error() ? false : true;
    }

    // We control this, so it shouldn't happen, but just to be safe...
    if ($version == 'Undefined') {
        COM_errorLog("Error updating {$_CONF_MLCH['pi_name']} - Undefined Version");
        return false;
    }

    // If no sql statements passed in, return success
    if (!isset($_MLCH_UPGRADE_SQL[$version]) ||
            !is_array($_MLCH_UPGRADE_SQL[$version]))
        return true;

    // Execute SQL now to perform the upgrade
    foreach ($_MLCH_UPGRADE_SQL[$version] as $sql) {
        COM_errorLOG("Mailchimp Plugin $version update: Executing SQL => $sql");
        DB_query($sql, '1');
        if (DB_error()) {
            COM_errorLog("SQL Error during Mailchimp plugin update",1);
            if (!$dvlp) return false;
        }
    }
    return true;
}


/**
 * Update the plugin version number in the database.
 * Called at each version upgrade to keep up to date with
 * successful upgrades.
 *
 * @param   string  $ver    New version to set
 * @return  boolean         True on success, False on failure
 */
function MLCH_do_set_version($ver)
{
    global $_TABLES, $_MLCH_CONF;

    $ver = DB_escapeString($ver);
    // now update the current version number.
    $sql = "UPDATE {$_TABLES['plugins']} SET
            pi_version = '$ver',
            pi_gl_version = '{$_MLCH_CONF['gl_version']}',
            pi_homepage = '{$_MLCH_CONF['pi_url']}'
        WHERE pi_name = '{$_MLCH_CONF['pi_name']}'";

    $res = DB_query($sql, 1);
    if (DB_error()) {
        COM_errorLog("Error updating the {$_MLCH_CONF['pi_display_name']} Plugin version to $ver",1);
        return false;
    } else {
        return true;
    }
}



?>
