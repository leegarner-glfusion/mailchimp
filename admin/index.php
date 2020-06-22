<?php
/**
 * Administrative entry point for the MailChimp plugin.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2012 Lee Garner <lee@leegarner.com>
 * @package     mailchimp
 * @version     v0.1.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

require_once '../../../lib-common.php';

$pi_title = $_CONF_MLCH['pi_display_name'] . ' Version ' .
                $_CONF_MLCH['pi_version'];

// If user isn't a root user or if the backup feature is disabled, bail.
if (!SEC_hasRights('mailchimp.admin')) {
    Mailchimp\Logger::System("User {$_USER['username']} tried to illegally access the mailchimp admin screen.");
    COM_404();
    exit;
}


/**
 * Import our current users to our subscriber list.
 * Only imports users that are not in the cache table.
 * Updates the list segment from the Membership plugin if available.
 *
 * @return  string  Success or Error message
 */
function MLCH_importUsers()
{
    global $_TABLES, $LANG_MLCH, $_CONF_MLCH;

    // if no api key, do nothing
    if (!MAILCHIMP_ACTIVE) {
        Mailchimp\Logger::System('Mailchimp is not active. API Key entered?');
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
    //USES_mailchimp_class_api();
    $api = Mailchimp\API::getInstance();
    while ($A = DB_fetchArray($result, false)) {
        $status = PLG_invokeService('membership', 'mailingSegment',
            array(
                'uid'   => $A['u_uid'],
                'email' => $A['email'],
            ),
            $segment,
            $msg
        );
        $args = array(
            'email_address' => $A['email'],
            'status' => $_CONF_MLCH['dbl_optin_members'] ? 'pending' : 'subscribed',
        );
        if ($status == PLG_RET_OK) {
            // Add member status, only if not empty
            $args['merge_fields'] = array('MEMSTATUS' => $segment);
        }
        $mc_status = $api->subscribe($A['email'], $args, $list_id);
        if ($api->success()) {
            $cache_vals[$A['email']] = array('uid' => $A['u_uid'], 'sub' => 0);
            $success++;
        } else {
            $msg .= $api->getLastError() . '<br />' . LB;
            $errors++;
        }
    }
    $retval .= "<p>Success: $success<br />\n";
    $retval .= "Errors: $errors</p>\n";
    $retval .= "<p>$msg</p>\n";

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
 * Create the main menu.
 *
 * @param   string  $explanation    Instruction text
 * @return  string  HTML for menu area
 */
function MLCH_adminMenu()
{
    global $_CONF, $LANG_ADMIN, $LANG_MLCH, $pi_title;

    USES_lib_admin();

    $retval = '';

    $token = SEC_createToken();
    $menu_arr = array(
        array(
            'url' => $_CONF['site_admin_url'],
            'text' => $LANG_ADMIN['admin_home'],
        ),
    );
    $retval .= COM_startBlock($pi_title, '',
                COM_getBlockTemplate('_admin_block', 'header'));
    $retval .= ADMIN_createMenu(
            $menu_arr, $LANG_MLCH['instr_admin'],
            plugin_geticon_mailchimp());
    $T = new \Template(MLCH_PI_PATH . '/templates');
    $T->set_file('funcs', 'maint.thtml');
    $T->set_var('admin_url', MLCH_ADMIN_URL . '/index.php');
    $T->parse('output', 'funcs');
    $retval .= $T->finish($T->get_var('output'));
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
    //$txt = MLCH_syncCache();
    $txt = Mailchimp\Subscriber::syncAllFromMailchimp();
    $content .= COM_showMessageText($txt);
    break;
}

echo COM_siteHeader('menu', $pi_title);
echo MLCH_adminMenu();
echo $content;
echo COM_siteFooter();

?>
