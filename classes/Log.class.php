<?php
/**
 * Class to cache DB and web lookup results.
 *
 * @author      Lee Garner <lee@leegarner.com>
 * @copyright   Copyright (c) 2018 Lee Garner <lee@leegarner.com>
 * @package     mailchimp
 * @version     v0.1.0
 * @since       v0.1.0
 * @license     http://opensource.org/licenses/gpl-2.0.php
 *              GNU Public License v2 or later
 * @filesource
 */
namespace Mailchimp;


/**
 * Class for Mailchimp Cache.
 * @package mailchimp
 */
class Log
{
    /**
     * Write a log file entry to the specified file.
     *
     * @param   string  $logentry   Log text to be written
     * @param   string  $logfile    Log filename, 'mailchimp.log' by default
     */
    private static function write($logentry, $logfile='')
    {
        global $_CONF, $_USER, $LANG01, $_CONF_MLCH;

        if ($logentry == '') {
            return;
        }

        // A little sanitizing
        $logentry = str_replace(
            array('<'.'?', '?'.'>'),
            array('(@', '@)'),
            $logentry);
        $timestamp = strftime( '%c' );
        if ($logfile == '') {
            $logfile = $_CONF_MLCH['pi_name'] . '.log';
        }
        $logfile = $_CONF['path_log'] . $logfile;

        // Can't open the log file?  Return an error
        if (!$file = fopen($logfile, 'a')) {
            COM_errorLog("Unable to open $logfile");
            return;
        }

        // Get the user name if it's not anonymous
        if (isset($_USER['uid'])) {
            $byuser = $_USER['uid'] . '-'.
                COM_getDisplayName(
                    $_USER['uid'],
                    $_USER['username'], $_USER['fullname']
                );
        } else {
            $byuser = 'anon';
        }
        $byuser .= '@' . $_SERVER['REMOTE_ADDR'];

        // Write the log entry to the file
        fputs($file, "$timestamp ($byuser) - $logentry\n");
        fclose($file);
    }


    /**
     * Write an entry to the Audit log.
     *
     * @param   string  $msg        Message to log
     */
    public static function Audit($msg)
    {
        global $_CONF_MLCH;

        $logfile = $_CONF_MLCH['pi_name'] . '.log';
        self::write($msg, $logfile);
    }


    /**
     * Write an entry to the system log.
     * Just a wrapper for COM_errorLog().
     *
     * @param   string  $msg        Message to log
     */
    public static function System($msg)
    {
        COM_errorLog($msg);
    }

}

?>
