<?php
/*
 * ajax/json.results.php
 *
 * Developed by Clayton Dukes <cdukes@logzilla.pro>
 * Copyright (c) 2011 logzilla.pro
 * All rights reserved.
 *
 * Changelog:
 * 2011-12-11 - created
 *
 */
$basePath = dirname( __FILE__ );
require_once ($basePath . "/../common_funcs.php");
$dbLink = db_connect_syslog(DBADMIN, DBADMINPW);
session_start();


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * Easy set variables
 */

/* Array of database columns which should be read and sent back to DataTables. Use a space where
 * you want to insert a non-database field (for example a counter or static image)
 */
if ($_SESSION['DEDUP'] == "1") {
$aColumns = array( 'id', 'eid', 'host', 'facility', 'severity', 'program', 'mne', 'msg', 'fo', 'lo', 'counter', 'notes' );
} else {
$aColumns = array( 'id', 'eid', 'host', 'facility', 'severity', 'program', 'mne', 'msg', 'lo', 'notes' );
}

/* Indexed column (used for fast and accurate table cardinality) */
$sIndexColumn = "id";


/* Database connection information */
$gaSql['user']       = DBADMIN;
$gaSql['password']   = DBADMINPW;
$gaSql['db']         = DBNAME;
$gaSql['server']     = DBHOST;
// $sTable = "".$_SESSION['username']."_search_results";
$sTable = $_SESSION['viewname'];


/* * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * * *
 * If you just want to use the basic configuration for DataTables with PHP server-side, there is
 * no need to edit below this line
 */

/* 
 * MySQL connection
 */
$gaSql['link'] =  mysql_pconnect( $gaSql['server'], $gaSql['user'], $gaSql['password']  ) or
die( 'Could not open connection to server' );

mysql_select_db( $gaSql['db'], $gaSql['link'] ) or 
die( 'Could not select database '. $gaSql['db'] );


/* 
 * Paging
 */
$sLimit = "";
if ( isset( $_GET['iDisplayStart'] ) && $_GET['iDisplayLength'] != '-1' )
{
    $sLimit = "LIMIT ".mysql_real_escape_string( $_GET['iDisplayStart'] ).", ".
        mysql_real_escape_string( $_GET['iDisplayLength'] );
}


/*
 * Ordering
 */
$sOrder = "";
if ( isset( $_GET['iSortCol_0'] ) )
{
    $sOrder = "ORDER BY  ";
    for ( $i=0 ; $i<intval( $_GET['iSortingCols'] ) ; $i++ )
    {
        if ( $_GET[ 'bSortable_'.intval($_GET['iSortCol_'.$i]) ] == "true" )
        {
            $sOrder .= $aColumns[ intval( $_GET['iSortCol_'.$i] ) ]."
                ".mysql_real_escape_string( $_GET['sSortDir_'.$i] ) .", ";
        }
    }

    $sOrder = substr_replace( $sOrder, "", -2 );
    if ( $sOrder == "ORDER BY" )
    {
        $sOrder = "";
    }
}


/* 
 * Filtering
 * NOTE this does not match the built-in DataTables filtering which does it
 * word by word on any field. It's possible to do here, but concerned about efficiency
 * on very large tables, and MySQL's regex functionality is very limited
 */
$sWhere = "";
if ( isset($_GET['sSearch']) && $_GET['sSearch'] != "" )
{
    $sWhere = "WHERE (";
    for ( $i=0 ; $i<count($aColumns) ; $i++ )
    {
        $sWhere .= $aColumns[$i]." LIKE '%".mysql_real_escape_string( $_GET['sSearch'] )."%' OR ";
    }
    $sWhere = substr_replace( $sWhere, "", -3 );
    $sWhere .= ')';
}

/* Individual column filtering */
for ( $i=0 ; $i<count($aColumns) ; $i++ )
{
    if ( isset($_GET['bSearchable_'.$i]) && $_GET['bSearchable_'.$i] == "true" && $_GET['sSearch_'.$i] != '' )
    {
        if ( $sWhere == "" )
        {
            $sWhere = "WHERE ";
        }
        else
        {
            $sWhere .= " AND ";
        }
        $sWhere .= $aColumns[$i]." LIKE '%".mysql_real_escape_string($_GET['sSearch_'.$i])."%' ";
    }
}

// date filtering
if (isset($_GET['startTime']) && preg_match('/^[0-9]+$/D', $_GET['startTime'])) {
    if ( $sWhere == "" )
    {
        $sWhere = "WHERE ";
    }
    else
    {
        $sWhere .= " AND ";
    }
    $sWhere .= ' `lo` >= FROM_UNIXTIME(' . $_GET['startTime'] . ')';

            $sql = 'SELECT FROM_UNIXTIME(' . $_GET['startTime'] . ')';
                $result = mysql_query($sql, $gaSql['link']) or die(mysql_error());
                while ($row = mysql_fetch_array($result)) {
                $start_time_formatted = $row[0];
                }
                mysql_free_result($result);
                }

                if (isset($_GET['endTime']) && preg_match('/^[0-9]+$/D', $_GET['endTime'])) {
                if ( $sWhere == "" )
                {
                $sWhere = "WHERE ";
                }
                else
                {
                $sWhere .= " AND ";
                }
                $sWhere .= ' `lo` <= FROM_UNIXTIME(' . $_GET['endTime'] . ')';

                    $sql = 'SELECT FROM_UNIXTIME(' . $_GET['endTime'] . ')';
                        $result = mysql_query($sql, $gaSql['link']) or die(mysql_error());
                        while ($row = mysql_fetch_array($result)) {
                        $end_time_formatted = $row[0];
                        }
                        mysql_free_result($result);
                        }

                        /*
                         * SQL queries
                         * Get data to display
                         */
                        $sQuery = "
                        SELECT SQL_CALC_FOUND_ROWS ".str_replace(" , ", " ", implode(", ", $aColumns))."
                        FROM   $sTable
                        $sWhere
                        $sOrder
                        $sLimit
                        ";
                        $rResult = mysql_query( $sQuery, $gaSql['link'] ) or die(mysql_error());

                    /* Data set length after filtering */
    $sQuery = "
SELECT FOUND_ROWS()
    ";
    $rResultFilterTotal = mysql_query( $sQuery, $gaSql['link'] ) or die(mysql_error());
    $aResultFilterTotal = mysql_fetch_array($rResultFilterTotal);
    $iFilteredTotal = $aResultFilterTotal[0];

    /* Total data set length */
    $sQuery = "
    SELECT COUNT(".$sIndexColumn.")
    FROM   $sTable
    ";
    $rResultTotal = mysql_query( $sQuery, $gaSql['link'] ) or die(mysql_error());
    $aResultTotal = mysql_fetch_array($rResultTotal);
    $iTotal = $aResultTotal[0];


    /*
     * Output
     */
    $output = array(
            "sEcho" => intval($_GET['sEcho']),
            "iTotalRecords" => $iTotal,
            "iTotalDisplayRecords" => $iFilteredTotal,
            "aaData" => array()
            );

while ( $aRow = mysql_fetch_array( $rResult ) )
{
    $row = array();
    for ( $i=0 ; $i<count($aColumns) ; $i++ )
    {
        if ( $aColumns[$i] == "version" )
        {
            /* Special output formatting for 'version' column */
            $row[] = ($aRow[ $aColumns[$i] ]=="0") ? '-' : $aRow[ $aColumns[$i] ];
        }
        if ( $aColumns[$i] == "msg" )
        {
            // cdukes: #338 - added htmlentities();
            /* #339 - Force utf-8 for msg column on foreign languages*/
            /* #362 - PHP's utf8_convert caused proiblems with IE9.
               As a result, I had to write a special function to check for utf-8, then convert if not */
            $msg = htmlentities($aRow[ $aColumns[$i] ]);
            $row[] = utfconvert($msg);
        }
        else if ( $aColumns[$i] != ' ' )
        {
            /* General output */
            $row[] = htmlentities($aRow[ $aColumns[$i] ]);
        }
    }
    $output['aaData'][] = $row;
}

// set the start time and end time
if (! isset($_GET['startTime']) && ! isset($_GET['endTime'])) {
    $start_time_formatted = NULL;
    $sql = 'SELECT lo FROM ' . $sTable . ' ORDER BY lo ASC LIMIT 1';
    $result = mysql_query($sql, $gaSql['link']) or die(mysql_error());
    while ($row = mysql_fetch_array($result)) {
        $start_time_formatted = $row[0];
        if (preg_match('/^([0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:)[0-9]{2}:[0-9]{2}$/D', $start_time_formatted, $matches)) {
            // round up to nearest hour
            $start_time_formatted = $matches[1] . '00:00';
        }
    }
    mysql_free_result($result);
    if ($start_time_formatted !== NULL) {
        $sql = 'SELECT UNIX_TIMESTAMP(\'' . $start_time_formatted . '\') AS unix';
        $result = mysql_query($sql, $gaSql['link']) or die(mysql_error());
        while ($row = mysql_fetch_array($result)) {
            $start_time = $row[0];
        }
        mysql_free_result($result);
        $output['startTime'] = $start_time;
    }

    $end_time_formatted = NULL;
    $sql = 'SELECT TIMESTAMPADD(HOUR, 2, lo) FROM ' . $sTable . ' ORDER BY lo DESC LIMIT 1';
    $result = mysql_query($sql, $gaSql['link']) or die(mysql_error());
    while ($row = mysql_fetch_array($result)) {
        $end_time_formatted = $row[0];
        if (preg_match('/^([0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:)[0-9]{2}:[0-9]{2}$/D', $end_time_formatted, $matches)) {
            $end_time_formatted = $matches[1] . '00:00';
        }
    }
    mysql_free_result($result);
    if ($end_time_formatted !== NULL) {
        $sql = 'SELECT UNIX_TIMESTAMP(\'' . $end_time_formatted . '\') AS unix';
        $result = mysql_query($sql, $gaSql['link']) or die(mysql_error());
        while ($row = mysql_fetch_array($result)) {
            $end_time = $row[0];
        }
        mysql_free_result($result);
        $output['endTime'] = $end_time;
    }
}

if (isset($start_time_formatted)) {
    $output['startTimeFormatted'] = $start_time_formatted;
}
if (isset($end_time_formatted)) {
    $output['endTimeFormatted'] = $end_time_formatted;
}

echo json_encode( $output );
?>
