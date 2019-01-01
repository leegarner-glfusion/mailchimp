<?php
/**
 * API functions for the MailChimp plugin
 * This plugin integrats with MailChimp (www.mailchimp.com).
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2012-2018 Lee Garner <lee@leegarner.com>
 * @package     mailchimp
 * @version     v0.1.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

if (!defined ('GVERSION')) {
    die('This file can not be used on its own.');
}

/** Define a numeric value for Debug-level logging */
define(MLCH_DEBUG, 1);
/** Define a numeric value for Informational logging */
define(MLCH_INFO, 2);
/** Define a numeric value for Warning-level logging */
define(MLCH_WARN, 4);
/** Define a numeric value for Critical error logging */
define(MLCH_CRIT, 8);

/** Language file include */
$lang = $_CONF['language'];
$langfile = __DIR__ . '/language/' . $lang . '.php';
if (file_exists($langfile)) {
    require_once $langfile;
} else {
    $lang = 'english';
    require_once __DIR__ . '/language/english.php';
}
if (file_exists(__DIR__ . '/language/custom/' . $lang. '.php')) {
    // Include custom language strings, if available
    include_once __DIR__ . '/language/custom/' . $lang. '.php';
}

// Check and see if we need to load the plugin configuration
if (!isset($_CONF_MLCH['confirm_period'])) {
    require_once $_CONF['path_system'] . 'classes/config.class.php';

    $mlr_config = config::get_instance();
    $_CONF_MLCH = $mlr_config->get_config('mailchimp');
}
// On rare occasions, a list ID can look like a numeric value and glFusion's
// config system truncates the value. To prevent this, an 'x' is prepended
// to the value to force it to be a string. Now we remove the 'x' to get back
// to the real list ID.
if ($_CONF_MLCH['def_list'][0] == 'x') {
    $_CONF_MLCH['def_list'] = substr($_CONF_MLCH['def_list'], 1);
}

require_once __DIR__ . '/mailchimp.php';
if (is_file(__DIR__ . '/config.php')) {
    include_once __DIR__ . '/config.php';
}

// Set a flag to indicate that an API key has been entered. If not, we
// don't want to call the MC API just to get errors.
/** Indicator that the Mailchimp plugin is active */
define(MAILCHIMP_ACTIVE, empty($_CONF_MLCH['api_key']) ? 0 : 1);
/** Guest-facing plugin URL */
define(MLCH_URL, $_CONF['site_url'] . '/' . $_CONF_MLCH['pi_name']);
/** Admin-facing plugin URL */
define(MLCH_ADMIN_URL, $_CONF['site_admin_url'] . '/plugins/' .
        $_CONF_MLCH['pi_name']);
/** Define the path to the plugin. */
define(MLCH_PI_PATH, __DIR__);

spl_autoload_register(function($class)
{
    if (strpos($class, 'Mailchimp\\') === 0) {
        $class = str_replace('Mailchimp\\', '', $class);
        $path = __DIR__ . '/classes/' . $class . '.class.php';
        if (file_exists($path)) {
            include $path;
        }
    }
});


/**
 * This will put an option for mailchimp in the command and control block on moderation.php.
 *
 * @return  mixed   Array(text, url, icon) or false if not allowed
 */
function plugin_cclabel_mailchimp()
{
    global $_CONF_MLCH;

    if (SEC_hasRights('mailchimp.admin', 'OR')) {
        return array ($_CONF_MLCH['pi_display_name'],
                MLCH_ADMIN_URL . '/index.php',
                plugin_geticon_mailchimp());
    }
    return false;
}


/**
 * Return the current version of code.
 * Used by the plugin admin to determine if an upgrade needs to be done.
 *
 * @return  string      Current code version
 */
function plugin_chkVersion_mailchimp()
{
    global $_CONF_MLCH;
    return $_CONF_MLCH['pi_version'];
}


/**
 * Adds the plugin to the Admin menu.
 *
 * @return  array   Array(pi_name, admin url, # of items) or '' for none.
 */
function plugin_getadminoption_mailchimp()
{
    global $_CONF, $_CONF_MLCH, $LANG_MLCH;

//    if (plugin_ismoderator_mailchimp()) {
        $retval = array(
            $LANG_MLCH['menu_label'],
            "{$_CONF['site_admin_url']}/plugins/{$_CONF_MLCH['pi_name']}/index.php"
        );
/*    } else {
        $retval = '';
    }*/
    return $retval;
}


/**
*   Get the user menu option to control subcriptions
*
*   @return array   Text=>URL for menu
*/
//function plugin_getuseroption_mailchimp()
//{
//  Currently no user options
//}


/**
 * Action when an new user is created.
 * Subscribe to the default list if so configured.
 *
 * @param   integer $uid    User id of the new user account
 */
function plugin_user_create_mailchimp($uid)
{
    // First, sync with mailchimp to get any lists that they might have
    // subscribed to before becoming site members.
    $email = MLCH_syncWithMC($uid);
    if (isset($_POST['mailchimp_subscribe']) &&
            $_POST['mailchimp_subscribe'] == 1 &&
            !MLCH_isSubscribed($uid) ) {
        MLCH_subscribe($uid, $email);
    }
}


/**
 * Unsubscribe deleted users and update mailchimps owned by them.
 *
 * @param   integer $uid    User id of deleted user
 */
function plugin_user_delete_mailchimp($uid)
{
    global $_CONF_MLCH;

    if ($_CONF_MLCH['del_user_unsub']) {
        MLCH_unsubscribe($uid);
    }
}

/**
 * Actions to take when a user logs in.
 * Here we check with Mailchimp to see if they're subscribed to the default
 * list and update the database accordingly, so they won't be prompted to
 * subscribe again.
 *
 * @param   integer $uid    User ID
 */
function plugin_user_login_mailchimp($uid)
{
    global $_CONF_MLCH;

    if ($_CONF_MLCH['sync_at_login']) {
        MLCH_syncWithMC($uid);
    }
}


/**
 * Create an edit form in the Account Settings section.
 * Future version to allow selection of multiple lists and interest groups
 *
 * @param   integer $uid        User ID
 * @param   string  $panel      Panel being populated
 * @param   string  $fieldset   Name of fieldset being populated
 * @return  string              HTML for edit form. Never NULL.
 */
function Xplugin_profileedit_mailchimp($uid, $panel, $fieldset)
{
    echo "here";die;
    // prototype to manage interest groups
    global $_TABLES, $_CONF_MLCH;

    $retval = '';
    if ($panel == '' && $fieldset == '') {
        // Get all the groups for the mailing list
//        $out  = MLCH_getGroups();
$out = MLCH_getLists();
var_dump($out);die;
        if (empty($out)) return $retval;
        $list_grps = $out[$_CONF_MLCH['def_list']];

        // Get all the subscription info for the member
        $out = MLCH_getMemberInfo($uid);
        if (is_array($out[0]['merges']['GROUPINGS'])) {
            $info = $out[0]['merges']['GROUPINGS'];
            foreach ($info as $key=>$data) {
                $sub_grps[$data['id']] = explode(', ', $data['groups']);
            }
        }

        $T = new Template(__DIR__ . '/templates/');
        $T->set_file('profile', 'profile_groups.thtml');
        $T->set_var('mailchimp_checked', $chk);
        $T->set_var('mailchimp_prev_check', $prev);
        $T->parse('output', 'profile');
//var_dump($list_grps);die;
        /* foreach ($list_grps as $key=>$data) {
            echo "\nGroup: id " . $data['id'] . " name: " . $data['name'];
            $grp = $data['groups'];
            foreach ($data['groups'] as $key1=>$grpdata) {
                echo "\n" . $grpdata['name'];
            }
        }*/
        $retval .= $T->finish($T->get_var('output'));
    }
}


/**
 * Create an edit form in the Account Settings section.
 * Adds a yes/no subscription checkbox under the "Privacy" panel
 *
 * @param   integer $uid        User ID
 * @param   string  $panel      Panel being populated
 * @param   string  $fieldset   Name of fieldset being populated
 * @return  string              HTML for edit form. Never NULL.
 */
function plugin_profileedit_mailchimp($uid, $panel, $fieldset)
{
    global $_TABLES, $_CONF_MLCH, $_USER;

    $retval = '';
    $uid = (int)$uid;

    if ($uid == $_USER['uid']) {
        // Get current data from $_USER array
        $old_fullname = $_USER['fullname'];
        $old_email = $_USER['email'];
    } else {
        $sql = "SELECT email, fullname
                FROM {$_TABLES['users']}
                WHERE uid = $uid";
        $res = DB_query($sql, 1);
        $U = DB_fetchArray($res, false);
        $old_fullname = $U['fullname'];
        $old_email = $U['email'];
    }
    if ($panel == 'privacy' && $fieldset == 'privacy') {
        $cnt = DB_count($_TABLES['mailchimp_cache'],
                array('uid', 'list', 'subscribed'),
                array($uid, DB_escapeString($_CONF_MLCH['def_list']), 1) );
        if ($cnt == 1) {
            $chk = 'checked="checked"';
            $prev = 1;
        } else {
            $chk = '';
            $prev = 0;
        }

        $T = new Template(__DIR__ . '/templates/');
        $T->set_file('profile', 'profile.thtml');
        // Set the template vars. mailchimp_prev_check is used when saving to
        // track whether the variable changes before calling the API
        $T->set_var(array(
            'mailchimp_checked' => $chk,
            'mailchimp_prev_check' => $prev,
            'mailchimp_old_fullname' => $old_fullname,
            'mailchimp_old_email' => $old_email,
        ) );
        $T->parse('output', 'profile');
        $retval .= $T->finish($T->get_var('output'));
    }

    return $retval;
}


/**
 * Save the profile information when edited via Account Settings.
 *
 * @param   integer $uid    User ID being saved
 */
function plugin_user_changed_mailchimp($uid)
{
    global $_USER, $_CONF_MLCH;

    if ($uid == $_USER['uid']) {
        // Logged-in user saving their profile, double opt-in not needed unless
        // configured to require it anyway
        $dbl_optin = $_CONF_MLCH['dbl_optin_members'] == 0 ? false : true;
    } else {
        // Administrator is updating the user record, double opt-in required
        $dbl_optin = true;
    }

    // Since this is calling an external API instead of just updating our
    // own database, make sure the subscription selection has actually changed
    if (isset($_POST['mailchimp_subscribe']) &&
        $_POST['mailchimp_oldsub'] == '0') {
        MLCH_subscribe($uid, $_POST['email'], '', $dbl_optin);
    } elseif (!isset($_POST['mailchimp_subscribe']) &&
        $_POST['mailchimp_oldsub'] == '1') {
        MLCH_unsubscribe($uid, $_POST['email']);
    }
    // Get the existing email address to update Mailchimp
    $fname = \LGLib\NameParser::F($_POST['fullname']);
    $lname = \LGLib\NameParser::L($_POST['fullname']);
    MLCH_updateUser($uid,
        array(
            'merge_vars' => array(
                'new-email' => $_POST['email'],
                'FNAME' => $fname,
                'LNAME' => $lname,
            ),
        ),
        $_POST['mailchimp_old_email']
    );
}


/**
 * Actions to perform when the plugin is enabled or disabled.
 *
 * @param   boolean $enable true = we're being enabled, false = disabled
 * @return  boolean     Value of $enable after processing
 */
function plugin_enablestatechange_mailchimp ($enable)
{
    global $_TABLES, $_PLUGINS;

    $is_enabled = $enable ? 1 : 0;

    // If trying to enable the plugin but lgLib is not available,
    // take no action and return
    if ($is_enabled && !in_array('lglib', $_PLUGINS)) {
        // Re-disable the plugin
        DB_change($_TABLES['plugins'], 'pi_enabled', 0, 'pi_name', 'mailchimp');
        $enable = false;
        $is_enabled = 0;
    }

    // Disable the block when plugin is disabled. Don't automatically enable.
    if ($is_enabled == 0) {
         DB_query("UPDATE {$_TABLES['blocks']}
            SET is_enabled = $is_enabled
            WHERE (type = 'phpblock')
            AND (phpblockfn LIKE 'phpblock_mailchimp%')"
        );
    }
    return $enable;
}


/**
 * Receives notification of anothe plugin's deletion.
 * If the LGLib plugin is removed, disable this plugin.
 *
 * @param   mixed   $id     Item ID
 * @param   string  $type   Plugin Name
 */
function plugin_itemdeleted_mailchimp($id, $type)
{
    global $_TABLES;

    return;     // LGLib dependency no longer a factor

    // If the lgLib plugin is disabled or removed, disable thie plugin
    if ($type == 'lglib' && $id == '*') {
        plugin_enablestatechange_mailchimp(false);
        DB_change($_TABLES['plugins'], 'pi_enabled', 0, 'pi_name', 'mailchimp');
    }
}


function plugin_userchanged_mailchimp($uid)
{
    echo "here";die;
}


/**
 * Get information from other plugins when an item is saved.
 *
 * @param   string  $id     Item ID
 * @param   string  $type   Item Type
 * @param   string  $old_id Original ID in case of ID change
 */
function plugin_itemsaved_mailchimp($id, $type, $old_id = '')
{
    $A = explode(':', $type);
    $type = $A[0];  // plugin name
    $subtype = isset($A[1]) ? $A[1] : '';   // plugin item type, if any

    $info = PLG_getItemInfo(
        $type, $id,
        'id,uid,list_segment,email,fullname',
        0,
        array(
            'subtype' => $subtype,
        )
    );
    //var_dump($info);die;
    if ($info !== NULL && !empty($info['list_segment'])) {       // got some information
        $lists = MLCH_listsByUid($id);
        $params = array(
            'merge_fields' => array(
                'MEMSTATUS' => $info['list_segment'],
            )
        );
        foreach ($lists as $L) {
            MLCH_updateUser($uid, $params, $info['email']);
        }
    }
}


/**
 * Get all the lists to which a user is subscribed, from the cache.
 *
 * @param   integer $uid    User ID
 * @return  array       Array of list IDs
 */
function MLCH_listsByUid($uid)
{
    global $_TABLES;

    $uid = (int)$uid;
    $sql = "SELECT list FROM {$_TABLES['mailchimp_cache']}
        WHERE uid = $uid AND subscribed = 1";
    $res = DB_query($sql);
    $retval = array();
    while ($A = DB_fetchArray($res, false)) {
        $retval[] = $A['list'];
    }
    return $retval;
}


/**
 * Automatic uninstallation function for plugins.
 *
 * @return  array   Array of items to be removed
 */
function plugin_autouninstall_mailchimp ()
{
    $out = array (
        // give the name of the tables, without $_TABLES[]
        'tables' => array('mailchimp_cache'),
        // give the full name of the group, as in the db
        'groups' => array('MailChimp Admin'),
        // give the full name of the feature, as in the db
        'features' => array('mailchimp.admin'),
        // give the full name of the block, including 'phpblock_', etc
        'php_blocks' => array('phpblock_mailchimp_sub'),
        // give all vars with their name
        'vars'=> array(),
        // give the full name of the plugins config data
        'config_data' => array('mailchimp')
    );
    return $out;
}


/**
 * Create a block for entering an email address to subscribe.
 * Always shown for Anonymous users, but only for members if they're not
 * already subscribed.
 *
 * @return  string      HTML for subscription block
 */
function phpblock_mailchimp_sub()
{
    global $_CONF, $LANG_MLCH, $_CONF_MLCH, $_USER, $_TABLES;

    if (!COM_isAnonUser()) {
        // Anon always sees the block, no way to know if they're subscribed
        $c = DB_count($_TABLES['mailchimp_cache'],
            array('uid', 'list', 'subscribed'),
            array($_USER['uid'], $_CONF_MLCH['def_list'], 1));
        if ($c > 0) return '';
    }

    $T = new Template(__DIR__ . '/templates/');
    $T->set_file('mailchimp_block', 'block_small.thtml');
    $T->set_var(array(
        'block_text' => sprintf($LANG_MLCH['block_text'], $_CONF['site_url']),
        //'block_button_text' => $LANG_MLCH['block_button_text'],
        //'block_link_text'   => $LANG_MLCH['block_link_text'],
        //'site_name' => $_CONF['site_name'],
    ) );
    $T->parse('output', 'mailchimp_block');
    return $T->finish($T->get_var('output'));
}


/**
 * Set template variables.
 * This addes the subscription checkbox option to the registration form.
 *
 * @param   string  $templatename   name of template, e.g. 'header'
 * @param   object  $template       reference of actual template
 */
function plugin_templatesetvars_mailchimp ($templatename, &$template)
{
    global $_CONF_MLCH, $LANG_MLCH;

    $chk = '';
    $pluginFields = '';
    if (isset($_POST['mailchimp_havedata'])) {
        // Registration form is being re-displayed after an error, get the
        // current status of the checkbox
        $have_input = true;
        if (isset($_POST['mailchimp_subscribe'])) {
            $chk = 'checked="checked"';
        }
    } else {
        $have_input = false;
    }

    if ($templatename == 'registration') {
        $existingPluginFields = $template->get_var('plugin_fields');
        switch ($_CONF_MLCH['sub_register']) {
        case 0:     // disabled
            break;

        case 1:     // checked selection, set if not already set & fall through
            if (!$have_input) $chk = 'checked="checked"';

        case 2:     // unchecked
            $T = new Template(__DIR__ . '/templates/');
            $T->set_file('mailchimp_block', 'registration.thtml');
            $T->set_var('chk', $chk);
            $T->parse('output', 'mailchimp_block');
            $pluginFields = $T->finish($T->get_var('output'));
            break;

        case 3:     // Automatic subscription, don't need a template for this
            $pluginFields =
                '<input type="hidden" name="mailchimp_subscribe value="1" />' .
                LB;
            break;
        }
        if (!empty($pluginFields)) {
            $template->set_var('plugin_fields',
                    $existingPluginFields . $pluginFields);
        }
    }
}


/**
 * Get CSS required for the plugin.
 *
 * @return  array   Array of file paths to load in lib-common.php
 */
function plugin_getheadercss_mailchimp()
{
    global $_CONF, $_CONF_MLCH;

    $styles = array();
    $files = array('style.css',);

    $pi_css = __DIR__ . '/css/';
    $layout_css = "{$_CONF['path_layout']}plugins/{$_CONF_MLCH['pi_name']}/";

    foreach ($files as $file) {
        if (@file_exists($layout_css . $file)) {
            $styles[] = $layout_css . $file;
        } elseif (@file_exists($pi_css . 'custom/' . $file)) {
            $styles[] = $pi_css . 'custom/' . $file;
        } else {
            $styles[] = $pi_css . $file;
        }
    }
    return $styles;
}


/**
 * Return the URL to the plugin's icon.
 *
 * @return  string      URL to the icon
 */
function plugin_geticon_mailchimp()
{
    return MLCH_URL . '/images/mailchimp.png';
}


/**
 * Update the plugin.
 *
 * @return  boolean     True on success, False on error
 */
function plugin_upgrade_mailchimp()
{
    require_once __DIR__ . '/upgrade.inc.php';
    return MLCH_do_upgrade();
}


/**
 * Helper function for the config manager to get a dropdown of mailing lists.
 * On rare occasions, a list ID can look like a numeric value and glFusion's
 * config system truncates the value. To prevent this, an 'x' is prepended
 * to the value to force it to be a string. It will be removed after the
 * configuration is read.
 *
 * @uses    MLCH_getLists()
 * @return  array   (list_text => list_id)
 */
function plugin_configmanager_select_def_list_mailchimp()
{
    $lists = MLCH_getLists();
    $retval = array();
    if ($lists !== false) {
        foreach ($lists as $key=>$data) {
            $retval[$data['name']] = 'x' . $data['id'];
        }
    }
    return $retval;
}


/**
 * Get the URL to the localized configuration help file.
 *
 * @param   string  $option     Help option to display
 * @param   string  $doclang    Language string, default=English
 * @return  array   Array of (url, window_type)
 */
function plugin_getconfigelementhelp_mailchimp($option, $doclang='english')
{
    $doc_url = MLCH_getDocURL('config.html', $doclang);
    if (empty($doc_url))
        return false;

    if (!empty($option)) {
        $doc_url .= '#desc_' . $option;
    }
    return array($doc_url, 0);
}


/**
 * Support merging local accounts into remote accounts.
 *
 * @param   integer $origUID    Original (remote) user ID
 * @param   integer $destUID    Merged (local) user ID
 */
function plugin_user_move_mailchimp($origUID, $destUID)
{
    global $_TABLES;

    $origUID = (int)$origUID;
    $destUID = (int)$destUID;

    DB_query("UPDATE {$_TABLES['mailchimp_cache']}
            SET uid = $destUID WHERE uid = $origUID", 1);
}


/**
 * Return true to enable service functions.
 *
 * @return  boolean     True to enable web service functions
 */
function plugin_wsEnabled_mailchimp()
{
    return true;
}


/**
 * Get the list of Mailchimp lists.
 * Holds lists in a static variable to save API calls.
 *
 * @return  mixed   Array of lists, or false on failure
 */
function MLCH_getLists()
{
    global $_CONF_MLCH;
    static $lists = null;

    if ($lists === null) {
        $lists = array();
        if (MAILCHIMP_ACTIVE && !empty($_CONF_MLCH['api_key'])) {
            //USES_mailchimp_class_api();
            $api = Mailchimp\API::getInstance();
            $list_data = $api->lists();
            if (is_array($list_data)) {
                foreach ($list_data['lists'] as $key => $list) {
                    $members = $api->listMembers($list['id'], 'subscribed', NULL, 0, 0);
                    $lists[$list['id']] = array(
                        'id' => $list['id'],
                        'name' => $list['name'],
                        'members' => $members['total_items'],
                    );
                }
            }
        }
    }
    return $lists;
}


/**
 * Get the Mailchimp groups for a specific list.
 * Holds groups in a static variable to save API calls.
 *
 * @param   string  $list_id    ID of list
 * @return  mixed   Array of lists, or false on failure
 */
function XMLCH_getGroups($list_id='')
{
    global $_CONF_MLCH;

    static $groups = array();
    if (!MAILCHIMP_ACTIVE) return $groups;

    if ($list_id == '') $list_id = $_CONF_MLCH['def_list'];

    if (!isset($groups[$list_id])) {
        //USES_mailchimp_class_api();
        $api = Mailchimp\API::getInstance();
        $groups[$list_id] = $api->call('lists/interest-groupings', array('id'=>$list_id));
    }
    return $groups[$list_id];
}


/**
 * Subscribe an address to a list.
 * For a registered user, takes the user ID and can get the email address from
 * the user record. For anonymous uses, set uid to 0 or 1 and $email is then
 * required.
 *
 * The cache table is updated by the Webhook function after confirmation is
 * received.
 *
 * @param   integer $uid    User ID being subscribed. 0 or 1 indicates anon
 * @param   string  $email  Email address. Taken from user if empty and uid > 1
 * @param   string  $list   List to subscribe, default = $_CONF_MLCH['def_list']
 * @param   boolean $dbl_opt    True (default) to require double-opt-in
 * @return  boolean     True on success, False on failure
 */
function MLCH_subscribe($uid, $email = '', $list = '', $dbl_opt=true)
{
    global $_CONF_MLCH, $LANG_MLCH, $_USER, $_TABLES;

    // if no api key, don't try anything
    if (!MAILCHIMP_ACTIVE) {
        COM_errorLog('Mailchimp is not active. API Key entered?');
        return false;
    }

    // Mailchimp list choice. Not yet implemented.
    //$list = isset($_POST['mlch_list_id']) ?
    //    $_POST['mlch_list_id'] : $_CONF_MLCH['def_list'];
    if (empty($list)) $list = $_CONF_MLCH['def_list'];
    if (empty($list)) return false;

    if ($dbl_opt !== false) $dbl_opt = true;
    if ($uid == 0) $uid = MLCH_getUid($email);
    $uid = (int)$uid;
    $merge_vars = array();

    // Get the first and last name merge values.
    if ($uid > 1) {
        $fullname = $uid == $_USER['uid'] ? $_USER['fullname'] :
                DB_getItem($_TABLES['users'], 'fullname', "uid = $uid");
        if (empty($email)) $email = MLCH_getEmail($uid);

        // Parse the member name ino first and last
        if (!empty($fullname)) {
            $fname = LGLib\NameParser::F($fullname);
            $lname = LGLib\NameParser::L($fullname);
        } else {
            $fname = '';
            $lname = '';
        }
        // only update if there is data (might be values in the list not in
        // the local db)
        if (!empty($fname)) $merge_vars['FNAME'] = $fname;
        if (!empty($lname)) $merge_vars['LNAME'] = $lname;
    } else {
        // Anonymous user. No merge vars available, and must have valid email.
        $merge_vars = NULL;
    }
    if (empty($email)) return false;    // Can't have an empty email address

    // Get the membership status of this subscriber from the Membership
    // plugin, if available. This goes into the merge_vars to segment the
    // list by current, former and nonmember status
    $status = PLG_invokeService('membership', 'mailingSegment',
        array(
            'uid'=>$uid,
            'email'=>$email,
        ),
        $segment,
        $msg
    );
    if ($status == PLG_RET_OK) {
        $merge_vars['MEMSTATUS'] = $segment;    // always update
    }

    // Process Subscription
    $api = \Mailchimp\API::getInstance();
    $params = array(
        'id' => $list,
        'email_address' => $email,
        'merge_fields' => $merge_vars,
        'email_type' => 'html',
        'status' => $_CONF_MLCH['dbl_optin_members'] ? 'pending' : 'subscribed',
        'double_optin' => $dbl_opt,
    );
    $mc_status = $api->subscribe($email, $params, $list);
    if (!$api->success()) {
        MLCH_auditLog("Failed to subscribe $email to $list. Error: " .
            $api->getLastError(), true);
        $retval = false;
    } else {
        if ($uid > 1) {
            MLCH_updateCache($uid, $list);     // Update cache if not anon
        }
        $msg = $dbl_opt ? $LANG_MLCH['dbl_optin_required'] :
                $LANG_MLCH['no_dbl_optin'];
        MLCH_auditLog("Subscribed $email to $list." . $msg);
        $retval = true;
    }
    return $retval;
}


/**
 * Remove a single email address from our list.
 *
 * @param   integer $uid    User ID to unsubscribe
 * @param   string  $email  Email address to remove
 * @param   string  $list   Mailing list being removed. Defaults to def_list
 * @return  boolean         True on success, False on error or plugin inactive
 */
function MLCH_unsubscribe($uid, $email='', $list = '')
{
    global $_CONF_MLCH, $_TABLES;


    // if no api key, do nothing
    if (!MAILCHIMP_ACTIVE) {
        COM_errorLog('Mailchimp is not active. API Key entered?');
        return false;
    }

    if (empty($list)) $list = $_CONF_MLCH['def_list'];
    if ($uid > 1 && empty($email)) {
        $email = MLCH_getEmail($uid);
    }
    if (empty($list)) $list = $_CONF_MLCH['def_list'];
    if (empty($email) || empty($list)) return false;

    $api = Mailchimp\API::getInstance();
    $mc_status = $api->unsubscribe($email, $list);
    if ($api->success()) {
        // List_NotSubscribed error is ok
        MLCH_auditLog("Unsubscribed $email from $list");
        // Delete records from cache.
        $sql = "INSERT INTO {$_TABLES['mailchimp_cache']} SET
                uid = $uid, list='$list', subscribed=0
                ON DUPLICATE KEY UPDATE
                    subscribed=0";
        DB_query($sql, 1);
        $retval = true;
    } else {
        MLCH_auditLog("Failed to unsubscribe $email from $list. Error" .
            $api->getLastError());
        $retval = false;
    }
    return $retval;
}


/**
 * Update the cache table if this site member is subscribed to the list.
 * This is to set the values in their account settings and to
 * decide whether to display any signup block.
 *
 * @param   integer $uid    User ID
 * @param   string  $list   Optional single or array of lists, or default list
 * @param   boolean $clear  Clear the current cache? default = false
 */
function MLCH_updateCache($uid, $list='', $clear=false)
{
    global $_USER, $_CONF_MLCH, $_TABLES;

    $uid = (int)$uid;
    if (empty($list)) $list = $_CONF_MLCH['def_list'];
    if (empty($list)) return;   // still empty, there's no list to check
    $lists = array();
    if (!is_array($list)) {     // Single list
        $lists  = array($list);
    } else {
        $lists = $list;
    }

    // Delete existing entries if requested
    if ($clear) {
        DB_query("UPDATE {$_TABLES['mailchimp_cache']}
                SET subscribed = 0
                WHERE uid = $uid");
    }

    foreach ($lists as $list) {
        $sql = "INSERT INTO {$_TABLES['mailchimp_cache']} SET
                uid = $uid,
                list = '" . DB_escapeString($list) . "',
                subscribed = 1
            ON DUPLICATE KEY UPDATE
                subscribed = 1";
        DB_query($sql, 1);      // Dup errors can be normal
    }
}


/**
 * Get the link to a documentation file.
 * Returns the URL to the file, or to the directory if no file requested.
 *
 * todo:    available in lglib
 * @see     plugin_getconfigelementhelp_mailchimp()
 * @param   string  $file       HTML file
 * @param   string  $doclang    Documentation language
 * @return  string              URL to document directory or file
 */
function MLCH_getDocURL($file, $doclang='')
{
    global $_CONF, $_CONF_MLCH;

    if ($doclang == '') $doclang = $_CONF['language'];
    $doc_path = $_CONF['path_html'] . $_CONF_MLCH['pi_name'] .
                "/docs/$doclang/";
    $doc_url = '';

    if ($file != '') {
        if (!is_file($doc_path . $file)) {
            $doclang = 'english';
        }
        $doc_url = MLCH_URL . "/docs/$doclang/$file";
    } else {
        if (!is_dir($doc_path)) {
            $doclang = 'english';
        }
        $doc_url = MLCH_URL . "/docs/$doclang/";
    }

    return $doc_url;
}


/**
 * Synchronize the local cache table with Mailchimp's.
 * First, delete all list entries and then add all the lists that the
 * member is subscribed to. This is quicker than trying to determine which
 * lists are not in the subscribed group.
 *
 * @param   integer $uid    User ID
 * @return  string          User Email address
 */
function MLCH_syncWithMC($uid)
{
    global $_TABLES, $_CONF_MLCH;

    $uid = (int)$uid;
    if ($uid < 1) return '';    // nothing to do for anon users

    // Get the user's email address & all the lists they belong go.
    $email = MLCH_getEmail($uid);
    //USES_mailchimp_class_api();
    $api = Mailchimp\API::getInstance();
    $out = $api->listsForEmail($email);
    if (is_array($out) && !isset($out['error'])) {
        $lists = array();
        foreach ($out as $list_info) {
            if (isset($list_info['id'])) {
                $lists[] = $list_info['id'];
            }
        }
        MLCH_updateCache($uid, $lists, true);
    }
    return $email;
}


/**
 * Gets the email address for a user
 * This is used a lot, so it's in one function and caches the email
 * address in a static variable.
 *
 * @param   integer $uid    User ID to retrieve
 * @param   boolean $force  True to force read from DB
 * @return  string      User's email address
 */
function MLCH_getEmail($uid, $force=false)
{
    global $_TABLES, $_USER;
    static $emails = array();

    $uid = (int)$uid;

    if ($uid == $_USER['uid']) {
        $emails['uid'] = $_USER['email'];
    } elseif ($uid > 1 && (!isset($emails[$uid]) || $force)) {
        $emails['uid'] = DB_getItem($_TABLES['users'], 'email', "uid=$uid");
    }
    return $emails['uid'];
}


/**
 * Get the user ID given an email address
 *
 * @param   string  $email  Email address
 * @return  integer $uid    User ID, zero if not found
 */
function MLCH_getUid($email)
{
    global $_TABLES, $_USER;

    if ($email == $_USER['email']) {
        return $_USER['uid'];
    }
    return (int)DB_getItem($_TABLES['users'], 'uid', "email='" . DB_escapeString($email) . "'");
}


/**
 * Get information for a specific member.
 * This is for future functionality, to allow members to select
 * multiple lists and interest groups.
 *
 * @param   integer $uid    User ID
 */
function XMLCH_getMemberInfo($uid)
{
    global $_CONF_MLCH;
    static $info = array();
    if (!MAILCHIMP_ACTIVE) return $info;

    if (!isset($info[$uid])) {
        $info[$uid] = array();
        $email = MLCH_getEmail($uid);
        $params = array(
            'query' => $email,
            'id' => $_CONF_MLCH['def_list'],
            'emails' => array(
                array(
                    'email' => $email,
                ),
            ),
        );
        $api = Mailchimp\API::getInstance();
        $out = $api->call('lists/member-info', $params);
        if ($out['errors'] == 0) {
            $info[$uid] = $out['data'];
        }
    }
    return $info[$uid];
}


/**
 * Update the subscriber data in Mailchimp.
 * Used when the user changes their profile, and called from
 * services.inc.php if a plugin needs to update the Mailchimp info
 *
 * @param   integer $uid    glFusion user ID to update
 * @param   array   $params Parameter array to pass to Mailchimp API
 * @param   string  $email  Optional email, if email address is to be changed
 * @return  boolean     True on success, False on failure
 */
function MLCH_updateUser($uid, $params, $email='')
{
    global $_CONF_MLCH;

    if (!MAILCHIMP_ACTIVE) return true;

    if (!isset($params['id'])) $params['id'] = $_CONF_MLCH['def_list'];
    $list_id = $params['id'];
    $params['email_address'] = empty($email) ? MLCH_getEmail($uid) : $email;
    if (!isset($params['merge_fields'])) $params['merge_fields'] = array();
    // other parameters and merge_vars set by caller

    //USES_mailchimp_class_api();
    //$api = new Mailchimp\Mailchimp($_CONF_MLCH['api_key']);
    $api = Mailchimp\API::getInstance();
    $out = $api->updateMember($email, $list_id, $params);
    if (!$api->success()) {
        return false;
    }
    return true;
}


/**
 * Sync our MailChimp list with the local cache table.
 *
 * @return  string      Text output from the sync process
 */
function MLCH_syncCache()
{
    global $_TABLES, $LANG_MLCH, $_CONF_MLCH;

    //USES_mailchimp_class_api();
    $api = Mailchimp\API::getInstance();
    $list_id = $_CONF_MLCH['def_list'];
    $params = array(
        'id' => $_CONF_MLCH['def_list'],
        'opts' => array(
            'sort_field' => 'email',
            'sort_dir' => 'ASC',
            'limit' => 100,
            'start' => 0,
        ),
    );
    $lists = MLCH_getLists();
    $total_members = $lists[$list_id]['members'];
    $pages = ceil(($total_members / 100));
    $subscribers = array();
    $sql_values = array();
    $list_id = DB_escapeString($list_id);
    // Load up an array of subscribers that can be checked with isset()
    $offset = 0;
    for ($i = 0; $i < $pages; $i++) {
        $mc_status = $api->listMembers($list_id, 'subscribed', NULL, $offset, 100);
        if (!$api->success()) {
            return __FUNCTION__ . ":: Error requesting list information";
        }
        foreach ($mc_status['members'] as $d) {
            $subscribers[$d['email_address']] = 1;
        }
        $offset += 100;
    }

    // Now get all the site user accounts and see if their email
    // addresses are in the Mailchimp members array
    $sql = "SELECT u.uid,u.email,u.fullname
            FROM {$_TABLES['users']} u
            WHERE uid > 1
            ORDER BY u.email ASC";
    $r = DB_query($sql);
    $retval = '';
    while ($A = DB_fetchArray($r, false)) {
        $uid = (int)$A['uid'];
        if (isset($subscribers[$A['email']])) {
            // found a site member who's subscribed
            $sub = 1;
        } else {
            // member is not a subscriber,
            $sub = 0;
        }
        $sql_values[] = "('$uid', '$list_id', '$sub')";
    }

    // Empty the current cache table and add all the new entries
    DB_query("TRUNCATE {$_TABLES['mailchimp_cache']}");
    if (!empty($sql_values)) {
        $val_str = implode(',', $sql_values);
        DB_query("INSERT INTO {$_TABLES['mailchimp_cache']} VALUES $val_str");
    }
    $retval .= "Re-sync'd mailchimp cache";
    return $retval;
}


/**
 * See if a user is subscribed according to our cache data.
 * Intended to be called shortly after updating the user cache with
 * data from mailchimp, to avoid immediately subscribing a user who's
 * already subscribed.
 *
 * @param   integer $uid    User ID, default = current user
 * @param   string  $list   List ID, default = default list
 * @return  boolean         True if subscribed, False if not
 */
function MLCH_isSubscribed($uid=0, $list='')
{
    global $_TABLES, $_USER, $_CONF_MLCH;

    if (0 == $uid) $uid = $_USER['uid'];
    $uid = (int)$uid;
    if ('' == $list) $list = $_CONF_MLCH['def_list'];
    $list = DB_escapeString($list);

    $res = (int)DB_getItem($_TABLES['mailchimp_cache'], 'subscribed',
            "uid = $uid AND list = '$list'");
    return $res == 1 ? true : false;
}


/**
 * Debug function to log data to the error log.
 *
 * @param   string  $msg    Message to log
 * @param   string  $type   Message severity
 */
function MLCH_debug($msg, $type='debug')
{
    global $_CONF_MLCH;

    $types = array(
        'debug' => 1,
        'info'  => 2,
        'warn'  => 4,
        'crit'  => 8,
    );

    if (!isset($types[$type]) ||
        !isset($_CONF_MLCH[$type]) ||
        $_CONF_MLCH[$type] > $types[$type]) {
        return;
    }

    if (is_array($msg) && isset($msg['error'])) {
        // a mailchimp error message
        $msg = sprintf("API Error %s - %s", $msg['code'], $msg['error']);
    }
    COM_errorLog('Mailchimp ' . strtoupper($type) . ': ' . $msg);
}


/**
 * Log activity to a plugin-specific log file.
 *
 * @todo    available in lglib. Perhaps just a wrapper to pass along $pi_name,$msg.
 * @param string $logentry Text to log
 */
function MLCH_auditLog($logentry = '')
{
    global $_CONF, $_USER, $LANG01, $_CONF_MLCH;

    if ($logentry == '')
        return '';

    // A little sanitizing
    $logentry = str_replace(
            array('<?', '?>'),
            array('(@', '@)'),
            $logentry);
    $timestamp = strftime( '%c' );
    $logfile = $_CONF['path_log'] . $_CONF_MLCH['pi_name'] . '.log';

    // Can't open the log file?  Return an error
    if (!$file = fopen($logfile, 'a')) {
        COM_errorLog("Unable to open {$_CONF_MLCH['pi_name']}.log");
        return;
    }

    // Get the user name if it's not anonymous
    if (isset($_USER['uid'])) {
        $byuser = $_USER['uid'] . '-'.
                COM_getDisplayName($_USER['uid'],
                    $_USER['username'], $_USER['fullname']);
    } else {
        $byuser = 'anon';
    }
    $byuser .= '@' . $_SERVER['REMOTE_ADDR'];

    // Write the log entry to the file
    fputs($file, "$timestamp ($byuser) - $logentry\n");
    fclose($file);

}

require_once __DIR__ . '/services.inc.php';

?>
