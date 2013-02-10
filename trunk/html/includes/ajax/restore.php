<?php

/*
 *
 * Developed by Thomas Honzik (thomas@honzik.at)
 * Copyright (c) 2011 LogZilla, LLC
 * All rights reserved.
 *
 * Changelog:
 * 2011-03-09 - created
 *
 */
$basePath = dirname( __FILE__ );

require_once ($basePath . "/../common_funcs.php");
$rdate = get_input('restdate');
$rdate = substr($rdate,0,4).substr($rdate,5,2).substr($rdate,8,2);
$dbLink = db_connect_syslog(DBADMIN, DBADMINPW);
session_start();
$sql = "SELECT value FROM settings where name='PATH_BASE'";
$result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
if(num_rows($result)==0){
    echo "ERROR: Unable to determine installed path<br />Please check your database setting for PATH_BASE";
} else {
$line = fetch_array($result);
$path = $line[0];
action('Restoring archive '.$rdate);
$cmd = "sudo $path/scripts/dorestore.sh dumpfile_".$rdate.".txt";
exec($cmd, $out);
echo  $out[0];
echo "Restore started";
   }
?>
