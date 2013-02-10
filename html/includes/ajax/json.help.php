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

$pname = get_input('pname');
// $pname = "Programs";

$sql = "SELECT description FROM help WHERE name='$pname'";
$result = perform_query($sql, $dbLink, "json.help.php");
$row = fetch_array($result);
echo $row[0];
?>
