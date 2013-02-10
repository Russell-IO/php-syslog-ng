<?php
/*
 * portlet-table.php
 *
 * Developed by Clayton Dukes <cdukes@logzilla.pro>
 * Copyright (c) 2010 LogZilla, LLC
 * All rights reserved.
 * Last updated on 2010-06-15
 *
 * Pagination and table formatting created using 
 * http://www.frequency-decoder.com/2007/10/19/client-side-table-pagination-script/
 * Changelog:
 * 2010-02-28 - created
 *
 */

// session_start();
$basePath = dirname( __FILE__ );
require_once ($basePath . "/../common_funcs.php");
require_once ($basePath . "/../jqNewChart.php");
require_once ($basePath . "/portlet_header.php");

if ((has_portlet_access($_SESSION['username'], 'Graph Results') == TRUE) || ($_SESSION['AUTHTYPE'] == "none")) { 
    $dbLink = db_connect_syslog(DBADMIN, DBADMINPW);


    if (!$error) {
        //------------------------------------------------------------
        // Run the search query to get results from Sphinx
        //------------------------------------------------------------

        // #425: Moved below to portlet_header
        // $json_o = search_graph(json_encode($searchArr), $spx_max, "distributed", $spx_ip, $spx_port);


        //------------------------------------------------------------
        // If something goes wrong, search() will return an error
        //------------------------------------------------------------
        if (!preg_match("/^Sphinxql error/", "$json_o")) {

            // Decode returned json object into an array:
        // #425: Moved below to portlet_header
        //     $sphinx_results = json_decode($json_o, true);

            if (sizeof($sphinx_results) > 0) {
                foreach ( $sphinx_results as $result ) {
                    $tag[] = $result;
                }

            } else {
                // Negate search since sphinx returned 0 hits
                $where = "WHERE 1<1";
				$tag = array();
            }
            for ($i = 0; $i <= $limit; $i++) {
                $counters[] .= $meta["docs[$i]"];
            }
        } else {
            //------------------------------------------------------------
            // If Sphinx returns and error, let the user know
            //------------------------------------------------------------
            $lzbase = str_replace("html/includes/portlets", "", dirname( __FILE__ ));
            $error = "[ERROR] Invalid results from Sphinx";
            if (preg_match("/.*failed to open.*spd/", "$json_o")) {
                $error .= "The Sphinx indexes are missing!<br>\n";
                $error .= "Please be sure you have run the indexer on your server by typing:<br><br>\n";
                $error .= "sudo ${lzbase}sphinx/indexer.sh full<br><br>";
            } elseif (preg_match("/.*connection to.*failed.*/", "$json_o")) {
                $error .= "The Sphinx daemon is not running!<br>\n";
                $error .= "Please be sure you have started the daemon on your server by typing:<br><br>\n";
                $error .= "sudo ${lzbase}sphinx/bin/searchd -c ${lzbase}sphinx/sphinx.conf<br><br>";
            } else {
                $error = $json_o;
            }
        }


        if ($orderby) {
            $where.= " ORDER BY $orderby";  
        }
        if ($order) {
            $where.= " $order";  
        }

$fetch = array();
        if (!$error) {



            foreach ($tag as $t) {			
                $group_by = preg_replace ('/_crc/m', '', $groupby);
                switch ($group_by) {
                    case "host":
                        $sql = " SELECT host FROM hosts WHERE crc32(host)=".$t[$groupby];
                        break;
                    case "msg":
                        $sql = " SELECT msg FROM logs WHERE crc32(msg)=".$t[$groupby]." LIMIT 1"; // we only need one record
                        break;
                    case "program":
                        $sql = " SELECT name FROM programs WHERE crc=".$t[$groupby];
                        break;
                    case "facility":
                        $sql = " SELECT name FROM facilities WHERE code='".$t[$groupby]."'";
                        break;
                    case "severity":
                        $sql = "SELECT name FROM severities WHERE code='".$t[$groupby]."'";
                        break;
                    case "mne":
                        $sql = " SELECT name FROM mne WHERE crc=".$t[$groupby];
                        break;
                }

                $result = perform_query($sql,$dbLink);
                $res = fetch_array($result);
                $t['name'] = $res[0];
                $fetch[] = $t;
            }

// ------------------------------------------------------
// BEGIN Chart Generation
// ------------------------------------------------------
            $chart_type = (!empty($chart_type)) ? $chart_type : "pie";
            $group_by = preg_replace ('/_crc/m', '', $groupby);
            switch ($group_by) {
                case "host":
                    $propername = "Hosts";
                    break;
                case "msg":
                    $propername = "Messages";
                    break;
                case "program":
                    $propername = "Programs";
                    break;
                case "facility":
                    $propername = "Facilities";
                    break;
                case "severity":
                    $propername = "Severities";
                    break;
                case "mne":
                    $propername = "Mnemonics";
                    break;
                case "eid":
                    $propername = "EventId";
                    break;
            }
            $order_by = preg_replace ('/_crc/m', '', $order_by);
            switch ($orderby) {
                case "id":
                    $sortname = "ID";
                    break;
                case "counter":
                    $sortname = "Count";
                    break;
                case "facility":
                    $sortname = "Facility";
                    break;
                case "severity":
                    $sortname = "Severity";
                    break;
                case "fo":
                    $sortname = "First Occurrence";
                    break;
                case "lo":
                    $sortname = "Last Occurrence";
                    break;
            }
            if ($order == 'DESC') { 
                $topx = "Top"; 
            } else {
                $topx = "Bottom";
            }
            if ($start) {
                $title = "$topx $limit $propername (by $sortname) Report\nGenerated on " .date("D M d Y")."\n<br>(Date Range: $start - $end)" ;
            } else {
                $title = "$topx $limit $propername (by $sortname) Report\nGenerated on " .date("D M d Y")."\n" ;
            }
            if (($group_by == "msg") && ($chart_type !== "pie")) {{?>
                    <script type="text/javascript">
                    $(document).ready(function(){
                        warn('<br>When grouping by message, pie charts should be used. Most often, messages are too long to place across the X labels on line and bar charts. The results will be shown using a Pie');
                    });
                    </script>
            <?php } ?>
<?php
$chart_type="pie";
}

if ($chart_type == "pie") {
    $pievalues = array();
	

    if($fetch) {
	
        $i=0;
		//$JSCode = "var groubyNumbers = [";
        foreach ($fetch as $r) {
            $name = $propername;
            $pievalues[] = array($r['name'],intval($r['scount']));//new pie_value(intval($counters[$i]),  $name);
            $ids[] = $r[$groupby];
			//$JSCode .= $r[$groupby].", ";
			/*
			 * AS A SOLUTION FOR THE ID OF THE SERVICE
			 * WE COULD BUILD A JS ARRAY AND PUT THE LIST OF IDS IN IT
			 * AND USE THE event.point.x AS PARMETER TO THE FUNCITON TO 
			 * GET THE ID 
			 *
			 */
            $i++;
        }
		//$JSCode .= "0]";
    } else {
        $pievalues[] = array();//intval(0),  " No results found"); 
        $title =  "$topx $limit $propername Report <br/> No results match your search criteria"."\n" ;
    }
	
    /**
     * CREATED BY: WESAM GERGES
     * DATE: 12-03-2011      
     */	
    // Chart Options

    $tchart = new jqNewChart($chart_type,$pievalues,$title,"xyz");
    //$tchart->setTitleMargin(30);
    //$tchart->rotateXLabels(-90,'right',"bold 10px");

    $tchart->setTitleMargin(30);
    $tchart->setTooltip("return '<b>'+ this.point.name +'</b><br> '+ addCommas(this.point.y) +' of '+addCommas(this.total)+' <br/>'+ Math.round(100*this.percentage)/100  + '% of '+'".$topx.' '. $limit.' '. $propername."'");
    
  /*  
    $clickevent = <<<LOAD 
		function(e) {
			alert("testing");     
		} 
	LOAD;
    
   */ 
    $tchart->addClick(" pClick_(event.point.name, event.point.total, '".$propername."'); ");
	//$tchart->addJSCode($JSCode);
	
	echo $tchart->renderChart("chart_adhoc","","");

// END OF TESTING HIGHCHART AREA            

} else {
    if($fetch) {
        $i=0;
        foreach ($fetch as $r) {
            $ids[] = $r[$groupby];
            $dotValues[] = intval($r['scount']);
            $name = $r['name'];
            $x_horiz_labels[] = $name;
            $i++;
          }
 	  } else {
        $dotValues[] = 0;
        $x_horiz_labels[] =  "No results found"; 
        $title = "$topx $limit $propername Report\nNo results match your search criteria"."\n" ;
    }
    /**
     * CREATED BY: WESAM GERGES
     * DATE: 12-04-2011
     * 
     */	
    // Chart Options

    if($chart_type != 'line' && $chart_type != 'pie')
        $chart_type = 'column';

    $tchart = new jqNewChart($chart_type,$dotValues,$title,"$topx $limit $propername ",$x_horiz_labels);
	$tchart->setXAxisData($x_horiz_labels);
	$tchart->setTooltip("return '<b>'+ this.x +'</b><br> '+ addCommas(this.point.y) +' <br/> ".$topx.' '. $limit.' '. $propername."'");
    $tchart->setTitleMargin(30);
    echo $tchart->renderChart("chart_adhoc");
}

// ------------------------------------------------------
// END Chart Generation
// ------------------------------------------------------

?>

<script type="text/javascript">

 var targets  = {"Hosts":"hosts","Programs":"programs","Messages":"msg_mask","Facilities":"facilities","Severities":"severities","Mnemonics":"mnemonics","EventId":"eids"};
 var targets2  = {"Hosts":"sel_hosts","Programs":"programs","Messages":"msg_mask","Facilities":"facilities","Severities":"severities","Mnemonics":"sel_mne","EventId":"sel_eid"};
 
function pClick_ (value, total, targetKey)
{
    value = value.replace(/"/g, "");
    var postvars = '<?php echo $qstring?>';
    var max = '<?php echo $spx_max?>';
    if (total > max) { 
        total = max;
    }
    postvars = postvars.replace(/&limit=\d+/g, "&limit=" + total);
    postvars = postvars.replace('/&'+targets[targetKey]+'[]=/g', "");
    postvars = postvars.replace(/&groupby=\w+/g, "");
    var url = (postvars + "&"+targets2[targetKey]+"[]=" + value);
    url = url.replace(/Graph/g, "Results");
    self.location=url;
}

var full_graph_width = $(document).width()-125;
var full_graph_height = $(document).height()-200;
//REPLACED WITH OUR NEW CHART OBJECT

function addCommas(nStr)
{
    nStr += '';
    x = nStr.split('.');
    x1 = x[0];
    x2 = x.length > 1 ? '.' + x[1] : '';
    var rgx = /(\d+)(\d{3})/;
    while (rgx.test(x1)) {
        x1 = x1.replace(rgx, '$1' + ',' + '$2');
    }
    return x1 + x2;
}

</script>


<!-- Placeholder for Charts menu charts -->
<div id="chart_adhoc"></div>

<?php require_once ($basePath . "/portlet_footer.php");?>


<?php } else { 
    //------------------------------------------------------------
    // If an error was found anywhere, bail out and tell the user 
    // the reason for the error
    //------------------------------------------------------------
    ?>
<script type="text/javascript">
$(document).ready(function(){
        var err = "<?php echo preg_replace("/\r?\n/", "\\n", addslashes($error)); ?>";
        error(err);
        // alert(err);
        }); // end doc ready
    </script>
        <?php
}
}

} else { 
    //------------------------------------------------------------
    // This 'else' is from the top of the file for checking portlet 
    // access. If the user does not have permission, we remove the 
    // portlet
    //------------------------------------------------------------
    ?>
        <script type="text/javascript">
        $('#portlet_Search_Results').remove()
        </script>
        <?php } ?>
