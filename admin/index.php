<?php
//  $Id: index.php 28 2011-01-14 17:36:55Z root $
/**
*   Administrative entry point for the MailChimp plugin
*   No admin function is available
*
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2012 Lee Garner <lee@leegarner.com>
*   @package    mailchimp
*   @version    0.0.1
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/

require_once '../../../lib-common.php';

$pi_title = $_CONF_MLCH['pi_display_name'] . ' Version ' .
                $_CONF_MLCH['pi_version'];

// If user isn't a root user or if the backup feature is disabled, bail.
if (!SEC_hasRights('mailchimp.admin')) {
    $display .= COM_siteHeader('menu', $pi_title);
    $display .= '<span class="alert">You do not have access to this area.</span>';
    $display .= COM_siteFooter();
    COM_accessLog("User {$_USER['username']} tried to illegally access the mailchimp admin screen.");
    echo $display;
    exit;
}


/**
*   Import our current users to our subscriber list.
*   Only imports users that are not in the cache table
*
*   Future option
*   @return string - success message
*/
function MLCH_importUsers()
{
    global $_TABLES, $LANG_MLCH, $_CONF_MLCH;

    // if no api key, do nothing
    if (!MAILCHIMP_ACTIVE) {
        COM_errorLog('Mailchimp is not active. API Key entered?');
        return '';
    }
    $retval = '';
    $emails = array();

    // Collect uer info. mc.uid will be NULL if the user has never
    // been updated
    $sql = "SELECT u.uid as u_uid,u.email,mc.uid FROM {$_TABLES['users']} u
            LEFT JOIN {$_TABLES['mailchimp_cache']} mc
                ON u.uid = mc.uid
            WHERE u.uid > 2 AND u.status= 3 AND mc.uid IS NULL";
    $result = DB_query($sql);
    $list_id = DB_escapeString($_CONF_MLCH['def_list']);
    $cache_vals = array();
    while ($A = DB_fetchArray($result, false)) {
       $status = LGLIB_invokeService('membership', 'mailingSegment',
             array('uid'=>$A['u_uid'], 'email'=>$A['email']), $segment, $msg);
        $memstatus = $status == PLG_RET_OK ? $segment : '';
        $merge_vars = array('MEMSTATUS' => $memstatus);  // always update
        $emails[] = array('email' => array(
                'email' => $A['email'],
                'merge_vars' => $merge_vars,
        ) );
        //$retval .= "DEBUG: {$A['u_uidi']} -- $segment<br />\n";

        // Save the pertinent data, $sub will be updated by MailChimp status
        $cache_vals[$A['email']] = array('uid' => $A['u_uid'], 'sub' => 0);
    }

    USES_mailchimp_class_api();
    $api = new Mailchimp($_CONF_MLCH['api_key']);
    $params = array(
        'id' => $_CONF_MLCH['def_list'],
        'batch' => $emails,
        'double_optin' => true,
        'update_existing' => false,
        'replace_interests' => false,
    );
    $mc_status = $api->call('lists/batch-subscribe', $params);
    if (isset($mc_status['error'])) {
        $retval .= '<span class="alert">' . $mc_status['error'] . '</span>';
    } else {
        $stats = array(
            'add' => 'Additions',
            'update' => 'Updates',
        );
        foreach ($stats as $key => $name) {
            $retval .= "<p>$name: ({$mc_status[$key.'_count']})<br />\n";
            foreach ($mc_status[$key.'s'] as $data) {
                $retval .= $data['email'];
                if (isset($data['error'])) {
                    $retval .= ' -- ' . $data['error'];
                }
                $retval .= "<br />\n";
            }
            $retval .= "</p>\n";
        }
        $retval .= "<p>Errors: ({$mc_status['error_count']})<br />\n";
        foreach ($mc_status['errors'] as $data) {
            if ($data['code'] == 214) {
                // already subscribed, update the value for the DB
                $cache_vals[$data['email']['email']]['sub'] = 1;
            }
            $retval .= $data['error'] . "<br />\n";
        }
        $retval .= "</p>\n";
    }

    // Update the cache table. All imported users will be set to unsubscribed
    // which will be updated when they confirm. $info['sub'] has been changed
    // from 0 to 1 if MailChimp reports that they're already subscribed. 
    $sql_vals = array();
    foreach ($cache_vals as $email => $info) {
        $sql_vals [] = "({$info['uid']}, '$list_id', {$info['sub']})";
    }
    if (!empty($sql_vals)) {
        $values = implode(',', $sql_vals);
        $upd_sql = "INSERT INTO {$_TABLES['mailchimp_cache']} VALUES $values";
        DB_query($upd_sql, 1);
    }
    
    return $retval;       
}


/**
*   Create the main menu
*
*   @param  string  $explanation    Instruction text
*   @return string  HTML for menu area
*/
function MLCH_adminMenu()
{
    global $_CONF, $LANG_ADMIN, $LANG_MLCH, $pi_title;

    USES_lib_admin();

    $retval = '';

    $token = SEC_createToken();
    $menu_arr = array(
        array('url' => MLCH_ADMIN_URL . '?updcache=x',
              'text' => $LANG_MLCH['sync_cache']),
        array('url' => MLCH_ADMIN_URL . '?importusers=x',
              'text' => $LANG_MLCH['import_users']),
        array('url' => $_CONF['site_admin_url'],
              'text' => $LANG_ADMIN['admin_home']),
    );
    $retval .= COM_startBlock($pi_title,
                COM_getBlockTemplate('_admin_block', 'header'));
    $retval .= ADMIN_createMenu(
            $menu_arr, $LANG_MLCH['instr_admin'],
            plugin_geticon_mailchimp());
    $retval .= COM_endBlock();

    return $retval;
}


$action = '';
$expected = array('updcache','importusers');
foreach($expected as $provided) {
    if (isset($_POST[$provided])) {
        $action = $provided;
    } elseif (isset($_GET[$provided])) {
        $action = $provided;
    }
}

$content = '';
switch ($action) {
case 'importusers':
    $content .= MLCH_importUsers();
    break;

case 'updcache':
    $content .= MLCH_syncCache();
    break;
}

echo COM_siteHeader('menu', $pi_title);
echo MLCH_adminMenu();
echo $content;
echo COM_siteFooter();

?>
