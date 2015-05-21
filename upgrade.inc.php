<?php
/**
*   Upgrade routines for the Mailchimp plugin
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2012 Lee Garner <lee@leegarner.com>
*   @package    mailchimp
*   @version    0.0.1
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/

// Required to get the config values
global $_CONF, $_MLCH_CONF, $_DB_dbms;

/** Include database definitions */
require_once dirname(__FILE__) . '/sql/'. $_DB_dbms. '_install.php';


/**
*   Perform the upgrade starting at the current version.
*   This plugin has no tables but may get new configuration items.
*
*   @param  string  $current_ver    Current installed version to be upgraded
*   @return integer                 Error code, 0 for success
*/
function MLCH_do_upgrade($current_ver)
{
    global $_MLCH_CONF;

    $error = 0;

    if ($current_ver < '0.0.3') {
        $error = MLCH_upgrade_0_0_3();
        if ($error) return $error;
    }

    return $error;
}


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
    if ($status == 0) {
        // If upgrade went ok, sync our cache to the current Mailchimp list
        MLCH_sync_cache();
    }
    return $status;
}


/**
*   Actually perform any sql updates.
*   If there are no SQL statements, then SUCCESS is returned.
*
*   @param  string  $version    Version being upgraded TO
*   @param  array   $sql        Array of SQL statement(s) to execute
*   @return integer             0 for success, >0 for failure
*/
function MLCH_do_upgrade_sql($version = 'Undefined', $sql='')
{
    global $_TABLES, $_CONF_MLCH, $_MLCH_UPGRADE_SQL;

    require_once dirname(__FILE__) . '/install_defaults.php';

    // Execute a single SQL query, if supplied.
    if (!empty($sql)) {
        DB_query($sql, 1);
        return DB_error() ? 1 : 0;
    }

    // We control this, so it shouldn't happen, but just to be safe...
    if ($version == 'Undefined') {
        COM_errorLog("Error updating {$_CONF_MLCH['pi_name']} - Undefined Version");
        return 1;
    }

    // If no sql statements passed in, return success
    if (!isset($_MLCH_UPGRADE_SQL[$version]) || 
            !is_array($_MLCH_UPGRADE_SQL[$version]))
        return 0;

    // Execute SQL now to perform the upgrade
    foreach ($_MLCH_UPGRADE_SQL[$version] as $sql) {
        COM_errorLOG("Mailchimp Plugin $version update: Executing SQL => $sql");
        DB_query($sql, '1');
        if (DB_error()) {
            COM_errorLog("SQL Error during Mailchimp plugin update",1);
            return 1;
            break;
        }
    }

    return 0;

}

?>
