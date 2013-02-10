<?php
/*
 * table_charts_header.php
 *
 * Developed by Clayton Dukes <cdukes@logzilla.pro>
 * Copyright (c) 2011 logzilla.pro
 * All rights reserved.
 *
 * Changelog:
 * 2011-12-14 - created
 *
 */

/*
   This include is used on both portlets for table and charts adhoc becuase they use the same functions to obtain data.
   This helps us not have to maintain both separate files
 */

$dbLink = db_connect_syslog(DBADMIN, DBADMINPW);

//------------------------------------------------------------
// start time is used to measure page load times
$start_time = microtime(true);
//------------------------------------------------------------

//------------------------------------------------------------
// The get_input statements below are used to get
// POST, GET, COOKIE or SESSION variables.
// Note that PLURAL words below are arrays.
// Where possible, list all get_input's here for readability
//------------------------------------------------------------
$page = get_input('page');
$show_suppressed = get_input('show_suppressed');
$spx_max = get_input('spx_max');
$spx_ip = get_input('spx_ip');
$spx_port = get_input('spx_port');
$spx_port = intval($spx_port);
$spx_max = intval($spx_max);
$groupby = get_input('groupby');
$chart_type = get_input('chart_type');
$fo_checkbox = get_input('fo_checkbox');
$fo_date = get_input('fo_date');
$fo_time_start = get_input('fo_time_start');
$fo_time_end = get_input('fo_time_end');
$lo_checkbox = get_input('lo_checkbox');
$lo_date = get_input('lo_date');
$lo_time_start = get_input('lo_time_start');
$lo_time_end = get_input('lo_time_end');
$q_type = get_input('q_type');
$tail = get_input('tail');
$limit = get_input('limit');
$searchText = get_input('msg_mask');
$notes_mask = get_input('notes_mask');
$orderby = get_input('orderby');
$order = get_input('order');
$dupop = get_input('dupop');
$dupcount = get_input('dupcount');
$severities = get_input('severities');
$facilities = get_input('facilities');
$hosts = get_input('hosts');
$sel_hosts = get_input('sel_hosts');
$eids = get_input('eids');
$sel_eids = get_input('sel_eid');
$mnemonics = get_input('mnemonics');
$sel_mne = get_input('sel_mne');
$programs = get_input('programs');
$sel_prg = get_input('sel_prg');
$topx = get_input('topx');
$graphtype = get_input('graphtype');
$chartdays = get_input('chartdays');



//------------------------------------------------------------
// Set Defaults
//------------------------------------------------------------
$total_found = 'unknown';
$qstring = '';
$spx_max = (!empty($spx_max)) ? $spx_max : $limit;
$spx_ip = (!empty($spx_ip)) ? $spx_ip : $_SESSION['SPX_SRV'];
$spx_port = (!empty($spx_port)) ? $spx_port : $_SESSION['SPX_PORT'];
$filter_fo_start = "";
$filter_fo_end = "";
$where = "WHERE 1=1";
$filter_lo_start = "";
$filter_lo_end = "";
$tail = (!empty($tail)) ? $tail : "off";
$limit = (!empty($limit)) ? $limit : "10";
unset($error);

if ($fo_checkbox == "on") {
    if($fo_date!='') {
        list($start,$end) = explode(' to ', $fo_date);
        if($end=='') $end = "$start" ; 
        if(($start==$end) and ($fo_time_start>$fo_time_end)) {
            $endx = strtotime($end);
            $endx = $endx+24*3600;
            $end = date('Y-m-d', mktime(0,0,0,date('m',$endx),date('d',$endx),date('Y',$endx))); }
            $start .= " $fo_time_start"; 
            $end .= " $fo_time_end"; 
            $where.= " AND fo BETWEEN '$start' AND '$end'";
            $filter_fo_start = "$start" ;
            $filter_fo_end = "$end" ;
    }
}
$start = "";
$end = "";
if (preg_match("/^\w+/", $chartdays)) {
    if ($chartdays == "thismonth") {
        $lo_date = date('Y-m-01');
    } else {
        $lo_date = date('Y-m-d', strtotime($chartdays));
    }
    if (($chartdays !== "yesterday") && ($chartdays !== "today")) {
        $lo_date .= " to ".date('Y-m-d', strtotime("today"));
    }
    $lo_checkbox = "on";
    $lo_time_start = "00:00:00";
    $lo_time_end = "23:59:59";
    $qstring .= "&lo_checkbox=on&lo_date=$lo_date";
    $qstring .= "&lo_time_start=$lo_time_start";
    $qstring .= "&lo_time_end=$lo_time_end";
}
if ($lo_checkbox == "on") {
    if($lo_date!='') {
        list($start,$end) = explode(' to ', $lo_date);
        if($end=='') $end = "$start" ; 
        if(($start==$end) and ($lo_time_start>$lo_time_end)) {
            $endx = strtotime($end);
            $endx = $endx+24*3600;
            $end = date('Y-m-d', mktime(0,0,0,date('m',$endx),date('d',$endx),date('Y',$endx))); }

            $start .= " $lo_time_start"; 
            $end .= " $lo_time_end"; 

            if ($date_andor=='') $date_andor = 'AND';
            $where.= " ".strtoupper($date_andor)." lo BETWEEN '$start' AND '$end'";
            $filter_lo_start = "$start" ;
            $filter_lo_end = "$end" ;
    }
}
if (($tail > 0) && ($limit > 10)) {
    ?>
        <script type="text/javascript">
        $(document).ready(function(){
                $( "<div id='tail_error'><center><br><br>Auto setting tail limit to 10<br>The Maximum result set for the auto refresh page is 10.<br>Any more than that would simply scroll off the page before being seen.<br>Please check your 'limit' setting in the 'Search Options' portlet.</div></center>" ).dialog({
modal: true,
width: "50%", 
height: 240, 
buttons: {
Ok: function() {
$( this ).dialog( "close" );
}
}
});
                }); // end doc ready
</script>
<?php
$limit = 10;
};


if ($severities) {
    $where .= " AND SEVERITY IN (";
    foreach ($severities as $sev) {
        if (!preg_match("/^\d/", $sev)) {
            $arr[] .= sev2int($sev);
	    $where .=  "'".sev2int($sev)."',";
        } else {
            $arr[] .= $sev;
	    $where .=  "'".$sev."',";
        }
        $qstring .= "&severities[]=".urlencode($sev);
    }
    $severities = $arr;
    $searchArr['severities'] = $severities;
    $where = rtrim($where, ",");
    $where .= ")";	
}

if ($facilities) {
    $where .= " AND FACILITY IN (";
    foreach ($facilities as $fac) {
        if (!preg_match("/^\d/", $fac)) {
            $arr[] .= fac2int($fac);
	    $where .= "'".fac2int($fac)."',";
        } else {
            $arr[] .= $fac;
	    $where .= "'".$fac."',";
        }
        $qstring .= "&facilities[]=".urlencode($fac);
    }
    $facilities = $arr;
    $searchArr['facilities'] = $facilities;
    $where = rtrim($where, ",");
    $where .= ")";
}





$filter_dup_min = "0";
$filter_dup_max = "999";
$dupop_orig = $dupop;

if (($dupop) && ($dupop != 'undefined')) {
    switch ($dupop) {
        case "gt":
            $dupop = ">";
        $filter_dup_min = $dupcount + 1;
        break;

        case "lt":
            $dupop = "<";
        $filter_dup_max = $dupcount - 1;
        break;

        case "eq":
            $dupop = "=";
        $filter_dup_min = $dupcount;
        $filter_dup_max = $dupcount;
        break;

        case "gte":
            $dupop = ">=";
        $filter_dup_min = $dupcount;
        break;
        $filter_dup_min = $dupcount;
        case "lte":
            $dupop = "<=";

        break;
    }
    $where.= " AND counter $dupop '$dupcount'"; 
}


//------------------------------------------------------------
// Set defaults for the searchArr
// This array is used to pass JSON requests to search()
// Which is the Sphinx function that does the actual search
//------------------------------------------------------------
$searchArr['chart_type'] = $chart_type;
$searchArr['lo_checkbox'] = $lo_checkbox;
$searchArr['lo_date'] = $lo_date;
$searchArr['lo_time_start'] = $lo_time_start;
$searchArr['lo_time_end'] = $lo_time_end;
$searchArr['orderby'] = $orderby;
$searchArr['order'] = $order;
$searchArr['limit'] = $limit;
$searchArr['groupby'] = $groupby;
$searchArr['tail'] = $tail;
$searchArr['show_suppressed'] = $show_suppressed;
$searchArr['q_type'] = $q_type;
$searchArr['page'] = $page;
$searchArr['dupop'] = $dupop_orig;
$searchArr['dupcount'] = $dupcount;

//------------------------------------------------------------
// Get the search operator 
// default is or (|) set in the search() function
//------------------------------------------------------------
$searchText = preg_replace('/^Enter Text To Search/', '',$searchText);
if (preg_match("/\||&|!|\-/", "$searchText")) {
    $searchArr['search_op'] = preg_replace ('/.*(\||&|!|\-).*/', '$1', $searchText);
    $op = $searchArr['search_op'];
}


if (preg_match("/^@host/i", "$searchText")) {
    $searchText = preg_replace('/^@[Hh][Oo][Ss][Tt][Ss]?\s+?(.*)/', '$1', $searchText);
    if ($op) {
        $h = explode("$op", $searchText);
        foreach ($h as $host) {
            $host = preg_replace('/\s+/', '',$host);
            $hosts[] .= $host;
            $searchText = preg_replace("/$host/", '', $searchText);
        }
        $searchText = preg_replace("/\\$op/", '', $searchText);
    } else {
        $hosts[] .= $searchText;
        $searchText = preg_replace("/$searchText/", '', $searchText);
    }
}

if (preg_match("/^@notes/i", "$searchText")) {
    $searchText = preg_replace('/^@[Nn][Oo][Tt][Ee][Ss]?(.*)/', '$1', $searchText);
    if ($op) {
        $h = explode("$op", $searchText);
        foreach ($h as $note) {
            $note = preg_replace('/\s+/', '',$note);
            $notes_mask[] .= $note;
            $searchText = preg_replace("/$note/", '', $searchText);
        }
        $searchText = preg_replace("/\\$op/", '', $searchText);
    } else {
        $notes_mask[] .= $searchText;
        $searchText = preg_replace("/$searchText/", '', $searchText);
    }
}


//------------------------------------------------------------
// Set these after the matches on hosts and notes above 
// so that the mask is cleaned up by them
//------------------------------------------------------------
if ($searchText) { 
    $searchArr['msg_mask'] = $searchText;
    if ($tail > 0) {
        $results = array();
        if (preg_match("/^(\w+)/", $searchText, $matches)) {
            $where .= " AND msg LIKE '%" . $matches[0] . "%'\n";
        }

        $word_to_capture = '(\w+)%';
        $patterns[] = "%(&)" . $word_to_capture;
        $patterns[] = "%(\|)" . $word_to_capture;
        $patterns[] = "%(!)" . $word_to_capture;
        $patterns[] = "%(-)" . $word_to_capture;

        foreach($patterns as $p) {
            preg_match($p,$searchText,$matches);
            $results[] = $matches;
        }
        // print_r($results);

        for($i=0; $i<count($results); $i++) {
            if ($results[$i][2]) {
                if (preg_match("/!|-/", $results[$i][1])) {
                    $where .= " AND msg NOT LIKE '%" . $results[$i][2] . "%'\n";
                } else {
                    $where .= " AND msg LIKE '%" . $results[$i][2] . "%'\n";
                }
            }
        }
    }
}
// echo "WHERE = $where<br>";
if (!is_array($hosts)) {
    $hosts = explode(",", $hosts);
}

$searchArr['hosts'] = $hosts;

if ($sel_hosts) {
    foreach ($sel_hosts as $host) {
        $hosts[] .= $host;
    }
    $searchArr['hosts'] = array_merge($sel_hosts, $hosts);
}

if ($eids) {
    if (!is_array($eids)) {
        $eids = explode(",", $eids);
    }
    $searchArr['eids'] = $eids;
}
if ($sel_eids) {
    foreach ($sel_eids as $eid) {
        $eids[] .= $eid;
    }
    $searchArr['eids'] = array_merge($sel_eids, $eids);
}
if ($searchArr['eids'])  {
    $where .= " AND eid IN (";
    foreach ($searchArr['eids'] as $eid) {
        $qstring .= "&eids[]=$eid";
        $where .= "'".$eid."',";
    }
    $where = rtrim($where, ",");
    $where .= ")";
}

if ($mnemonics) {
    if (!is_array($mnemonics)) {
        $mnemonics = explode(",", $mnemonics);
    }
    $searchArr['mnemonics'] = $mnemonics;
}
if ($sel_mne) {
    if ($mnemonics) {
        $searchArr['mnemonics'] = array_merge($sel_mne, $mnemonics);
    } else {
        $searchArr['mnemonics'] = $sel_mne;
    }
}
if ($searchArr['mnemonics'])  {
    $where .= " AND mne IN (";
    foreach ($searchArr['mnemonics'] as $mnemonic) {
        $qstring .= "&mnemonics[]=$mnemonic";
        $where .= "'".mne2crc($mnemonic)."',";
    }
    $where = rtrim($where, ",");
    $where .= ")";
}

if ($programs) {
    if (!is_array($programs)) {
        $programs = explode(",", $programs);
    }
    $searchArr['programs'] = $programs;
}
if ($sel_prg) {
    if ($programs) {
        $searchArr['programs'] = array_merge($sel_prg, $programs);
    } else {
        $searchArr['programs'] = $sel_prg;
    }
}


unset($searchArr['sel_hosts']);
unset($searchArr['sel_eid']);
unset($searchArr['sel_mne']);
unset($searchArr['sel_prg']);


//------------------------------------------------------------
// This is insecure, needs to pass through get_input
//------------------------------------------------------------
if ($_POST) {
    foreach ($_POST as $i => $value) {
        if (preg_match("/^jqg_/", "$i")) {
            $name_val = preg_replace('/jqg_(\w+grid)_(.*)/', '$1,$2', $i);
            $array = explode(',', $name_val);
            switch ($array[0]) {
                case "mnegrid":
                    $searchArr['mnemonics'][] .= $array[1];
                    break;
                case "eidgrid":
                    $searchArr['eids'][] .= $array[1];
                    break;
                case "hostsgrid":
                    $array[1] = preg_replace('/_/', '.', $array[1]);
                    $searchArr['hosts'][] .= $array[1];
                    break;
                case "prggrid":
                    $array[1] = preg_replace('/_/', '.', $array[1]);
                    $searchArr['programs'][] .= $array[1];
                    break;

            }
        }
    }
}

if(is_array($searchArr['mnemonics'])) {
    $where .= " AND mne IN (";
    $searchArr['mnemonics'] = array_unique($searchArr['mnemonics']);
    foreach ($searchArr['mnemonics'] as $mne) {
        $qstring .= "&mnemonics[]=$mne";
        $where.= "'".mne2crc($mne)."',";
    }
    $where = rtrim($where, ",");
    $where .= ")";
}

if(is_array($searchArr['hosts'])) {
    $searchArr['hostssel'] = array_unique($searchArr['hosts']);
    unset($searchArr['hosts']);  
    foreach ($searchArr['hostssel'] as $host) {
        $sql = "SELECT rbac(".$_SESSION['rbac'].", (select rbac_key from hosts where host='".$host."'))";
        $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
        $row = mysql_fetch_array($result, MYSQL_NUM);  
        if ( $row[0] == 1 ) { 
            $searchArr['hosts'][] .= $host;
        }
    }
}


if ($searchArr['hosts'][0]=='') {
    $where .= " AND host_crc IN (";
    // only look for hosts inside the lo area 
    $sql = "SELECT crc32(host) FROM hosts where rbac(".$_SESSION['rbac'].", rbac_key) and lastseen>='".$filter_lo_start."'";
    $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']); 
    while ($row = mysql_fetch_array($result, MYSQL_NUM)) {
        $searchArr['hosts'][] = $row[0];
        $where.= "'".$row[0]."',";
    }
    $where = rtrim($where, ",");
    $where .= ")";
}
else {
    if(is_array($searchArr['hosts'])) {
        $where .= " AND host IN (";
        foreach ($searchArr['hosts'] as $host) {
            $qstring .= "&hosts[]=$host";
            $where.= "'$host',";
        }
    } else {
        // If the user have no host access fill with dummy value
        $searchArr['hosts'][] = '0.0.0.0';
        $qstring .= "&hosts[]=0.0.0.0";
        $where.= " AND HOST IN ('0.0.0.0',";
    }
    $where = rtrim($where, ",");
    $where .= ")";
}


if(is_array($searchArr['eids'])) {
    $where .= " AND eid IN (";
    $searchArr['eids'] = array_unique($searchArr['eids']);
    foreach ($searchArr['eids'] as $eid) {
        $qstring .= "&eids[]=$eid";
        $where.= "'$eid',";
    }
    $where = rtrim($where, ",");
    $where .= ")";
}

if(is_array($searchArr['programs'])) {
    $where .= " AND program IN (";
    $searchArr['programs'] = array_unique($searchArr['programs']);
    foreach ($searchArr['programs'] as $program) {
        $qstring .= "&programs[]=" . rawurlencode($program);
        $where.= "'".prg2crc($program)."',";
    }
    $where = rtrim($where, ",");
    $where .= ")";
}
$tail_where = $where;

//------------------------------------------------------------
// Run the search query to get results from Sphinx
//------------------------------------------------------------
if ($page == "Graph") {
    $json_o = search_graph(json_encode($searchArr), $spx_max, "distributed", $spx_ip, $spx_port);
} else {
    $json_o = search(json_encode($searchArr), $spx_max, "distributed", $spx_ip, $spx_port);
}

// Decode returned json object into an array:
$sphinx_results = json_decode($json_o, true);

//------------------------------------------------------------
// If something goes wrong, search() will return an error
//------------------------------------------------------------

if (preg_match('/[Ee]rror|warning/', $sphinx_results[count($sphinx_results)-4]['Variable_name'])) {
    $error =$sphinx_results[count($sphinx_results)-4]['Variable_name'];
    if (preg_match('/:(.*)/', $sphinx_results[count($sphinx_results)-4]['Value'], $matches)) {
        $error .= '<br>' . $matches[0];
    } else {
        $error .= '<br>' . $sphinx_results[count($sphinx_results)-4]['Value'];
    }
    // echo "<br><br><br>E: $error<pre>";
    // die(print_r($sphinx_results));
}
if (!$sphinx_results) {
$error = $json_o;
}
if ($error) {
    //------------------------------------------------------------
    // If Sphinx returns and error, let the user know
    //------------------------------------------------------------
    $lzbase = str_replace("html/includes/portlets", "", dirname( __FILE__ ));
    if (preg_match("/.*failed to open.*spd/", "$json_o")) {
        $error = "The Sphinx indexes are missing!<br>\n";
        $error .= "Please be sure you have run the indexer on your server by typing:<br><br>\n";
        $error .= "sudo ${lzbase}sphinx/indexer.sh full<br><br>";
    } elseif (preg_match("/.*connection to.*failed.*/", "$json_o")) {
        $error = "The Sphinx daemon is not running!<br>\n";
        $error .= "Please be sure you have started the daemon on your server by typing:<br><br>\n";
        $error .= "sudo ${lzbase}sphinx/bin/searchd -c ${lzbase}sphinx/sphinx.conf<br><br>";
    }
    ?>
        <script type="text/javascript">
        $(document).ready(function(){
                var err = "<?php echo preg_replace("/\r?\n/", "\\n", addslashes($error)); ?>";
                error('[Sphinx Error] ' + err);
                // alert(err);
                }); // end doc ready
    </script>
        <?php } 
        //------------------------------------------------------------
        // Set the query string to be passed to the browser
        //------------------------------------------------------------
        $qstring .= "&page=$page";
        $qstring .= "&show_suppressed=$show_suppressed";
        $qstring .= "&spx_max=$spx_max";
        $qstring .= "&spx_ip=$spx_ip";
        $qstring .= "&spx_port=$spx_port";
        $qstring .= "&groupby=$groupby";
        $qstring .= "&chart_type=$chart_type";
        $qstring .= "&fo_checkbox=$fo_checkbox";
        $qstring .= "&fo_date=".urlencode($fo_date);
        $qstring .= "&fo_time_start=$fo_time_start";
        $qstring .= "&fo_time_end=$fo_time_end";
        $qstring .= "&lo_checkbox=$lo_checkbox";
        $qstring .= "&lo_date=".urlencode($lo_date);
        $qstring .= "&lo_time_start=$lo_time_start";
        $qstring .= "&lo_time_end=$lo_time_end";
        $qstring .= "&q_type=$q_type";
        $qstring .= "&tail=$tail";
        $qstring .= "&limit=$limit";
        $qstring .= "&msg_mask=$searchText";
        $qstring .= "&topx=$topx";
        $qstring .= "&notes_mask=$notes_mask";
        $qstring .= "&orderby=$orderby";
        $qstring .= "&order=$order";
        $qstring .= "&dupop=$dupop";
        $qstring .= "&dupcount=$dupcount";
        $qstring .= "&graphtype=$graphtype";
        // spanid is used to indicate which menu item to save favorites to (Searches or Charts)
        switch ($page) {
            case "Results":
                $spanid = 'search_history';
                break;
            case "Graph":
                $spanid = 'chart_history';
                break;
        }
// Replace ^& with ^? for URL saving
$qstring = preg_replace('/^&(.*)/', '?$1', $qstring);

// sessions used below for ajax/tail.php
$_SESSION['orderby'] = $orderby;
$_SESSION['order'] = $order;
$_SESSION['limit'] = $limit;
$_SESSION['searchArr'] = $searchArr;

?>
