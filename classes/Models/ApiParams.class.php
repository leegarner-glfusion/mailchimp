<?php
/**
 * Define parameters to send to Mailchimp.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2020 Lee Garner <lee@leegarner.com>
 * @package     mailchimp
 * @version     v0.2.0
 * @since       v0.2.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Mailchimp\Models;


/**
 * The state of a membership.
 * @package membership
 */
class ApiParams implements \ArrayAccess
{
    private $properties = array();
    //private $merge_fields = NULL;

    public function __construct()
    {
        $this->init();
    }


    /**
     * Reset the properties array to default values.
     *
     * @return  object  $this
     */
    public function init()
    {
        global $_CONF_MLCH;

        $this->properties = array(
            'id' => $_CONF_MLCH['def_list'],
            'email_type' => 'html',
            'status' => $_CONF_MLCH['dbl_optin_members'] ? 'pending' : 'subscribed',
            'double_optin' => true,
            'update_existing' => true,
            'merge_fields' => array(),
        );
        //$this->merge_fields = new MergeFields;
        return $this;
    }


    /**
     * Set the email address property.
     *
     * @param   string  $email  Email address
     * @return  object  $this
     */
    public function setEmail($email)
    {
        $this->properties['email_address'] = $email;
        return $this;
    }


    /**
     * Get the email address.
     *
     * @return  string  Email address, NULL if not set
     */
    public function getEmail()
    {
        if (isset($this->properties['email_address'])) {
            return $this->properties['email_address'];
        } else {
            return NULL;
        }
    }


    /**
     * Set the mailing list (audience) ID.
     *
     * @param   string  $listid Mailing list ID
     * @return  object  $this
     */
    public function setList($listid)
    {
        $this->properties['id'] = $listid;
        return $this;
    }


    /**
     * Get the mailing list ID.
     *
     * @return  string  Mailing list ID, NULL if not set
     */
    public function getList()
    {
        if (isset($this->properties['id'])) {
            return $this->properties['id'];
        } else {
            return NULL;
        }
    }


    /**
     * Set the double opt-in flag.
     *
     * @param   boolean $flag   True to require double opt-in, Falst if not
     * @return  object  $this
     */
    public function setDoubleOptin($flag)
    {
        $this->double_optin = $flag ? true : false;
        return $this;
    }


    /**
     * Set the email type, html or text.
     *
     * @param   string  $type   Email type
     * @return  object  $this
     */
    public function setEmailType($type)
    {
        $this->properties['email_type'] = $type == 'html' ? 'html' : 'text';
        return $this;
    }


    /**
     * Set the Update Existing Member flag.
     *
     * @param   boolean $flag   True to update existing data, False if not
     * @return  object  $this
     */
    public function setUpdateExisting($flag)
    {
        $this->update_existing = $flag ? true : false;
        return $this;
    }


    /**
     * Set the merge fields, if any.
     * Sets the merge_fields parameter, or removes it if no fields given.
     *
     * @param   array   $fields Array of name=>value pairs
     * @return  object  $this
     */
    public function setMergeFields(?array $fields)
    {
        if (!empty($fields)) {
            $this->properties['merge_fields'] = $fields;
        }
        return $this;
    }


    /**
     * Generic setter function for other parameters.
     *
     * @param   string  $key    Parameter name
     * @param   mixed   $value  Parameter value
     * @return  object  $this
     */
    public function set($key, $value)
    {
        if ($value === NULL) {
            unset($this->properties[$key]);
        } else {
            $this->properties[$key] = $value;
        }
        return $this;
    }


    /**
     * Get all the parameters to send to Mailchimp.
     *
     * @return  array       Properties array
     */
    public function get()
    {
        return $this->properties;
    }


    public function toArray()
    {
        if (empty($this->properties['merge_fields'])) {
            unset($this->properties['merge_fields']);
        }
        return $this->properties;
    }


    /**
     * Get the merge fields from plugins.
     * Each plugin returns an array of name=>value pairs.
     * Merge fields are added to the static array to be retrieved via
     * `self::get()`.
     *
     * @param   integer $uid    User ID
     */
    public function mergePlugins($uid)
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
                    $this->addMerge($name, $value);
                }
            }
        }
        return $this;
    }


    public function addMerge($key, $value)
    {
        if (!empty($value)) {
            $this->properties['merge_fields'][$key] = $value;
        }
        return $this;
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

/*    public function current() {}
    public function key() {}
    public function next() {}
    public function rewind() {}
        public function valid() {}*/

}
