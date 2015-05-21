/**
*   Sync our MailChimp list with the local cache table
*/
function MLCH_syncCache()
{
    global $_TABLES, $LANG_MLCH, $_CONF_MLCH;

    USES_mailchimp_class_api();
    $api       = new Mailchimp($_CONF_MLCH['api_key']);
    $params = array(
        'id' => $_CONF_MLCH['def_list'],
        'opts' => array(
            'sort_field' => 'email',
            'sort_dir' => 'ASC',
        ),
    );
    $mc_status = $api->call('lists/members', $params);
    if (isset($mc_status['error'])) {
        return $mc_status['error'];
    }

    // Load up an array of subscribers that can be checked with isset()
    $subscribers = array();
    foreach ($mc_status['data'] as $d) {
        $subscribers[$d['email']] = 1;
    }
    $sql = "SELECT u.uid,u.email,u.fullname
            FROM {$_TABLES['users']} u
            ORDER BY u.email ASC";
    $r = DB_query($sql);
    $retval = '';
    $values = array();
    $list_id = DB_escapeString($_CONF_MLCH['def_list']);
    while ($A = DB_fetchArray($r, false)) {
        $uid = (int)$A['uid'];
        if (isset($subscribers[$A['email']])) {
            // found a site member who's subscribed
            $sub = 1;
        } else {
            // member is not a subscriber,
            $sub = 0;
        }
        $values[] = "('$uid', '$list_id', '$sub')";
    }
    DB_query("TRUNCATE {$_TABLES['mailchimp_cache']}");
    $val_str = implode(',', $values);
    DB_query("INSERT INTO {$_TABLES['mailchimp_cache']} VALUES $val_str");
    $retval .= "Re-sync'd mailchimp cache";

    return $retval;
