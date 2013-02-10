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

session_start();
$basePath = dirname( __FILE__ );
require_once ($basePath . "/../common_funcs.php");
$dbLink = db_connect_syslog(DBADMIN, DBADMINPW);
$userid = get_input('userid');
$rbac_key = get_input('rbac_key');
$action = get_input('action');

//---use below to debug from the command line
// $userid = (!empty($userid)) ? $userid : "1";
// $action = (!empty($action)) ? $action : "get";
?>

<?php

switch ($action) {
    case "save":
        $sql = "UPDATE users set rbac_key='$rbac_key' WHERE id='$userid'";
    $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
    if ($result) {
        $_SESSION["$rbac_key"] = $rbac_key;
	echo "writing new key";
    } else {
        echo "Failed to save";
    }
    break;

    case "get":
    $sql = "SELECT id, rbac_key FROM users WHERE id = $userid";

    $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
    while($row = fetch_array($result)) { 
        $data->userid = $row['id'];
        $data->rbac_key = $row['rbac_key'];
    }

    echo json_encode($data);
    break;
}
?>
