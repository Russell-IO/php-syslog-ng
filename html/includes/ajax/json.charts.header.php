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

$basePath21 = dirname( __FILE__ );
// include the jqUtils Class. The class is needed in all jqSuite components.

//require_once $basePath21."/../grid/php/jqUtils.php";

// include the jqChart Class
//require_once $basePath21."/../grid/php/jqChart.php";

$dbLink = db_connect_syslog(DBADMIN, DBADMINPW);
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
?>
