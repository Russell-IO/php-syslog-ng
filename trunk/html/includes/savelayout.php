<?php
/*
 *
 * Developed by Clayton Dukes <cdukes@logzilla.pro>
 * Copyright (c) 2010 LogZilla, LLC
 * All rights reserved.
 * Last updated on 2010-06-15
 *
 * Changelog:
 * 2009-12-13 - created
 *

 */
session_start();
$basePath = dirname( __FILE__ );
require_once ($basePath . "/common_funcs.php");
$dbLink = db_connect_syslog(DBADMIN, DBADMINPW);

$varqty = count($_POST); // count how many portlets are we passing
$varnames = array_keys($_POST); // Obtain variable names
$varvalues = array_values($_POST);// Obtain variable values
for($i=0;$i<$varqty;$i++){  // For each variable
$semivalue = explode("|", $varvalues[$i]);  // Split variable when "|" is found and save it in $semivalue
$header = str_replace("portlet_", "", $varnames[$i]);
$header = str_replace("_", " ", $header);
$pagename = str_replace("tab-", "", $semivalue[0]);
$sql = ("UPDATE ui_layout SET col='$semivalue[1]', rowindex='$semivalue[2]' WHERE userid=(SELECT id FROM users WHERE username='".$_SESSION['username']."') AND pagename='$semivalue[0]' AND header='$header'");
$queryresult = perform_query($sql, $dbLink, $vars);
}
?>
