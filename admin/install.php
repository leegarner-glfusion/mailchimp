<?php
//  $Id: install.php 12 2010-09-13 22:06:32Z root $
/**
*   Installation routine for the MailChimp plugin
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2012 Lee Garner <lee@leegarner.com>
*   @package    mailchimp
*   @version    0.0.1
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/


/** Include core glFusion library */
require_once '../../../lib-common.php';
/** Include plugin auto-installation routines */
require_once $_CONF['path'].'/plugins/mailchimp/autoinstall.php';

USES_lib_install();

if (!SEC_inGroup('Root')) {
    // Someone is trying to illegally access this page
    COM_errorLog("Someone has tried to illegally access the MailChimp Plugin install/uninstall page.  User id: {$_USER['uid']}, Username: {$_USER['username']}, IP: $REMOTE_ADDR",1);
    $display = COM_siteHeader ('menu', $LANG_ACCESS['accessdenied'])
             . COM_startBlock ($LANG_ACCESS['accessdenied'])
             . $LANG_ACCESS['plugin_access_denied_msg']
             . COM_endBlock ()
             . COM_siteFooter ();
    echo $display;
    exit;
}

/**
* Main Function
*/
if (SEC_checkToken()) {
    $action = COM_applyFilter($_GET['action']);
    if ($action == 'install') {
        if (plugin_install_mailchimp()) {
    		// Redirects to the plugin editor
    		echo COM_refresh($_CONF['site_admin_url'] . '/plugins.php?msg=44');
    		exit;
        } else {
    		echo COM_refresh($_CONF['site_admin_url'] . '/plugins.php?msg=72');
    		exit;
        }
    } else if ($action == 'uninstall') {
        USES_lib_plugin();
        if (PLG_uninstall('mailchimp')) {
    		echo COM_refresh($_CONF['site_admin_url'] . '/plugins.php?msg=45');
    		exit;
    	} else {
    		echo COM_refresh($_CONF['site_admin_url'] . '/plugins.php?msg=73');
    		exit;
    	}
    }
}

echo COM_refresh($_CONF['site_admin_url'] . '/plugins.php');

?>
