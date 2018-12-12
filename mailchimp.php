<?php
/**
 * Table definitions and other static config variables.
 *
 * @author     Lee Garner <lee@leegarner.com>
 * @copyright  Copyright (c) 2012-2017 Lee Garner <lee@leegarner.com>
 * @package    mailchimp
 * @version    0.1.0
 * @license    http://opensource.org/licenses/gpl-2.0.php 
 *              GNU Public License v2 or later
 * @filesource
 */

/**
*   Global configuration array
*   @global array $_CONF_MLCH
*/
global $_CONF_MLCH;
$_CONF_MLCH['pi_name']            = 'mailchimp';
$_CONF_MLCH['pi_version']         = '0.1.0';
$_CONF_MLCH['gl_version']         = '1.4.0';
$_CONF_MLCH['pi_url']             = 'http://www.leegarner.com';
$_CONF_MLCH['pi_display_name']    = 'MailChimp';

/**
*   Global table name prefix
*   @global string $_DB_table_prefix
*/
global $_DB_table_prefix;
$_TABLES['mailchimp_cache']    = $_DB_table_prefix . 'mailchimp_cache';

?>
