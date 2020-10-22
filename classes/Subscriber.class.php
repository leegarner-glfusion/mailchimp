<?php
/**
 * Class to handle mailing list subscribers.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2018-2020 Lee Garner <lee@leegarner.com>
 * @package     mailchimp
 * @version     v0.1.0
 * @since       v0.1.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Mailchimp;


/**
 * Subscriber information class.
 * Gets the user information from the User table, and subscribed
 * lists from the cache table.
 * @package mailchimp
 */
class Subscriber
{
    /** glFusion user ID.
     * @var integer */
    private $uid = 0;

    /** glFusion user name. Used if fullname is empaty.
     * @var string */
    private $username = '';

    /** User's full name.
     * @var string */
    private $fullname = '';

    /** User's email address.
     * @var string */
    private $email = '';

    /** User's first name, for Mailchimp merge variables.
     * @var string */
    private $firstname = NULL;

    /** User's last name, for Mailchimp merge variables.
     * @var string */
    private $lastname = NULL;

    /** Lists retrieved from the cache table.
     * @var array */
    private $lists = array();


    /**
     * Constructor.
     * Creates a user object for the requested user ID, or
     * for the current user if no $uid specified.
     *
     * @param   integer $uid    Optional user ID
     */
    public function __construct($uid=0)
    {
        global $_USER;

        if ($uid == 0) $uid = $_USER['uid'];
        $this->uid = (int)$uid;
        if ($uid > 1) {
            $this->Read();
        }
    }


    /**
     * Get an instance of a user record.
     *
     * @param   integer $uid    User ID
     * @param   string  $email  Email, required if $uid is 1
     * @return  object          User object
     */
    public static function getInstance($uid, $email='')
    {
        global $_USERS;

        if ($uid > 1) {
            $cache_key = 'uid_' . $uid;
            $obj = Cache::get($cache_key);
            if ($obj === NULL) {
                $obj = new self($uid);
                Cache::set($cache_key, $obj, 'users');
            }
        } else {
            $obj = new self;
        }
        return $obj;
    }


    /**
     * Set the email address for the subscriber if different.
     *
     * @param   string  $email  Email address
     * @return  object  $this
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }


    /**
     * Get the user's email address.
     *
     * @return  string      Email address
     */
    public function getEmail()
    {
        return $this->email;
    }


    /**
     * Get the user's full name.
     *
     * @return  string      Full name
     */
    public function getFullname()
    {
        return $this->fullname;
    }


    /**
     * Sets the user variables from the $info array.
     * Could be from a database record or form variables.
     *
     * @param   array $info Array containing user info fields
     */
    public function setVars($A)
    {
        $this->uid = (int)$A['uid'];
        $this->listid = $A['listid'];
        $this->subscribed = (int)$A['subscribed'];
        if ($this->fullname == '') $this->fullname = $this->username;
    }


    /**
     * Reads from the database and calls setVars() to set up the variables.
     *
     * @param   integer $uid    User ID to read
     */
    public function Read()
    {
        global $_TABLES, $_USER;

        $sql = "SELECT * from {$_TABLES['mailchimp_cache']}
                WHERE uid = $this->uid";
        $res = DB_query($sql);
        if ($res) {
            while ($A = DB_fetchArray($res, false)) {
                $this->lists[$A['listid']] = $A['subscribed'];
            }
        }
        if (!empty($A)) {
            $this->setVars($A);
        }

        // Get the user name from the array if it is the current user,
        // otherwise look in the DB.
        if ($this->uid == $_USER['uid']) {
            $this->username = $_USER['username'];
            $this->fullname = $_USER['fullname'];
        } else {
            $sql = "SELECT username, fullname, email FROM {$_TABLES['users']}
                WHERE uid = {$this->uid}";
            $res = DB_query($sql);
            if ($res) {
                $U = DB_fetchArray($res, false);
                $this->username = $U['username'];
                $this->fullname = $U['fullname'];
                $this->email = $U['email'];
            }
        }
    }


    /**
     * Gets the user's last name.
     * Assumes that everything after the first space in the fullname
     * is the last name.
     * Also sets the local lastname variable for future use.
     *
     * @return  string      Last Name
     */
    public function getLastName()
    {
        // If we haven't cached the last name, then calculate it
        if ($this->lastname === NULL) {
            //$this->lastname = \LGLib\NameParser::L($this->fullname);
            $status = PLG_invokeService('lglib', 'parseName',
                array(
                    'format' => 'L',
                    'name' => $this->fullname,
                ),
                $output, $svc_msg
            );
            if ($status == PLG_RET_OK) $this->lastname = $output;
            if ($this->lastname == '') {    // still not found?
                $this->lastname = $this->fullname;
                if ($this->lastname == '') {    // still not found?
                    $this->lastname = $this->username;
                }
            }
        }
        return $this->lastname;
    }


    /**
     * Gets the user's first name.
     * Also sets the local variable for future use.
     *
     * @return  string      First Name
     */
    public function getFirstName()
    {
        // If we haven't cached the last name, then calculate it
        if ($this->firstname === NULL) {
            //$this->lastname = \LGLib\NameParser::L($this->fullname);
            $status = PLG_invokeService('lglib', 'parseName',
                array(
                    'format' => 'F',
                    'name' => $this->fullname,
                ),
                $output, $svc_msg
            );
            if ($status == PLG_RET_OK) {
                $this->lastname = $output;
            }
            if ($this->firstname == '') {    // still not found?
                $this->firstname = $this->fullname;
                if ($this->firstname == '') {    // still not found?
                    $this->firstname = $this->username;
                }
            }
        }
        return $this->firstname;
    }


    /**
     * Get all the subscribed list IDs.
     *
     * @return  array   Array of list IDs.
     */
    public function getSubscribed()
    {
        $retval = array();
        foreach ($this->lists as $id=>$subscribed) {
            if ($subscribed) $retval[] = $id;
        }
        return $retval;
    }


    /**
     * Update user information at Mailchimp.
     *
     * @param   araay   $params     Parameters to update
     * @param   string  $email      Optional email, for anonymous users
     * @return  boolean     True on success, False on failure
     */
    public function updateMailchimp($params, $email='')
    {
        global $_CONF_MLCH;

        // If the Mailchimp API is not available, just return OK
        if (!MAILCHIMP_ACTIVE) {
            return true;
        }

        // Make sure the list ID is in the parameters.
        if (!isset($params['id'])) {
            $params['id'] = $_CONF_MLCH['def_list'];
        }
        $list_id = $params['id'];
        if (empty($email)) {
            $params['email_address'] = $this->getEmail();
        } else {
            $params['email_address'] = $email;
        }
        if (!isset($params['merge_fields'])) {
            // Make sure there's something for the merge parameters.
            $params['merge_fields'] = array();
        }
        // other parameters and merge_vars set by caller

        //USES_mailchimp_class_api();
        //$api = new Mailchimp\Mailchimp($_CONF_MLCH['api_key']);
        $api = API::getInstance();
        $out = $api->updateMember($email, $list_id, $params);
        if (!$api->success()) {
            return false;
        }
        return true;
    }


    /**
     * Update the tags for a subscriber.
     *
     * @param   array   $tags   Array of tagname=>tagstatus values
     * @param   array|string    $lists  List IDs to update
     * @return  boolean     Status of API::updateTags()
     */
    public function updateTags($tags, $lists='')
    {
        $vars = array();
        foreach ($tags as $name=>$status) {
            $vars[] = array(
                'name' => $name,
                'status' => $status,
            );
        }
        if (empty($vars)) {
            return;
        }
        $api = API::getInstance();
        return $api->updateTags($this->email, $vars, $lists);
    }


    /**
     * See if a user is subscribed according to our cache data.
     * Intended to be called shortly after updating the user cache with
     * data from mailchimp, to avoid immediately subscribing a user who's
     * already subscribed.
     *
     * @param   string  $list   List ID, default = default list
     * @return  boolean         True if subscribed, False if not
     */
    public function isSubscribed($list='')
    {
        global $_CONF_MLCH;

        if ('' == $list) $list = $_CONF_MLCH['def_list'];
        if (isset($this->lists[$list]) && $this->lists[$list] == 1) {
            return true;
        } else {
            return false;
        }
    }


    /**
     * Get the user ID given an email address.
     *
     * @param   string  $email  Email address
     * @return  integer $uid    User ID, zero if not found
     */
    public static function getUid($email)
    {
        global $_TABLES, $_USER;

        if (isset($_USER['email']) && $email == $_USER['email']) {
            return (int)$_USER['uid'];
        }
        return (int)DB_getItem(
            $_TABLES['users'],
            'uid',
            "email='" . DB_escapeString($email) . "'"
        );
    }


    /**
     * Subscribe an address to a list.
     * For a registered user, takes the user ID and can get the email address
     * from the user record. For anonymous uses, set uid to 0 or 1 and $email
     * is then required.
     *
     * The cache table is updated by the Webhook function after confirmation is
     * received.
     *
     * @param   integer $uid    User ID being subscribed. 0 or 1 for anon
     * @param   string  $email  Email address. Taken from user if empty and uid > 1
     * @param   string  $list   List to subscribe, default = $_CONF_MLCH['def_list']
     * @param   boolean $dbl_opt    True (default) to require double-opt-in
     * @return  boolean     True on success, False on failure
     */
    public static function subscribe($uid, $email='', $list = '', $dbl_opt=true)
    {
        global $_CONF_MLCH, $LANG_MLCH, $_USER, $_TABLES;

        // if no api key, don't try anything
        if (!MAILCHIMP_ACTIVE) {
            Logger::System('Mailchimp is not active. API Key entered?');
            return false;
        }

        // Mailchimp list choice. Not yet implemented.
        if (empty($list) && !empty($_CONF_MLCH['def_list'])) {
            $list = $_CONF_MLCH['def_list'];
        }
        if (empty($list)) {
            return false;
        }

        MergeFields::clear();
        if ($dbl_opt !== false) {
            $dbl_opt = true;
        }
        $uid = (int)$uid;

        // Try to get the user ID from the email address if not given
        if ($uid == 0) {
            $uid = self::getUid($email);
        }

        $fname = '';
        $lname = '';
        // Get the first and last name merge values.
        if ($uid > 1) {
            $U = self::getInstance($uid);
            if (!empty($email)) {
                $U->setEmail($email);
            } else {
                $email = $U->getEmail();
            }

            // Parse the member name into first and last
            $fname = PLG_callFunctionForOnePlugin(
                'plugin_parseName_lglib',
                array(
                    1 => $U->getFullname(),
                    2 => 'F',
                )
            );
            $lname = PLG_callFunctionForOnePlugin(
                'plugin_parseName_lglib',
                array(
                    1 => $U->getFullname(),
                    2 => 'L',
                )
            );

            // only update if there is data (might be values in the list not in
            // the local db)
            if (!empty($fname)) MergeFields::add('FNAME', $fname);
            if (!empty($lname)) MergeFields::add('LNAME', $lname);
            // Get the membership status of this subscriber from the Membership
            // plugin, if available. This goes into the merge_vars to segment the
            // list by current, former and nonmember status
            $status = LGLIB_invokeService(
                'membership', 'mailingSegment',
                array(
                    'uid' => $uid,
                    'email' => $email,
                ),
                $segment,
                $msg
            );
            if ($status == PLG_RET_OK) {
                MergeFields::setMemStatus($segment);    // always update
            }
        }
        if (empty($email)) return false;    // Can't have an empty email address

        // Process Subscription
        $api = API::getInstance();
        $params = array(
            'id' => $list,
            'email_address' => $email,
            'merge_fields' => MergeFields::get(),
            'email_type' => 'html',
            'status' => $_CONF_MLCH['dbl_optin_members'] ? 'pending' : 'subscribed',
            'double_optin' => $dbl_opt,
            'update_existing' => true,
        );
        $mc_status = $api->subscribe($email, $params, $list);
        if (!$api->success()) {
            Logger::Audit("Failed to subscribe $email to $list. Error: " .
                $api->getLastError(), true);
            $retval = false;
        } else {
            if ($uid > 1) {
                // already instantiated above
                $U->updateCache();
            }
            $msg = $dbl_opt ? $LANG_MLCH['dbl_optin_required'] :
                $LANG_MLCH['no_dbl_optin'];
            Logger::Audit("Subscribed $email to $list." . $msg);
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
    public static function unsubscribe($uid, $email='', $list = '')
    {
        global $_CONF_MLCH, $_TABLES;

        // if no api key, do nothing
        if (!MAILCHIMP_ACTIVE) {
            Logger::System('Mailchimp is not active. API Key entered?');
            return false;
        }

        if (empty($list)) $list = $_CONF_MLCH['def_list'];
        if ($uid > 1 && empty($email)) {
            $email = self::getInstance($uid)->getEmail();
        }
        if (empty($list)) $list = $_CONF_MLCH['def_list'];
        if (empty($email) || empty($list)) return false;

        $api = API::getInstance();
        $mc_status = $api->unsubscribe($email, $list);
        if ($api->success()) {
            // List_NotSubscribed error is ok
            Logger::Audit("Unsubscribed $email from $list");
            // Delete records from cache.
            $sql = "INSERT INTO {$_TABLES['mailchimp_cache']} SET
                    uid = $uid, listid = '$list', subscribed = 0
                ON DUPLICATE KEY UPDATE
                    subscribed=0";
            DB_query($sql, 1);
            $retval = true;
        } else {
            Logger::Audit("Failed to unsubscribe $email from $list. Error" .
                $api->getLastError());
            $retval = false;
        }
        return $retval;
    }


    /**
     * Update the cache table for this user.
     *
     * @param   boolean $clear  True to clear all entries before updating
     * @param   string  $listid Mailing list ID to update
     */
    public function updateCache($clear=false, $listid='')
    {
        global $_USER, $_CONF_MLCH, $_TABLES;

        if (empty($listid)) {
            $listid = $_CONF_MLCH['def_list'];
            if (empty($listid)) {
                return;   // still empty, there's no list to check
            }
        }

        // Delete existing entries if requested
        if ($clear) {
            DB_query("UPDATE {$_TABLES['mailchimp_cache']}
                SET subscribed = 0
                WHERE uid = {$this->uid}");
        }

        //foreach ($this->lists as $list) {
            $sql = "INSERT INTO {$_TABLES['mailchimp_cache']} SET
                    uid = {$this->uid},
                    listid = '" . DB_escapeString($listid) . "',
                    subscribed = 1
                ON DUPLICATE KEY UPDATE
                    subscribed = 1";
//echo $sql;die;
        DB_query($sql);      // Dup errors can be normal
        //}
    }


    /**
     * Sync our MailChimp list with the local cache table.
     *
     * @return  string      Text output from the sync process
     */
    public static function syncAllFromMailchimp()
    {
        global $_TABLES, $LANG_MLCH, $_CONF_MLCH;

        $api = API::getInstance();
        $list_id = $_CONF_MLCH['def_list'];

        $List = MailingList::getInstance($list_id);
        if ($List === NULL) {
            return 'Unable to retrieve list information.';
        }

        $offset = 0;
        $pages = ceil(($List->getMemberCount() / 100));
        $subscribers = array();
        $sql_values = array();
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
        $list_id = DB_escapeString($list_id);
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
        $retval .= "Re-sync&apos;d mailchimp cache";
        return $retval;
    }


    /**
     * Synchronize the local cache table with Mailchimp's for a single user.
     * First, delete all list entries and then add all the lists that the
     * member is subscribed to. This is quicker than trying to determine which
     * lists are not in the subscribed group.
     *
     * @param   integer $uid    User ID
     * @return  string          User Email address
     */
    public function syncWithMC()
    {
        global $_TABLES, $_CONF_MLCH;

        if ($this->uid < 2) return '';    // nothing to do for anon users

        $api = API::getInstance();
        $out = $api->listsForEmail($email);
        if (is_array($out) && !isset($out['error'])) {
            $lists = array();
            foreach ($out as $list_info) {
                if (isset($list_info['id'])) {
                    $lists[] = $list_info['id'];
                }
            }
            $this->updateCache(true);
        }
        return $this->email;
    }

}   // class Subscriber

?>
