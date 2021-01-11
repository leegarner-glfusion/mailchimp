<?php
/**
 * Table definitions and other static config variables.
 *
 * @author     Lee Garner <lee@leegarner.com>
 * @copyright  Copyright (c) 2012-2020 Lee Garner <lee@leegarner.com>
 * @package    mailchimp
 * @version    0.2.0
 * @license    http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

// Set versions

Mailchimp\Config::set('pi_version', '0.2.0');
Mailchimp\Config::set('gl_version', '1.7.8');

/**
 * Global table name prefix.
 * @global string $_DB_table_prefix
 */
global $_DB_table_prefix;
$_TABLES['mailchimp_cache']    = $_DB_table_prefix . 'mailchimp_cache';

?>
