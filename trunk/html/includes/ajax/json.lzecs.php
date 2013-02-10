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
require_once "$basePath/../LZECS.class.php";
$cemdb = new LZECS($dbLink);

$dbid = get_input('dbid');
$action = get_input('action');
$msg = get_input('msg');
 // $msg = "NMS_Replay[4886]: %SYS-5-CONFIG_I: Configured from memory by console (Q1 2009 C U)";
 // $action = "get";
// $msg = "syslog-ng[28340]: Log statistics; dropped=pipe(/dev/xconsole)=2144807";

switch ($action) {
    case "get":
        $sql = "SELECT preg_name FROM lzecs";
    $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
    while($row = fetch_array($result)) { 
        $preg = $row['preg_name'];
        $name = preg_replace("/$preg/", '$1', $msg);
         // echo "Loop PREG = $preg\n";
        // logmsg("PREG = $preg");
        $query = "SELECT * FROM lzecs WHERE name = '$name'";
        // echo "$query\n";
        // logmsg("QRY = $query");
        $res = perform_query($query, $dbLink, $_SERVER['PHP_SELF']);
        if(mysql_affected_rows() == 1) {
            $line = fetch_array($res);
            $return->name  = "<tr><td>Name</td><td>".$line['name']."</td></tr>";
            $return->message = "<tr><td>Message Pattern</td><td>".$line['preg_msg']."</td></tr>";
            $return->explanation = "<tr><td>Explanation</td><td>".$line['explanation']."</td></tr>";
            $return->action = "<tr><td>Action</td><td>".$line['action']."</td></tr>";
            $return->si  = "<tr><td>Service Impacting?</td><td>".$line['si']."</td></tr>";
            $return->psr  = "<tr><td>Personal Severity Rating</td><td>".$line['psr']."</td></tr>";
            $return->suppress  = "<tr><td>Suppress</td><td>".$line['suppress']."</td></tr>";
            $return->trig_amt  = "<tr><td>Trigger Amount</td><td>".$line['trig_amt']."</td></tr>";
            $return->trig_win  = "<tr><td>Trigger Window</td><td>".$line['trig_win']."</td></tr>";
            $return->pairwith  = "<tr><td>Pairs With</td><td>".$line['pairwith']."</td></tr>";
            $return->vendor  = "<tr><td>Vendor</td><td>".$line['vendor']."</td></tr>";
            $return->type  = "<tr><td>Type</td><td>".$line['type']."</td></tr>";
            $return->class  = "<tr><td>Class</td><td>".$line['class']."</td></tr>";
            $return->lastupdate = "<tr><td>Last Updated</td><td>".$line['lastupdate']."</td></tr>";
            break; 
        }
    }
    if ($return) {
        echo json_encode($return);
    } else {
        $return->name = "LZECS_ERR_MISSING";
        echo json_encode($return);
    }
    break;

    case "save":
        $sql = "SELECT notes FROM $_SESSION[TBL_MAIN] WHERE id IN ('$dbid')";
    $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
    while($row = fetch_array($result)) { 
        echo $row['notes'];
    }
    break;
}
?>
