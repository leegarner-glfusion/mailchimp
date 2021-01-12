<?php
/**
 * Class to handle mailing list functions.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2020 Lee Garner <lee@leegarner.com>
 * @package     mailchimp
 * @version     v0.1.0
 * @since       v0.1.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Mailchimp;


/**
 * Mailing list information class.
 * @package mailchimp
 */
class MailingList
{
    /** Mailchimp list ID.
     * @var string */
    private $list_id = '';

    /** List name.
     * @var string */
    private $list_name = '';

    /** Number of subscribed members.
     * @var integer */
    private $member_count = 0;


    /**
     * Instantiate a list object populated with data from getAll().
     *
     * @param   array   $A      Array of list data from getAll()
     */
    public function __construct($A)
    {
        $this->list_id = $A['id'];
        $this->list_name = $A['name'];
        $this->member_count = (int)$A['members'];
    }


    private function _getApi()
    {
        static $api = NULL;
        if ($api === NULL) {
            switch (Config::get('provider')) {
            case 'mailchimp':
            case 'sendinblue':
                $cls = 'Mailchimp\\' . Config::get('provider') . '\\API';
                break;
            }
            $api = $cls::getInstance();
        }
        return $api;
    }


    /**
     * Get the list of Mailchimp lists.
     * Holds lists in a static variable to save API calls.
     *
     * @return  mixed   Array of lists, or false on failure
     */
    public static function getAll()
    {
        static $lists = null;

        if ($lists === null) {
            $lists = array();
            if (MAILCHIMP_ACTIVE && !empty(Config::get('api_key'))) {
                $api = self::_getApi();
                $list_data = $api->lists();
                if (is_array($list_data)) {
                    $lists = $list_data;
                    //foreach ($list_data as $key => $list) {
                        //$members = $api->listMembers($list['id']);
                    //    $lists[$list['id']] = new self($list->toArray());
                            /*'id' => $list['id'],
                            'name' => $list['name'],
                            //'members' => $members['total_items'],
                            'members' => $list['stats']['member_count'],
                        ) );*/
                    //}
                }
            }
        }
        return $lists;
    }


    /**
     * Get a single list. Calls getAll() and extracts the requested list.
     *
     * @param   string  $list_id    List ID
     * @return  object      MailingList object if found, Null if not
     */
    public static function getInstance($list_id=NULL)
    {
        if ($list_id === NULL) {
            $list_id = DB_escapeString(Config::get('def_list'));
        }
        $Lists = self::getAll();
        if (array_key_exists($list_id, $Lists)) {
            return $Lists[$list_id];
        } else {
            return NULL;
        }
    }


    /**
     * Get the number of members subscribed to the list.
     *
     * @return  integer     Member count
     */
    public function getMemberCount()
    {
        return (int)$this->member_count;
    }


    /**
     * Get the list ID.
     *
     * @return  string      List ID
     */
    public function getID()
    {
        return $this->list_id;
    }


    /**
     * Get the name of the list.
     *
     * @return  string      List name
     */
    public function getName()
    {
        return $this->list_name;
    }


    /**
     * Import our current users to our subscriber list.
     * Only imports users that are not in the cache table.
     * Updates the list segment from the Membership plugin if available.
     *
     * @return  string  Success or Error message
     */
    public function importUsers()
    {
        global $_TABLES, $LANG_MLCH;

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
        $list_id = DB_escapeString(Config::get('def_list'));
        $cache_vals = array();
        $api = Mailchimp\API::getInstance();
        while ($A = DB_fetchArray($result, false)) {
            $ApiParams = (new ApiParams)
                ->setEmail($A['email'])
                ->mergePlugins($A['uid']);
            $mc_status = $api->subscribe($A['email'], $ApiParams, $list_id);
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
     * Get the current subscribers to this mailing list.
     *
     * @return  array   Array of members
     */
    public function getSubscribers()
    {
        $members = array();
        $offset = 0;
        $perpage = 3;
        $pages = ceil(($this->getMemberCount() / $perpage));
        $subscribers = array();
        // Load up an array of subscribers that can be checked with isset()
        $api = self::_getApi();
        for ($i = 0; $i < $pages; $i++) {
            $opts = array(
                'offset' => $offset,
                'count' => $perpage,
            );
            $response = $api->listMembers($this->getID(), $opts);
            /*if (!$api->success()) {
                return __FUNCTION__ . ":: Error requesting list information";
            }*/
            foreach ($response as $d) {
                $members[$d['id']] = array(
                    'id' => $d['id'],
                    'email_address' => $d['email_address'],
                    'email_type'    => $d['email_type'],
                    'status'        => $d['status'],
                    'merge_fields'  => $d['merge_fields'],
                );
            }
            $offset += $perpage;
        }

        return $members;
    }

}
