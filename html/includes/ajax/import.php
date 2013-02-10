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
$dbLink = db_connect_syslog(DBADMIN, DBADMINPW);
session_start();

$action = get_input('action');
$date_str = get_input('date_str');
$date_str_nodash = str_replace('-', '', $date_str);
$idate = get_input('impdate');
$idate = substr($idate,0,4).substr($idate,5,2).substr($idate,8,2);

$path = $_SESSION['PATH_BASE'];
$apath = $_SESSION['ARCHIVE_PATH'];


// Get a list of all files in the export directory
if ($handle = opendir($apath)) {
    while (false !== ($file = readdir($handle))) {
        if ( preg_match("/^dumpfile/",$file) ) {
            $date_string_from_files[] = substr($file,9,4)."-".substr($file,13,2)."-".substr($file,15,2);

        }
    }
    closedir($handle);
}

$prettydate = date('l, F d, Y', strtotime($date_str));

switch ($action) {
    case "info":
        if (in_array($date_str, $date_string_from_files)) {
            $sql = "SELECT records FROM archives where archive='dumpfile_$date_str_nodash.txt'";
            $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
            $count = mysql_num_rows($result);
            $row = fetch_array($result);
            echo commify($row["records"]) . " events available for import. <br />";
            echo "You may select \"Import Now\" to begin importing all data from $prettydate<br />";
        } else {
             echo $apath."dumpfile_$date_str_nodash.txt is missing or invalid<br />";
        }
        break;
    case "import":
        // if the file has already been decompressed, then look for the logfile
        $logfile = $apath."/_import_$date_str_nodash.log";
        if (file_exists($logfile)) {
            echo "Logfile:<br />";
                $loghandle = fopen($logfile, rb);
                while (!feof($loghandle)) {
                    echo fgets($loghandle, 8192)."<br>";
                }
                fclose($loghandle);
        echo "<i>Note: If you need to re-import this archive, you must remove $logfile </i><br />";
        } else {
            action($_SESSION['username'] . 'importing archive from '.$date_str);
            echo "Importing all events from $prettydate<br />";
            $cmd = "sudo $path/scripts/doimport.sh $date_str_nodash";
            exec($cmd, $out);
            echo "RUNNING";
        }
        break;
}

?>
