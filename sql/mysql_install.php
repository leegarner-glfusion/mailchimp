<?php
/**
*   Table definitions for the Mailchimp plugin
*   @author     Lee Garner <lee@leegarner.com>
*   @copyright  Copyright (c) 2013 Lee Garner <lee@leegarner.com>
*   @package    mailchimp
*   @version    0.0.1
*   @license    http://opensource.org/licenses/gpl-2.0.php 
*               GNU Public License v2 or later
*   @filesource
*/

$_SQL['mailchimp_cache'] = "CREATE TABLE {$_TABLES['mailchimp_cache']} (
  `uid` int(11) NOT NULL,
  `list` varchar(255) DEFAULT NULL,
  `subscribed` tinyint(1) unsigned default 1,
  PRIMARY KEY (`uid`, `list`)
)";

$_MLCH_UPGRADE_SQL = array(
'0.0.3' => array(
    "ALTER TABLE {$_TABLES['mailchimp_cache']}
        ADD subscribed tinyint(1) unsigned default 1",
    ),
    "DLETE FROM {$_TABLES['mailchimp_cache']} WHERE list=''",
);

?>
