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
$action = get_input('action');
$name = get_input('name');


$url        = get_input('url'); // the page url
$form_type  = get_input('form_type'); // e.g.: text or text-area or select.
$id         = get_input('id'); // the id of the <td>
$orig_value = get_input('orig_value'); // original value
$new_value  = get_input('new_value'); // new value

//---use below to debug from the command line
// $name = (!empty($name)) ? $name : "ADMIN_EMAIL";
// $action = (!empty($action)) ? $action : "get";

switch ($action) {
    case "save":
        $sql = "UPDATE settings set value='$new_value' WHERE name='$name' AND value='$orig_value'";
        $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
        if ($result) {
            $sql = "SELECT name,value, type FROM settings WHERE name='$name'";
            $res = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
            while($row = fetch_array($res)) {
                if ($row['type'] == "int") {
                    $_SESSION[$row["name"]] = intval($row["value"]);
                } else {
                    $_SESSION[$row["name"]] = $row["value"];
                }
            }
            echo "<br />Sucessfully modified $name to $new_value<br>";
        } else {
            echo "Error" . mysql_error() . "<br />";
        }
        break;

    case "get":
        $sql = "SELECT * FROM settings WHERE name='$name'";
        $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
        while($row = fetch_array($result)) { 
            $data->name = $row['name'];
            $data->value = $row['value'];
            $data->type = $row['type'];
            $data->options = $row['options'];
            $data->def = $row['default'];
            $data->description = $row['description'];
        }
        echo json_encode($data);
        break;
}
?>
