<?php
/*
 *
 * Developed by Clayton Dukes <cdukes@logzilla.pro>
 * Copyright (c) 2010 LogZilla, LLC
 * All rights reserved.
 * Last updated on 2010-06-15
 *
 * Changelog:
 * 2010-03-01 - created
 *
 */

$basePath = dirname( __FILE__ );
require_once ($basePath . "/../common_funcs.php");
$dbLink = db_connect_syslog(DBADMIN, DBADMINPW);
$dbid = get_input('dbid');
$note = get_input('note');
$sup_date = get_input('sup_date');
$sup_time = get_input('sup_time');
$sup_field = get_input('sup_field');
$msg_text = get_input('sup_msg');
$msg_text = mysql_real_escape_string($msg_text);
$action = get_input('action');

if ($sup_field) {
    if ($sup_field == 'this single event') {
        $where = "WHERE id IN ('$dbid')";
        $success .= "Set event suppression for record #$dbid ";
    } else {
        $sql = "SELECT $sup_field FROM ".$_SESSION["TBL_MAIN"]." WHERE id IN ('$dbid')";
        $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
        $line = fetch_array($result);
        $column = $line[0];
        if ($sup_field == "msg") {
            $where = "WHERE $sup_field LIKE '%$msg_text%'";
        } else {
            $where = "WHERE $sup_field='$column'";
        }
        if ($sup_field == "msg") {
            $sql = "REPLACE INTO suppress (name,col,expire) VALUES ('$msg_text','$sup_field','$sup_date $sup_time')";
        } else {
            $sql = "REPLACE INTO suppress (name,col,expire) VALUES ('$column','$sup_field','$sup_date $sup_time')";
        }
        $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
        switch ($sup_field) {
            case 'mne':
                $column = crc2mne($column);
                break;
            case 'facility':
                $column = int2fac($column);
                break;
            case 'severity':
                $column = int2sev($column);
                break;
            case 'program':
                $column = crc2prg($column);
                break;
        }
        if ($sup_field == "msg") {
            $success .= "Set event suppression for $sup_field to '$msg_text'<br>";
        } else {
            $success .= "Set event suppression for $sup_field to '$column'<br>";
        }
    }
} else {
    $where = "WHERE id IN ('$dbid')";
}
switch ($action) {
    case "save":
        if ($sup_field) {
            $sql = "UPDATE ".$_SESSION["TBL_MAIN"]." set suppress='$sup_date $sup_time' $where";
            $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
                $success .= "until $sup_date $sup_time<br>";
        }
    $sql = "UPDATE ".$_SESSION["TBL_MAIN"]." set notes='$note' WHERE id IN ('$dbid')";
    $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
    if ($note) {
        $success .= "Updated note to:<br>$note";
    } else {
        $success .= " and clear the note<br>";
    }
    if ($success) {
        echo $success;
    } else {
        echo "No update needed";
    }
    break;

    case "get":
        $sql = "SELECT notes FROM $_SESSION[TBL_MAIN] WHERE id IN ('$dbid')";
    $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
    while($row = fetch_array($result)) { 
        echo $row['notes'];
    }
    break;
}
?>
