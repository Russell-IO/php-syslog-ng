<?php

/*
 *
 * Developed by Clayton Dukes <cdukes@logzilla.pro>
 * Copyright (c) 2010 LogZilla, LLC
 * All rights reserved.
 *
 * Changelog:
 * 2010-01-13 - created
 *
 */
$basePath = dirname( __FILE__ );
require_once ($basePath . "/../common_funcs.php");
$dbLink = db_connect_syslog(DBADMIN, DBADMINPW);
session_start();
$text = get_input('txt');

// logmsg("$text");
$sql = "SELECT value FROM settings where name='PATH_BASE'";
$result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
if(num_rows($result)==0){
    echo "ERROR: Unable to determine installed path<br />Please check your database setting for PATH_BASE";
} else {
    $line = fetch_array($result);
    $path = $line[0];
    $text = preg_replace('/\'/', '',$text);
    $cmd = "echo '$text' | sudo $path/scripts/licadd.pl";
    exec($cmd, $out);
    if ($out[0] == 1) {
        $destination = $_SESSION['SITE_URL']."index.php";
        g_redirect($destination, "JS"); 
    } else {
        echo $out[0];
    }
}
?>
