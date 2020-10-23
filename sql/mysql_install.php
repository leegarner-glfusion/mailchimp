<?php
/**
 * Table definitions for the Mailchimp plugin
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2013-2020 Lee Garner <lee@leegarner.com>
 * @package     mailchimp
 * @version     v0.1.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */

$_SQL['mailchimp_cache'] = "CREATE TABLE {$_TABLES['mailchimp_cache']} (
  `uid` int(11) NOT NULL,
  `listid` varchar(255) NOT NULL,
  `subscribed` tinyint(1) unsigned default 1,
  PRIMARY KEY (`uid`, `listid`)
)";

$_MLCH_UPGRADE_SQL = array(
'0.0.3' => array(
    "ALTER TABLE {$_TABLES['mailchimp_cache']}
        ADD subscribed tinyint(1) unsigned default 1",
    "DELETE FROM {$_TABLES['mailchimp_cache']} WHERE list=''",
),
'0.1.0' => array(
    "ALTER TABLE {$_TABLES['mailchimp_cache']}
        CHANGE list listid varchar(20) not null default ''",
    ),
);

?>
