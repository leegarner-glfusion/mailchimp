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


    /**
     * Get the list of Mailchimp lists.
     * Holds lists in a static variable to save API calls.
     *
     * @return  mixed   Array of lists, or false on failure
     */
    public static function getAll()
    {
        global $_CONF_MLCH;
        static $lists = null;

        if ($lists === null) {
            $lists = array();
            if (MAILCHIMP_ACTIVE && !empty($_CONF_MLCH['api_key'])) {
                $api = API::getInstance();
                $list_data = $api->lists();
                if (is_array($list_data)) {
                    foreach ($list_data['lists'] as $key => $list) {
                        $members = $api->listMembers($list['id']);
                        $lists[$list['id']] = new self(array(
                            'id' => $list['id'],
                            'name' => $list['name'],
                            'members' => $members['total_items'],
                        ) );
                    }
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
    public static function getInstance($list_id)
    {
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

}

?>
