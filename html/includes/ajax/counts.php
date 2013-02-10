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

$data = get_input('data');

// DEBUG below - remove in production!
//   $data = "msgs";

switch ($data) {
    case "mps":
        $sql = "SELECT AVG(value) FROM cache where name like 'chart_mps_%' AND updatetime >= NOW() - INTERVAL 59 SECOND";
    break;

    case "msgs":
        $sql = "SELECT value FROM cache where name='msg_sum'";
        // $spx_sql = "select * from distributed limit 1";
    break;

    case "notes":
        $sql = "SELECT COUNT(*) FROM $_SESSION[TBL_MAIN] WHERE notes!=''";
    break;

    case "prgs":
        $sql = "SELECT COUNT(*) FROM (SELECT DISTINCT name FROM programs) AS result";
    break;

    case "mnes":
        $sql = "SELECT COUNT(*) FROM (SELECT DISTINCT name FROM mne) AS result";
    break;

    case "sevs":
        $sql = "SELECT COUNT(*) FROM (SELECT DISTINCT severity FROM ".$_SESSION['TBL_MAIN'] .") AS result";
    break;

    case "facs":
        $sql = "SELECT COUNT(*) FROM (SELECT DISTINCT facility FROM ".$_SESSION['TBL_MAIN'] .") AS result";
    break;
}
 if ($spx_sql) {
     $array = spx_query($spx_sql);
     // print_r($array);
     if (is_array($array)) {
     $total = $array[2][1];
     echo(intval($total));
     } else {
         echo "$array";
     }
 } else {
    $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
    if(num_rows($result)==0){
        // echo "ERROR in ajax/counts.php";
        echo 0;
    } else {
        $line = fetch_array($result);
        if ($data == "mps") {
            $seconds = date_offset_get(new DateTime);
            $offset = $seconds / 3600;
            $x = ((time() + $seconds) * 1000);
            $y = round($line[0], 0);
            $ret = array($x, $y);
            echo json_encode($ret);
        } else {
            echo round($line[0], 0);
        }
   }
}
?>
