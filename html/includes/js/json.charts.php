<?php

/*
 *
 * Developed by Clayton Dukes <cdukes@logzilla.pro>
 * Copyright (c) 2010 LogZilla, LLC
 * All rights reserved.
 * Last updated on 2010-06-15
 *
 * Changelog:
 * 2009-12-07 - created
 *
 */

// set manually for command line debugging:
// $chartId = "chart_mpw";

$basePath = dirname( __FILE__ );
require_once ($basePath . "/../common_funcs.php");
require_once ($basePath . "/../Chart.php");  
require_once ($basePath . "/../jqNewChart.php");
require_once ($basePath . "/../ofc/php/open-flash-chart.php");

$basePath2 = dirname( __FILE__ );
// include the jqUtils Class. The class is needed in all jqSuite components.

require_once $basePath2."/../grid/php/jqUtils.php";

// include the jqChart Class
require_once $basePath2."/../grid/php/jqChart.php";

$dbLink = db_connect_syslog(DBADMIN, DBADMINPW);
//$chartId = "chart_mpd";//get_input('chartId');
$nChart=new NewChart("pie",array(),"");
// ------------------------------------------------------
// BEGIN Ad-hoc chart variables
// ------------------------------------------------------
//---------------------------------------------------
// The get_input statements below are used to get
// POST, GET, COOKIE or SESSION variables.
// Note that PLURAL words below are arrays.
//---------------------------------------------------

//construct where clause 
$where = "WHERE 1=1";

$qstring = '';

// Special - this gets posted via javascript since it comes from the hosts grid
// Form code is somewhere near line 992 of js_footer.php
$hosts = get_input('hosts');
// sel_hosts comes from the main page <select>, whereas 'hosts' above this line comes from the grid select via javascript.
$sel_hosts = get_input('sel_hosts');
if ($hosts) {
    $pieces = explode(",", $hosts);
    foreach ($pieces as $host) {
        $sel_hosts[] .= $host;
        $qstring .= "&hosts[]=$host";
    }
}
$hosts = $sel_hosts;
if ($hosts) {
    $where .= " AND host IN (";
    $sph_msg_mask .= " @host ";
    
    foreach ($hosts as $host) {
            $where.= "'$host',";
            $sph_msg_mask .= "$host|";
        $qstring .= "&sel_hosts[]=$host";
    }
    $where = rtrim($where, ",");
    $sph_msg_mask = rtrim($sph_msg_mask, "|");
    $where .= ")";
    $sph_msg_mask .= " ";
}

// Special - this gets posted via javascript since it comes from the mnemonics grid
// Form code is somewhere near line 992 of js_footer.php
$mnemonics = get_input('mnemonics');
// sel_mne comes from the main page <select>, whereas 'mnemonics' above this line comes from the grid select via javascript.
$sel_mne = get_input('sel_mne');
if ($mnemonics) {
    $pieces = is_array($mnemonics)?$mnemonics:explode(",", $mnemonics); 
    foreach ($pieces as $mne) {
        $sel_mne[] .= $mne;
        $qstring .= "&mnemonics[]=$mne";
    }
}
$mnemonics = $sel_mne;
if ($mnemonics) {
    if (!in_array(mne2crc('None'), $mnemonics)) {
        $where .= " AND mne !='".mne2crc('None')."'";
    }
    $where .= " AND mne IN (";
    $sph_msg_mask .= " @mne ";
    
    foreach ($mnemonics as $mne) {
        if (!preg_match("/^\d+/m", $mne)) {
            $mne = mne2crc($mne);
        }
            $where.= "'$mne',";
            $sph_msg_mask .= "$mne|";
        $qstring .= "&sel_mne[]=$mne";
    }
    $where = rtrim($where, ",");
    $sph_msg_mask = rtrim($sph_msg_mask, "|");
    $where .= ")";
    $sph_msg_mask .= " ";
}


// portlet-programs
$programs = get_input('programs');
if ($programs) {
    $where .= " AND program IN (";
    foreach ($programs as $program) {
            $where.= "'$program',";
        $qstring .= "&programs[]=$program";
    }
    $where = rtrim($where, ",");
    $where .= ")";
}

// portlet-severities
$severities = get_input('severities');
if ($severities) {
    $where .= " AND severity IN (";
    foreach ($severities as $severity) {
            $where.= "'$severity',";
        $qstring .= "&severities[]=$severity";
    }
    $where = rtrim($where, ",");
    $where .= ")";
}


// portlet-facilities
$facilities = get_input('facilities');
if ($facilities) {
    $where .= " AND facility IN (";
    foreach ($facilities as $facility) {
            $where.= "'$facility',";
        $qstring .= "&facilities[]=$facility";
    }
    $where = rtrim($where, ",");
    $where .= ")";
}

// portlet-sphinxquery
$msg_mask = get_input('msg_mask');
$msg_mask = preg_replace ('/^Search through .*\sMessages/m', '', $msg_mask);
$msg_mask_oper = get_input('msg_mask_oper');
$qstring .= "&msg_mask=$msg_mask&msg_mask_oper=$msg_mask_oper";
if($msg_mask) {
    if ($_SESSION['SPX_ENABLE'] == "1") {
        //---------------BEGIN SPHINX
        require_once ($basePath . "/../SPHINX.class.php");
        // Get the search variable from URL
        // $var = @$_GET['msg_mask'] ;
        // $trimmed = trim($$msg_mask); //trim whitespace from the stored variable

        // $q = $trimmed;
#$q = "SELECT id ,group_id,title FROM documents where title = 'test one'";
#$q = " SELECT id, group_id, UNIX_TIMESTAMP(date_added) AS date_added, title, content FROM documents";
        $index = "idx_logs";

        $cl = new SphinxClient ();
        $hostip = $_SESSION['SPX_SRV'];
        $port = intval($_SESSION['SPX_PORT']);
        $cl->SetServer ( $hostip, $port );
        $res = $cl->Query ( $msg_mask, $index);
        if ( !$res )
        {
            die ( "ERROR: " . $cl->GetLastError() . ".\n" );
        } else
        {
            if ($res['total_found'] > 0) {
                $where .= " AND id IN (";
                foreach ( $res["matches"] as $doc => $docinfo ) {
                    $where .= "'$doc',";
                    // echo "$doc<br>\n";
                }
                $where = rtrim($where, ",");
                $where .= ")";
            } else {
                // Negate search since sphinx returned 0 hits
                $where = "WHERE 1<1";
                //  die(print_r($res));
            }
        }
        //---------------END SPHINX
    } else {
        switch ($msg_mask_oper) {
            case "=":
                $where.= " AND msg='$msg_mask'";  
            break;

            case "!=":
                $where.= " AND msg='$msg_mask'";  
            break;

            case "LIKE":
                $where.= " AND msg LIKE '%$msg_mask%'";  
            break;

            case "! LIKE":
                $where.= " AND msg NOT LIKE '%$msg_mask%'";  
            break;

            case "RLIKE":
                $where.= " AND msg RLIKE '$msg_mask'";  
            break;

            case "! RLIKE":
                $where.= " AND msg NOT LIKE '$msg_mask'";  
            break;
        }
    }
}
$notes_mask = get_input('notes_mask');
$notes_mask = preg_replace ('/^Search through .*\sNotes/m', '', $notes_mask);
$notes_mask_oper = get_input('notes_mask_oper');
$notes_andor = get_input('notes_andor');
$qstring .= "&notes_mask=$notes_mask&notes_mask_oper=$notes_mask_oper&notes_andor=$notes_andor";
if($notes_mask) {
    switch ($notes_mask_oper) {
        case "=":
            $where.= " AND notes='$notes_mask'";  
        break;

        case "!=":
            $where.= " AND notes='$notes_mask'";  
        break;

        case "LIKE":
            $where.= " AND notes LIKE '%$notes_mask%'";  
        break;

        case "! LIKE":
            $where.= " AND notes NOT LIKE '%$notes_mask%'";  
        break;

        case "RLIKE":
            $where.= " AND notes RLIKE '$notes_mask'";  
        break;

        case "! RLIKE":
            $where.= " AND notes NOT LIKE '$notes_mask'";  
        break;

        case "EMPTY":
            $where.= " AND notes = ''";  
        break;

        case "! EMPTY":
            $where.= " AND notes != ''";  
        break;
    }
} else {
    if($notes_mask_oper) {
        switch ($notes_mask_oper) {
            case "EMPTY":
                $where.= " AND notes = ''";  
            break;

            case "! EMPTY":
                $where.= " AND notes != ''";  
            break;
        }
    }
}

// portlet-datepicker 
$fo_checkbox = get_input('fo_checkbox');
    $qstring .= "&fo_checkbox=$fo_checkbox";
$fo_date = get_input('fo_date');
    $qstring .= "&fo_date=$fo_date";
$fo_time_start = get_input('fo_time_start');
    $qstring .= "&fo_time_start=$fo_time_start";
$fo_time_end = get_input('fo_time_end');
    $qstring .= "&fo_time_end=$fo_time_end";
$date_andor = get_input('date_andor');
    $qstring .= "&date_andor=$date_andor";
$lo_checkbox = get_input('lo_checkbox');
    $qstring .= "&lo_checkbox=$lo_checkbox";
$lo_date = get_input('lo_date');
    $qstring .= "&lo_date=$lo_date";
$lo_time_start = get_input('lo_time_start');
    $qstring .= "&lo_time_start=$lo_time_start";
$lo_time_end = get_input('lo_time_end');
    $qstring .= "&lo_time_end=$lo_time_end";
//------------------------------------------------------------
// START date/time
//------------------------------------------------------------
// FO
if ($fo_checkbox == "on") {
    if($fo_date!='') {
        list($start,$end) = explode(' to ', $fo_date);
        if($end=='') $end = "$start" ; 
        if($fo_time_start!=$fo_time_end) {
            $start .= " $fo_time_start"; 
            $end .= " $fo_time_end"; 
        }
            $where.= " AND fo BETWEEN '$start' AND '$end'";
    }
}
// LO
$start = "";
$end = "";
if ($lo_checkbox == "on") {
    if($lo_date!='') {
        list($start,$end) = explode(' to ', $lo_date);
        if($end=='') $end = "$start" ; 
        if($lo_time_start!=$lo_time_end) {
            $start .= " $lo_time_start"; 
            $end .= " $lo_time_end"; 
        }
            $where.= " ".strtoupper($date_andor)." lo BETWEEN '$start' AND '$end'";
    }
}
//------------------------------------------------------------
// END date/time
//------------------------------------------------------------

// portlet-search_options
$limit = get_input('limit');
$limit = (!empty($limit)) ? $limit : "10";
    $qstring .= "&limit=$limit";
$dupop = get_input('dupop');
    $qstring .= "&dupop=$dupop";
$dupcount = get_input('dupcount');
    $qstring .= "&dupcount=$dupcount";
if ($dupop) {
switch ($dupop) {
    case "gt":
        $dupop = ">";
    break;

    case "lt":
        $dupop = "<";
    break;

    case "eq":
        $dupop = "=";
    break;

    case "gte":
        $dupop = ">=";
    break;

    case "lte":
        $dupop = "<=";
    break;
}
        $where.= " AND counter $dupop '$dupcount'"; 
}
$orderby = get_input('orderby');
    $qstring .= "&orderby=$orderby";
$groupby = get_input('groupby');
    $qstring .= "&groupby=$groupby";
$groupby = (!empty($groupby)) ? $groupby : "host";
$order = get_input('order');
    $qstring .= "&order=$order";
if ($orderby) {
    $where.= " ORDER BY $orderby";  
}
if ($order) {
    $where.= " $order";  
}

$graphtype = get_input('graphtype');
    $qstring .= "&graphtype=$graphtype";

// ------------------------------------------------------
// END Ad-hoc chart variables
// ------------------------------------------------------

// ------------------------------------------------------
// BEGIN Chart Generation
// ------------------------------------------------------
switch ($chartId) {

    case "chart_mpm":
    $title = new title( "Last Hour" );
    $bar = new line();
    $bar2 = new line();
    // -------------------------
    // Get Messages Per Minute 
    // -------------------------
    $array = array(1);
    $avg = array();
    $hm = array();
    $sql = "SELECT name,value,updatetime, (SELECT ROUND(SUM(value)/60) FROM cache WHERE name LIKE 'chart_mpm_%') AS avg FROM cache WHERE name LIKE 'chart_mpm_%' AND updatetime BETWEEN NOW() - INTERVAL 59 MINUTE and NOW() - INTERVAL 0 MINUTE ORDER BY updatetime ASC";
    $queryresult = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
    while ($line = fetch_array($queryresult)) {
        $hms[] = preg_replace('/.*(\d\d):(\d\d):\d\d$/m', "$1:$2", $line['updatetime']);
        $count = intval($line['value']);
        if (!is_int($count)) {
            $count = 0;
        }
        $array[] = $count;
        $v = intval($line['avg']);
        if (is_int($v)){
            $avg[] = $v;
        }
    }
    if (empty($array)) $array[] = 0;
    
    $nChart = new NewChart("line",$array,$title->text," ",$hms,"",$chartId);
	$nChart->chartData($avg);
	$nChart->rotateXLabels(-90,'right',"bold 10px");
	$nChart->setMarker(false);
	$nChart->setTooltip("this.point.y");
	//echo $nChart->toJSON();
		/*
	$chart55 = new jqChart(); 
$chart55->setChartOptions(array("defaultSeriesType"=>"line","marginRight"=>130,"marginBottom"=>25))
->setTitle(array('text'=>'Monthly Average Temperature',"x"=>-20)) 
->setSubtitle(array("text"=>"Source: WorldClimate.com","x"=>-20)) 
->setxAxis(array( 
    "categories"=>array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'),
    'title'=>array('text'=>'Changelist', 'margin'=>-1) 
)) 
->setyAxis(array("title"=>array("text"=>"Temperature (Â°C)"))) 
->setTooltip(array("formatter"=>"function(){return '<b>'+ this.series.name +'</b><br/>'+this.x +': '+ this.y +' (Â°C)';}")) 
->setLegend(array( "layout"=>"vertical","align"=>"right","verticalAlign"=>'top',"x"=>-10,"y"=>100,"borderWidth"=>0))
->addSeries('Tokyo', array(7.0, 6.9, 9.5, 14.5, 18.2, 21.5, 25.2, 26.5, 23.3, 18.3, 13.9, 9.6))
->addSeries('New York', array(-0.2, 0.8, 5.7, 11.3, 17.0, 22.0, 24.8, 24.1, 20.1, 14.1, 8.6, 2.5))
->addSeries('Berlin', array(-0.9, 0.6, 3.5, 8.4, 13.5, 17.0, 18.6, 17.9, 14.3, 9.0, 3.9, 1.0))
->addSeries('London', array(3.9, 4.2, 5.7, 8.5, 11.9, 15.2, 17.0, 16.6, 14.2, 10.3, 6.6, 4.8));
echo $chart55->renderChart($chartId); 

	*/
	
    break;

    case "chart_mps":
        $title = new title( "Last Minute" );
    $bar = new line();
    $bar2 = new line();
    // -------------------------
    // Get Messages Per Second 
    // Alternate method - this will smooth out all the spikes:
    // select round(SUM(counter)/30) as count from logs where lo BETWEEN NOW() - INTERVAL 30 SECOND and NOW() - INTERVAL 0 SECOND;
    // -------------------------
    $array = array(1);
    $avg = array();
    $hms = array();
    $sql = "SELECT name,value,updatetime, (SELECT ROUND(SUM(value)/(SELECT count(*) FROM cache WHERE name LIKE 'chart_mps_%')) FROM cache WHERE name LIKE 'chart_mps_%') AS avg FROM cache WHERE name LIKE 'chart_mps_%' AND updatetime BETWEEN NOW() - INTERVAL 59 SECOND and NOW() - INTERVAL 0 SECOND ORDER BY updatetime ASC";
    $queryresult = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
    while ($line = fetch_array($queryresult)) {
        $hms[] = preg_replace('/.*(\d\d):(\d\d):(\d\d)$/m', "$1:$2:$3", $line['updatetime']);
        $count = intval($line['value']);
        if (!is_int($count)) {
            $count = 0;
        }
        $array[] = $count;
        $v = intval($line['avg']);
        if (is_int($v)){
            $avg[] = $v;
        }
    }
    if (empty($array)) $array[] = 0;

	$nChart = new NewChart("line",$array,$title->text," ",$hms,"",$chartId);
	$nChart->chartData($avg);
	$nChart->rotateXLabels(-90,'right',"bold 10px");
	$nChart->setMarker(false);
	echo $nChart->toJSON();

    //echo $chart->toPrettyString();
    break;

    case "chart_mmo":
        $title = new title( date("D M d Y") );
    $bar = new bar_rounded_glass();
   	// -------------------------
   	// Get Messages Per Month
   	// -------------------------
   	
   	
   	$array = array();
    // Below will update today every time the page is refreshed, otherwise we get stale data
    $sql = "REPLACE INTO cache (name,value,updatetime)  SELECT CONCAT('chart_mmo_',DATE_FORMAT(NOW(), '%Y-%m_%b')), (SELECT value from cache where name='msg_sum') as counter, NOW() from ".$_SESSION['TBL_MAIN']." where lo BETWEEN CONCAT(CURDATE(), ' 00:00:00') - INTERVAL 1 MONTH AND CONCAT(CURDATE(), ' 23:59:59') LIMIT 1";
    $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
   	for($i = 0; $i<=12 ; $i++) {
		// Check cache first
		$sql = "SELECT name, value, updatetime FROM cache WHERE name=CONCAT('chart_mmo_',DATE_FORMAT(NOW() - INTERVAL $i MONTH, '%Y-%m_%b'))";
	   	$result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
	   	if(num_rows($result) > 0) {
		   	while ($line = fetch_array($result)) {
			   	$pieces = explode("_", $line['name']);
				$date = explode("-", $pieces[2]);
			   	$days[] = $pieces[3].", ".$date[0];
			   	$array[] = intval($line['value']);
		   	}
	   	} else {
		   	// Insert into cache if it doesn't exist, then select the data from cache
		   	$sql = "INSERT INTO cache (name,value,updatetime)  SELECT CONCAT('chart_mmo_',DATE_FORMAT(NOW() - INTERVAL $i MONTH, '%Y-%m_%b')), SUM(counter) as count, NOW() from ".$_SESSION['TBL_MAIN']." where lo BETWEEN CONCAT(CURDATE(), ' 00:00:00') - INTERVAL $i MONTH and CONCAT(CURDATE(), ' 23:59:59') - INTERVAL $i MONTH ON duplicate KEY UPDATE updatetime=NOW()";
		   	$result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
		$sql = "SELECT name, value, updatetime FROM cache WHERE name=CONCAT('chart_mmo_',DATE_FORMAT(NOW() - INTERVAL $i MONTH, '%Y-%m_%b'))";
		   	$result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
		   	while ($line = fetch_array($result)) {
			   	$pieces = explode("_", $line['name']);
				$date = explode("-", $pieces[2]);
			   	$days[] = $pieces[3].", ".$date[0];
			   	$array[] = intval($line['value']);
		   	}
	   	}
	}
	// Delete any old entries
   	$sql = "DELETE FROM cache WHERE name like 'chart_mmo%' AND updatetime< NOW() - INTERVAL ".$_SESSION['CHART_MPD_DAYS']." MONTH";
   	$result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
	$nChart = new NewChart("column",array_reverse($array),$title->text," ",array_reverse($days),"",$chartId);
	echo $nChart->toJSON();
    break;

    case "chart_mpd_old":
        $title = new title( "Last Week" );
    $bar = new bar_rounded_glass();
    $bar2 = new line();
    $array = array();
    $avg = array();
    $hms = array();
    $sql = "SELECT name,value,updatetime, (SELECT ROUND(SUM(value)/7) FROM cache WHERE name LIKE 'chart_mpd_%') AS avg, DATE_FORMAT(updatetime, '%a, the %D') as Day FROM cache WHERE name LIKE 'chart_mpd_%' AND updatetime BETWEEN NOW() - INTERVAL 6 DAY and NOW() - INTERVAL 0 DAY ORDER BY updatetime ASC";
    $queryresult = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
    while ($line = fetch_array($queryresult)) {
        $hms[] = $line['Day'];
        $count = intval($line['value']);
        if (!is_int($count)) {
            $count = 0;
        }
        $array[] = $count;
        $v = intval($line['avg']);
        if (is_int($v)){
            $avg[] = $v;
        }
    }
    if (empty($array)) $array[] = 0;
    $bar->set_values( $array );
    $bar->set_tooltip("#val#<br>Average = ".commify($avg[0]));
    $bar2->set_values( ($avg) );
    $bar2->set_colour( "#40FF40" );
    $bar2->set_tooltip("#val#<br>Average [#x_label#]");
    //
    // create a Y Axis object
    //
    $y = new y_axis();
    // grid steps:
    $y->set_range( 0, max($array), round(max($array)/10));
    $chart->set_y_axis( $y );
    $x_labels = new x_axis_labels();
    $x_labels->set_vertical();
    $x_labels->set_labels( $hms );
    $x = new x_axis();
    $x->set_labels( $x_labels );
    $chart->set_x_axis( $x );
    echo $chart->toPrettyString();
    break;

    case "chart_mpd":
        $title = new title( date("D M d Y") );
    $bar = new bar_rounded_glass();
   	// -------------------------
   	// Get Messages Per Day
   	// -------------------------
   	$array = array();
    // Below will update today every time the page is refreshed, otherwise we get stale data
    $sql = "REPLACE INTO cache (updatetime,name, value) SELECT NOW(), CONCAT('chart_mpd_',DATE_FORMAT(NOW(), '%Y-%m-%d_%a')), (SUM(value)/2) FROM cache WHERE name LIKE 'chart_mph_%'";
    $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
   	for($i = 0; $i<=$_SESSION['CHART_MPD_DAYS'] ; $i++) {
		// Check cache first
		$sql = "SELECT name, value, updatetime, (SELECT ROUND(SUM(value)/".$_SESSION['CHART_MPD_DAYS'].") as avg FROM cache WHERE name LIKE 'chart_mpd_%') AS avg FROM cache WHERE name=CONCAT('chart_mpd_',DATE_FORMAT(NOW() - INTERVAL $i DAY, '%Y-%m-%d_%a'))";
	   	$result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
	   	if(num_rows($result) > 0) {
		   	while ($line = fetch_array($result)) {
			   	$pieces = explode("_", $line['name']);
				$date = explode("-", $pieces[2]);
			   	$days[] = $pieces[3].", ".$date[2];
                $array[] = intval($line['value']);
                $v = intval($line['avg']);
                if (is_int($v)){
                    $avg[] = $v;
                }
            }
	   	} else {
		   	// Insert into cache if it doesn't exist, then select the data from cache
		   	$sql = "INSERT INTO cache (name,value,updatetime)  SELECT CONCAT('chart_mpd_',DATE_FORMAT(NOW() - INTERVAL $i DAY, '%Y-%m-%d_%a')), SUM(counter) as count, NOW() from ".$_SESSION['TBL_MAIN']." where lo BETWEEN CONCAT(CURDATE(), ' 00:00:00') - INTERVAL $i DAY and CONCAT(CURDATE(), ' 23:59:59') - INTERVAL $i DAY ON duplicate KEY UPDATE updatetime=NOW()";
		   	$result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
		$sql = "SELECT name, value, updatetime, (SELECT ROUND(SUM(value)/".$_SESSION['CHART_MPD_DAYS'].") as avg FROM cache WHERE name LIKE 'chart_mpd_%') AS avg FROM cache WHERE name=CONCAT('chart_mpd_',DATE_FORMAT(NOW() - INTERVAL $i DAY, '%Y-%m-%d_%a'))";
		   	$result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
		   	while ($line = fetch_array($result)) {
			   	$pieces = explode("_", $line['name']);
				$date = explode("-", $pieces[2]);
			   	$days[] = $pieces[3].", ".$date[2];
			   	$array[] = intval($line['value']);
                $v = intval($line['avg']);
                if (is_int($v)){
                    $avg[] = $v;
                }
            }
	   	}
	}
	// Delete any old entries
   	$sql = "DELETE FROM cache WHERE name like 'chart_mpd%' AND updatetime< NOW() - INTERVAL ".$_SESSION['CHART_MPD_DAYS']." DAY";
   	$result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
	
	$nChart = new NewChart("column",array_reverse($array),$title->text," ",array_reverse($days),"",$chartId);
	$nChart->rotateXLabels(-90,'right',"bold 10px");
	//echo $nChart->toJSON();
	
	$tchart = new jqNewChart("column",array_reverse($array),$title->text," ",array_reverse($days));
	$tchart->rotateXLabels(-90,'right',"bold 10px");

	echo $tchart->renderChart($chartId);
	break;

    case "chart_mpw":
        $title = new title( date("D M d Y") );
    $bar = new bar_rounded_glass();
    // -------------------------
    // Get Messages Per Week
    // -------------------------
    $array = array();
    // Get the starting day of the week for your region
    $SoW = $_SESSION['CHART_SOW'];
    if ($SoW == "Sun") {
        $SoW = 1;
    } else {
        $SoW = 2;
    }
    // Below will update this week every time the page is refreshed, otherwise we get stale data
    $sql = "REPLACE INTO cache (name,value,updatetime) SELECT CONCAT('chart_mpw_',(DATE_ADD(CURDATE(),INTERVAL($SoW-DAYOFWEEK(CURDATE()))DAY))), (SELECT value from cache where name='msg_sum') as count, NOW() from $_SESSION[TBL_MAIN] where lo>=(DATE_ADD(CURDATE(),INTERVAL($SoW-DAYOFWEEK(CURDATE()))DAY)) LIMIT 1";
    $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);

    // Now process the rest
    for($i = 0; $i<=$_SESSION['CACHE_CHART_MPW'] ; $i++) {
        // Check cache first
		$sql = "SELECT name, value, updatetime FROM cache WHERE name=CONCAT('chart_mpw_',(DATE_ADD(CURDATE() - INTERVAL $i WEEK,INTERVAL($SoW-DAYOFWEEK(CURDATE() - INTERVAL $i WEEK))DAY)))";
	   	$result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
	   	if(num_rows($result) > 0) {
		   	while ($line = fetch_array($result)) {
			   	$pieces = explode("_", $line['name']);
				$date = $pieces[2];
                // Below sets X labels
			   	$xlabels[] = $date;
			   	$array[] = intval($line['value']);
		   	}
        } else {
            // Insert into cache if it doesn't exist, then select the data from cache
            $sql = "INSERT INTO cache (name,value,updatetime) SELECT CONCAT('chart_mpw_',(DATE_ADD(CURDATE() - INTERVAL $i WEEK,INTERVAL($SoW-DAYOFWEEK(CURDATE() - INTERVAL $i WEEK))DAY))), SUM(counter), NOW() from $_SESSION[TBL_MAIN] where lo BETWEEN (DATE_ADD(CURDATE() - INTERVAL $i WEEK,INTERVAL($SoW-DAYOFWEEK(CURDATE() - INTERVAL $i WEEK))DAY)) AND (DATE_ADD(CURDATE() - INTERVAL ".$i++." WEEK,INTERVAL($SoW-DAYOFWEEK(CURDATE() - INTERVAL ".$i++." WEEK))DAY)) ON duplicate KEY UPDATE updatetime=NOW()";
                $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
		$sql = "SELECT name, value, updatetime FROM cache WHERE name=CONCAT('chart_mpw_',(DATE_ADD(CURDATE() - INTERVAL $i WEEK,INTERVAL($SoW-DAYOFWEEK(CURDATE() - INTERVAL $i WEEK))DAY)))";
		   	$result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
		   	while ($line = fetch_array($result)) {
			   	$pieces = explode("_", $line['name']);
				$date = $pieces[3];
                // Below sets X labels
			   	$xlabels[] = $date;
			   	$array[] = intval($line['value']);
		   	}
	   	}
	}
	// Delete any old entries
   	$sql = "DELETE FROM cache WHERE name like 'chart_mpw%' AND updatetime< NOW() - INTERVAL ".$_SESSION['CACHE_CHART_MPW']." WEEK";
   	$result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);

	$nChart = new NewChart("column",array_reverse($array),$title->text," ",array_reverse($xlabels),"",$chartId);
	$nChart->rotateXLabels(-45,'right',"bold 10px");
	//echo $nChart->toJSON();
	
	$tchart = new jqNewChart("column",array_reverse($array),$title->text," ",array_reverse($days));
	$tchart->rotateXLabels(-45,'right',"bold 10px");
echo "gooooooooooo";
	echo $tchart->renderChart($chartId);
	
   break;

    case "chart_mph":
        $title = new title( "Last Day" );
    $bar = new bar_rounded_glass();
    $bar2 = new line();
   	// -------------------------
    // Get Messages Per Hour
    // -------------------------
    $array = array();
    $avg = array();
    $hms = array();
    $sql = "SELECT name,value,updatetime, (SELECT ROUND(SUM(value)/24) FROM cache WHERE name LIKE 'chart_mph_%') AS avg, DATE_FORMAT(updatetime, '%a-%h%p') as DH FROM cache WHERE name LIKE 'chart_mph_%' AND updatetime BETWEEN NOW() - INTERVAL 23 HOUR and NOW() - INTERVAL 0 HOUR ORDER BY updatetime ASC";
    $queryresult = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
    while ($line = fetch_array($queryresult)) {
        // $hms[] = preg_replace('/.*(\d\d):\d\d:\d\d$/m', "$1", $line['updatetime']);
        $hms[] = $line['DH'];
        $count = intval($line['value']);
        if (!is_int($count)) {
            $count = 0;
        }
        $array[] = $count;
        $v = intval($line['avg']);
        if (is_int($v)){
            $avg[] = $v;
        }
    }
    if (empty($array)) $array[] = 0;
 	$nChart = new NewChart("column",$array,$title->text," ",$hms,"Events",$chartId);
	$nChart->rotateXLabels(-90,'right',"bold 10px");
	echo $nChart->toJSON();	
    break;

    case "chart_tophosts":
        $title = new title( date("D M d Y") );
   	// -------------------------
   	// Get Top 10 hosts
   	// -------------------------
   	$pie = new pie();
   	// Check cache first
   	$sql = "SELECT name, value, updatetime FROM cache WHERE name like 'chart_tophosts%' AND updatetime> NOW() - INTERVAL ".$_SESSION['CACHE_CHART_TOPHOSTS']." MINUTE";
   	$result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
   	if(num_rows($result) >= 1) {
	   	while ($line = fetch_array($result)) {
		   	$pieces = explode("_", $line['name']);
		   	$hosts[] = explode("-", $pieces[2]);
		   	$count[] = intval($line['value']);
		   	$array[] = new pie_value(intval($line['value']),  $pieces[2]);
	   	}
   	} else {
	   	// Insert into cache if it doesn't exist, then select the data from cache
	   	$sql = "INSERT INTO cache (name,value,updatetime) SELECT CONCAT('chart_tophosts_',host), (SELECT value from cache where name='msg_sum') as count, NOW() from ".$_SESSION['TBL_MAIN']." GROUP BY host ORDER BY count DESC LIMIT 10 ON duplicate KEY UPDATE updatetime=NOW()";
	   	$result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
	   	$sql = "SELECT name, value, updatetime FROM cache WHERE name like 'chart_tophosts%' AND updatetime> NOW() - INTERVAL ".$_SESSION['CACHE_CHART_TOPHOSTS']." MINUTE";
	   	$result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
	   	while ($line = fetch_array($result)) {
		   	$pieces = explode("_", $line['name']);
		   	$hosts[] = explode("-", $pieces[2]);
		   	$count[] = intval($line['value']);
		   	$array[] = new pie_value(intval($line['value']),  $pieces[2]);
	   	}
   	}
	// Delete any old entries
   	$sql = "DELETE FROM cache WHERE name like 'chart_tophosts%' AND updatetime< NOW() - INTERVAL ".$_SESSION['CACHE_CHART_TOPHOSTS']." MINUTE";
   	$result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
	// Set random pie colors
   	for($i = 0; $i<=count($array) ; $i++) {
	$colors[] = '#'.random_hex_color(); // 09B826
	}

   	$pie->set_alpha(0.5);
	$pie->add_animation( new pie_fade() );
	$pie->add_animation( new pie_bounce(5) );
	// $pie->start_angle( 270 )
	$pie->start_angle( 0 );
	$pie->set_tooltip( '#label#<br>#val# of #total#<br>#percent# of top 10 hosts' );
	$pie->radius(80);
	$pie->set_colours( $colors );
    $pie->on_click('pie_slice_clicked');
	$pie->set_values( $array );
	$chart = new open_flash_chart();
	// $chart->set_title( $title );
	$chart->add_element( $pie );
	echo $chart->toPrettyString();
    break;

    case "chart_topmsgs":
        $title = new title( date("D M d Y") );
   	// -------------------------
   	// Get Top 10 Messages
   	// -------------------------
   	$pie = new pie();
   	$sql = "SELECT name, value, updatetime FROM cache WHERE name like 'chart_topmsgs%' AND updatetime> NOW() - INTERVAL ".$_SESSION['CACHE_CHART_TOPMSGS']." MINUTE";
   	$result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
   	if(num_rows($result) >= 1) {
	   	while ($line = fetch_array($result)) {
			$msg = ltrim($line['name'], "chart_topmsgs_");
		   	$count[] = intval($line['value']);
		   	$wrapmsg = wordwrap($msg, 60, "\n");
		   	$array[] = new pie_value(intval($line['value']),  $wrapmsg);
	   	}
   	} else {
	   	// Insert into cache if it doesn't exist, then select the data from cache
   	$sql = "INSERT INTO cache (name,value,updatetime) SELECT CONCAT('chart_topmsgs_',msg), (SELECT value from cache where name='msg_sum') AS count, NOW() FROM ".$_SESSION['TBL_MAIN']." GROUP BY msg ORDER BY count DESC LIMIT 10 ON duplicate KEY UPDATE updatetime=NOW()";
	   	$result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
	   	$sql = "SELECT name, value, updatetime FROM cache WHERE name like 'chart_topmsgs%' AND updatetime> NOW() - INTERVAL ".$_SESSION['CACHE_CHART_TOPMSGS']." MINUTE";
	   	$result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
	   	while ($line = fetch_array($result)) {
			$msg = ltrim($line['name'], "chart_topmsgs_");
		   	$count[] = intval($line['value']);
		   	$wrapmsg = wordwrap($msg, 60, "\n");
		   	$array[] = new pie_value(intval($line['value']),  $wrapmsg);
	   	}
   	}
	// Delete any old entries
   	$sql = "DELETE FROM cache WHERE name like 'chart_topmsgs%' AND updatetime< NOW() - INTERVAL ".$_SESSION['CACHE_CHART_TOPMSGS']." MINUTE";
   	$result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
	// Generate random pie colors
   	for($i = 0; $i<=count($array) ; $i++) {
	$colors[] = '#'.random_hex_color(); // 09B826
	}

   	$pie->set_alpha(0.5);
	$pie->add_animation( new pie_fade() );
	$pie->add_animation( new pie_bounce(5) );
	// $pie->start_angle( 270 )
	$pie->start_angle( 0 );
	$pie->set_tooltip( '#label#<br>#val# of #total#<br>#percent# of top 10 messages' );
	$pie->radius(80);
	$pie->set_colours( $colors );
	$pie->set_values( $array );
	$chart = new open_flash_chart();
	// $chart->set_title( $title );
	$chart->add_element( $pie );
	echo $chart->toPrettyString();
    break;

    // Default is to generate an Ad-hoc chart
    default:
    $chartType = get_input('chartType');
    $chartType = (!empty($chartType)) ? $chartType : "pie";
    $sql = "SELECT id, host, facility, priority, tag, program, msg, counter, fo, lo, notes, (SELECT value from cache where name='msg_sum') as count FROM ".$_SESSION['TBL_MAIN']." $where GROUP BY $groupby ORDER BY count LIMIT $limit";
    $title = new title( date("D M d Y") );
    $ctype = new pie();
    $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
    if(num_rows($result) >= 1) {
        while ($line = fetch_array($result)) {
            $hosts[] = $line['host'];
            $pievalues[] = new pie_value(intval($line['count']),  $line['host']);
        }
    }
    // Generate random pie colors
    for($i = 0; $i<=count($pievalues) ; $i++) {
        $colors[] = '#'.random_hex_color(); // 09B826
    }

    $ctype->set_alpha(0.5);
    $ctype->add_animation( new pie_fade() );
    $ctype->add_animation( new pie_bounce(5) );
    // $ctype->start_angle( 270 )
    $ctype->start_angle( 0 );
	$ctype->set_tooltip( "#label#<br>#val# of #total#<br>#percent# of top $limit hosts" );
	$ctype->radius(80);
	$ctype->set_colours( $colors );
	$ctype->set_values( $pievalues );
	$chart = new open_flash_chart();
	// $chart->set_title( $title );
	$chart->add_element( $ctype );
	echo $chart->toPrettyString();
}

// ------------------------------------------------------
// END Chart Generation
// ------------------------------------------------------
?>
