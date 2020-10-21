<?php
/**
 * Class to handle setting up merge fields
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
class Mergefields
{
    /** Merge fields.
     * @var array */
    private static $fields = array();


    /**
     * Clear the merge fields array.
     */
    public static function clear()
    {
        self::$fields = array();
    }


    /**
     * Add a merge field name=>value.
     *
     * @param   string  $name   Field name
     * @param   string|array    $value  Field value
     */
    public static function add($name, $value)
    {
        self::$fields[$name] = $value;
    }


    /**
     * Get the merge fields array to provide to Mailchimp.
     *
     * @return  array   Array of merge field name=>value pairs
     */
    public static function get()
    {
        return self::$fields;
    }


    /**
     * Set the membership status merge field value.
     * This is for integration with the Membership plugin.
     *
     * @param   string  $status     Member status
     */
    public static function setMemStatus($status)
    {
        global $_CONF_MLCH;

        if (
            isset($_CONF_MLCH['mem_status_fldname']) &&
            !empty($_CONF_MLCH['mem_status_fldname'])
        ) {
            self::add($_CONF_MLCH['mem_status_fldname'], $status);
        }
    }

}

?>
