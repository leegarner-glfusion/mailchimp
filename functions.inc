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
    $lang = 'english_utf-8';
    require_once __DIR__ . '/language/english_utf-8.php';
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
use Mailchimp\MergeFields;


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
    //$email = MLCH_syncWithMC($uid);
    $Sub = Mailchimp\Subscriber::getInstance($uid);
    $email = $Sub->syncWithMC();
    //$email = MLCH_syncWithMC($uid);
    if (isset($_POST['mailchimp_subscribe']) &&
        $_POST['mailchimp_subscribe'] == 1 &&
        !$Sub->isSubscribed()
    ) {
        //!MLCH_isSubscribed($uid) ) {
        //MLCH_subscribe($uid, $email);
        \Mailchimp\Subscriber::subscribe($uid, $email);
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
        //MLCH_unsubscribe($uid);
        \Mailchimp\Subscriber::unsubscribe($uid);
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
        //MLCH_syncWithMC($uid);
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
        $cnt = DB_count(
            $_TABLES['mailchimp_cache'],
            array('uid', 'listid', 'subscribed'),
            array($uid, DB_escapeString($_CONF_MLCH['def_list']), 1)
        );
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

    static $alreadyDone = NULL;
    if ($alreadyDone) {
        return;
    }
    $alreadyDone = true;

    if ($uid == $_USER['uid']) {
        // Logged-in user saving their profile, double opt-in not needed unless
        // configured to require it anyway
        $dbl_optin = $_CONF_MLCH['dbl_optin_members'] == 0 ? false : true;
    } else {
        // Administrator is updating the user record, double opt-in required
        $dbl_optin = true;
    }

    $U = Mailchimp\Subscriber::getInstance($uid);
    // Since this is calling an external API instead of just updating our
    // own database, make sure the subscription selection has actually changed
    if (
        isset($_POST['mailchimp_subscribe']) &&
        $_POST['mailchimp_oldsub'] == '0'
    ) {
        Mailchimp\Subscriber::subscribe($uid, $_POST['email'], '', $dbl_optin);
    } elseif (
        !isset($_POST['mailchimp_subscribe']) &&
        $_POST['mailchimp_oldsub'] == '1'
    ) {
        Mailchimp\Subscriber::unsubscribe($uid, $_POST['email']);
    }

    // Update the name merge fields if changed
    if ($_POST['fullname'] != $_POST['mailchimp_old_fullname']) {
        // Update Mailchimp from the new parameters.
        // Don't worry about the return value, nothing to be done if if fails.
        MergeFields::add('FNAME', LGLib\NameParser::F($_POST['fullname']));
        MergeFields::add('LNAME', LGLib\NameParser::L($_POST['fullname']));
    }
    $params = array();
    if (!empty(MergeFields::get())) {
        $params['merge_fields'] = MergeFields::get();
    }

    if (!empty($params)) {
        $U->updateMailchimp($params, $_POST['email']);
    }
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


/*function plugin_userchanged_mailchimp($uid)
{
    echo "here";die;
}*/


/**
 * Get information from other plugins when an item is saved.
 * This gets the mailing list merge fields and tags.
 * If a single string is returned for list_segment then the MEMSTATUS
 * merge field set. Otherwise any number of merge fields and tags is supported.
 *
 * @param   string  $id     Item ID
 * @param   string  $type   Item Type
 * @param   string  $old_id Original ID in case of ID change
 */
function plugin_itemsaved_mailchimp($id, $type, $old_id = '')
{
    $info = PLG_getItemInfo(
        $type,
        $id,
        'id,uid,merge_fields,email,fullname',
        0
    );
    if (!is_array($info) || !isset($info['id'])) {
        return;
    }
    $Sub = Mailchimp\Subscriber::getInstance($info['id']);
    $lists = $Sub->getSubscribed();
    if (empty($lists)) {
        // No lists to update
        return;
    }

    $tags = array();
    if (is_array($info) && isset($info['merge_fields'])) {
        if (is_array($info['merge_fields'])) {
            foreach ($info['merge_fields'] as $key=>$val) {
                MergeFields::add($key, $val);
            }
        }

        $merge_fields = MergeFields::get();
        if (!empty($merge_fields)) {
            $params = array(
                'merge_fields' => $merge_fields,
            );
        }
        foreach ($lists as $L) {
            $Sub->updateMailchimp($params, $info['email']);
        }
        /*if (!empty($tags)) {
            $Sub->updateTags($tags, $lists);
        }*/

    }
}


/**
 * Automatic uninstallation function for plugins.
 *
 * @return  array   Array of items to be removed
 */
function plugin_autouninstall_mailchimp()
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

    $Sub = Mailchimp\Subscriber::getInstance($_USER['uid']);
    if ($Sub->isSubscribed()) {
        return '';
    }

    $T = new Template(__DIR__ . '/templates/');
    $T->set_file('mailchimp_block', 'block_small.thtml');
    $T->set_var(array(
        'block_text' => sprintf($LANG_MLCH['block_text'], $_CONF['site_url']),
        'uid' => $_USER['uid'],
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
                    $members = $api->listMembers($list['id']);
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

require_once __DIR__ . '/services.inc.php';

?>
