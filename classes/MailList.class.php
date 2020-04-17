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
 * Subscriber information class.
 * Gets the user information from the User table, and subscribed
 * lists from the cache table.
 * @package mailchimp
 */
class MailList
{

    private $list_id = '';
    private $list_name = '';
    private $member_count = 0;

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
                //USES_mailchimp_class_api();
                $api = API::getInstance();
                $list_data = $api->lists();
                if (is_array($list_data)) {
                    foreach ($list_data['lists'] as $key => $list) {
                        $members = $api->listMembers($list['id'], 'subscribed', NULL, 0, 0);
                        //$lists[$list['id']] = array(
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


    public static function getInstance($list_id)
    {
        $Lists = self::getAll();
        if (array_key_exists($list_id, $Lists)) {
            return $Lists[$list_id];
        } else {
            return NULL;
        }
    }


    public function getMemberCount()
    {
        return (int)$this->member_count;
    }


    public function getID()
    {
        return $this->list_id;
    }


    public function getName()
    {
        return $this->list_name;
    }

}

?>
