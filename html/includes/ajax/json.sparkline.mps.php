<?php
/*
 *
 * Developed by Clayton Dukes <cdukes@logzilla.pro>
 * Copyright (c) 2010-2012 LogZilla, LLC
 * All rights reserved.
 * Last updated on 2012-09-25
 *
 * Changelog:
 * 2009-12-06 - created
 * 2012-09-14 - moved to RRD file
 * 2012-09-25 - moved back to MySQL :-)
 *
 */
@session_start();
$basePath = dirname( __FILE__ );
require_once ($basePath . "/../common_funcs.php");
$dbLink = db_connect_syslog(DBADMIN, DBADMINPW);

$to = time();
$from = $to - 60;

$sql = 
    "SELECT ts_from, count " .
    "FROM events_per_second " .
    "WHERE name = 'msg' " .
    "AND ts_from > $from " .
    "AND ts_from <= $to";

$queryresult = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);

$values = array_fill( $from, 60, 0 );
while ($line = fetch_array($queryresult)) {
    $values[$line['ts_from']] = intval($line['count']);
}

echo(json_encode(array_values($values)));

?>
