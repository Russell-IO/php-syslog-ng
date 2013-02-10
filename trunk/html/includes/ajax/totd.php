<?php

/*
 *
 * Developed by Clayton Dukes <cdukes@logzilla.pro>
 * Copyright (c) 2010 LogZilla, LLC
 * All rights reserved.
 * Last updated on 2010-06-15
 *
 * Changelog:
 * 2010-01-13 - created
 *
 */
$basePath = dirname( __FILE__ );
require_once ($basePath . "/../common_funcs.php");
$dbLink = db_connect_syslog(DBADMIN, DBADMINPW);
session_start();

$username = $_SESSION['username']; 
$action = get_input('action');

switch ($action) {
    case "get":
        $username = $_SESSION['username']; 
    $sql = "SELECT totd FROM users where username='$username'";
    $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
    $line = fetch_array($result);
    if ($line[0] == "show") {
        $count = get_total_rows('totd', $dbLink);
        $sql = "SELECT * FROM totd where lastshown<(SELECT NOW() - INTERVAL ".$_SESSION['TOOLTIP_REPEAT']." MINUTE) ORDER BY RAND() LIMIT 1";
        $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
        while ($line = fetch_array($result)) {
            $num = $line['tipnum'];
            $id = $line['id'];
            $name = $line['name'];
            $text = $line['text'];
        }
        if ($id) {
            echo "<i>Tip #$num of $count</i><br><b>$name:</b><br>$text";
            $sql = "UPDATE totd SET lastshown=NOW() WHERE id='".$id."'";
            perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
        }
    }
    break;
    case "disable":
        $username = $_SESSION['username']; 
    $sql = "UPDATE users SET totd='hide' WHERE username='$username'";
    $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
            if(mysql_affected_rows() == 1) {
                echo ucfirst($username)."'s tips have been permanently disabled";
            }
    break;
}
?>
