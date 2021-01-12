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
use Mailchimp\Config;


/**
 * A standard representation of the interesting list info fields.
 * @package mailchimp
 */
class SubscriberInfo implements \ArrayAccess
{
    /** Properties array. Sent to the API as an array.
     * @var array */
    private $properties = array(
        'id' => '',
        'email_address' => '',
        'merge_fields' => array(),
    );
 

    /**
     * Initialize the parameters array.
     */
    public function __construct()
    {
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

}
