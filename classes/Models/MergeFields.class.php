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
namespace Mailchimp\Models;


/**
 * Mailing list information class.
 * @package mailchimp
 */
class MergeFields implements \ArrayAccess
{
    /** Merge fields.
     * @var array */
    private $properties = array();


    /**
     * Set a merge field name=>value. Fluid interface method.
     *
     * @param   string  $name   Field name
     * @param   string|array    $value  Field value
     * @return  this
     */
    public function set($name, $value)
    {
        $this->offsetSet($name, $value);
        return $this;
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
echo "DEPRECATED"; die;
        if (
            isset($_CONF_MLCH['mem_status_fldname']) &&
            !empty($_CONF_MLCH['mem_status_fldname'])
        ) {
            self::add($_CONF_MLCH['mem_status_fldname'], $status);
        }
    }


    /**
     * Get the merge fields from plugins.
     * Each plugin returns an array of name=>value pairs.
     * Merge fields are added to the static array to be retrieved via
     * `self::get()`.
     *
     * @param   integer $uid    User ID
     */
    public function getPlugins($uid)
    {
        /*
         * Testing PR#408 to return values from PLG_callFunctionForAllPlugins().
        foreach (
            PLG_callFunctionForAllPlugins('getMergeFields', array(1=>$uid))
            as $pi_name=>$data) {
            if (is_array($data)) {
                foreach ($data as $name=>$value) {
                    $this->set($name, $value);
                }
            }
        }
        return;*/

        global $_PLUGINS;

        foreach ($_PLUGINS as $pi_name) {
            $output = PLG_callFunctionForOnePlugin(
                'plugin_getMergeFields_' . $pi_name,
                array(1 => $uid)
            );
            if (is_array($output)) {
                foreach ($output as $name=>$value) {
                    $this->set($name, $value);
                }
            }
        }
    }

    public function offsetSet($key, $value)
    {
        if ($value === NULL) {
            unset($this->properties[$key]);
        } else {
            $this->properties[$key] = $value;
        }
    }

    public function offsetExists($key)
    {
        return isset($this->properties[$key]);
    }

    public function offsetUnset($key)
    {
        unset($this->properties[$key]);
    }

    public function offsetGet($key)
    {
        return isset($this->properties[$key]) ? $this->properties[$key] : null;
    }


    public function toArray()
    {
        return $this->properties;
    }

}

?>
