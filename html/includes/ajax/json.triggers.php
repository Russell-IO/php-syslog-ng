<?php
/*
 *
 * Developed by Clayton Dukes <cdukes@logzilla.pro>
 * Copyright (c) 2010 LogZilla, LLC
 * All rights reserved.
 * Last updated on 2010-12-30
 *
 * Changelog:
 * 2010-12-31 - created
 *
 */

$basePath = dirname( __FILE__ );
require_once ($basePath . "/../common_funcs.php");
$dbLink = db_connect_syslog(DBADMIN, DBADMINPW);
$dbid = get_input('dbid');
$description = get_input('description');
$pattern = get_input('pattern');
$mailto = get_input('mailto');
$mailfrom = get_input('mailfrom');
$subject = get_input('subject');
$body = get_input('body');
$disabled = get_input('disabled');
$action = get_input('action');

//---use below to debug from the command line
// $dbid = (!empty($dbid)) ? $dbid : "1";
// $action = (!empty($action)) ? $action : "get";


switch ($action) {
    case "save":
        $d = mysql_real_escape_string($description);
        $p = mysql_real_escape_string($pattern);
        $t = mysql_real_escape_string($mailto);
        $f = mysql_real_escape_string($mailfrom);
        $s = mysql_real_escape_string($subject);
        $b = mysql_real_escape_string($body);
        $di = mysql_real_escape_string($disabled);
        $sql = "UPDATE triggers set description='$d', pattern='$p', mailto='$t', mailfrom='$f', subject='$s', body='$b', disabled='$di' WHERE id='$dbid'";
        // $sql = "UPDATE triggers set mailto='$t' WHERE id='$dbid'";
    $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
    if ($result) {
        echo "Trigger Updated";
    } else {
        echo "Failed to save Trigger";
    }
    break;

    case "get":
        $sql = "SELECT * FROM triggers WHERE id IN ('$dbid')";
    $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
    while($row = fetch_array($result)) { 
        $data->description = stripslashes($row['description']);
        $data->pattern = stripslashes($row['pattern']);
        $data->mailto = stripslashes($row['mailto']);
        $data->mailfrom = stripslashes($row['mailfrom']);
        $data->subject = stripslashes($row['subject']);
        $data->body = stripslashes($row['body']);
        $data->disabled = stripslashes($row['disabled']);
    }
    echo json_encode($data);
    break;
}
?>
