<?php
/*
 * portlet-table.php
 *
 * Developed by Clayton Dukes <cdukes@logzilla.pro>
 * Copyright (c) 2010 LogZilla, LLC
 * All rights reserved.
 * Last updated on 2010-06-15
 *
 * Changelog:
 * 2010-02-28 - created
 *
 */

// session_start();
$basePath = dirname( __FILE__ );
require_once ($basePath . "/../common_funcs.php");
require_once ($basePath . "/portlet_header.php");
if ((has_portlet_access($_SESSION['username'], 'Search Results') == TRUE) || ($_SESSION['AUTHTYPE'] == "none")) {
    $dbLink = db_connect_syslog(DBADMIN, DBADMINPW);


    // $error gets set in portlets/portlet_header.php
    if (!$error) {

        // echo "<pre>";
        // die(print_r($sphinx_results));

        //------------------------------------------------------------
        // CDUKES
        // Note: Meta info is appended to search results, so the metadata 
        // can be found at position $limit (e.g: if user sets limit = 100, then "total" is found at $result[100]
        // Note: Have to get first occurrence of total, total_found and time because of the RBAC hosts being appended to the query.
        // When the hosts are appended, these totals show for those as well in the meta info
        //------------------------------------------------------------
        // echo "<pre>";
        // die(print_r($sphinx_results));
        $meta['total'] = $sphinx_results[$limit]['Value'];
        $meta['total_found'] = $sphinx_results[$limit+1]['Value'];
        $meta['time'] = $sphinx_results[$limit+2]['Value'];
        // Get totals
        $total = $meta['total'];
        $total_found = $meta['total_found'];
        $time = $meta['time'];
        for ($i = $limit; $i <= count($sphinx_results, COUNT_RECURSIVE); $i++) {
            if (!preg_match("/^total|time/", $sphinx_results[$i]['Variable_name'])) {
                $meta[$sphinx_results[$i]['Variable_name']] = $sphinx_results[$i]['Value'];
            }
            // Remove the meta info from the results so that we can do our mysql now.
            unset($sphinx_results[$i]);
        }

        if (sizeof($sphinx_results) > 0) {
            $where = " where id IN (";
            foreach ( $sphinx_results as $result ) {
                $where .= "'$result[0]',";
            }
            $where = rtrim($where, ",");
            $where .= ")";

        } else {
            // Negate search since sphinx returned 0 hits
            $where = "WHERE 1<1";
        }


        if ($orderby) {
            $where.= " ORDER BY $orderby";  
        }
        if ($order) {
            $where.= " $order";  
        }

    }

    if (!$error) {
        $sql_fac = "(SELECT name FROM facilities WHERE code=facility) AS facility";
        $sql_sev = "(SELECT name FROM severities WHERE code=severity) AS severity";
        $sql_prg = "(SELECT name FROM programs WHERE crc=program) AS program";
        $sql_mne = "(SELECT name FROM mne WHERE crc=mne) AS mne";
        $select_columns = "id,host,$sql_fac,$sql_sev,$sql_prg,$sql_mne,msg,eid,suppress,counter,fo,lo,notes";
        $_SESSION['select_columns'] = $select_columns;

        // Generate a fairly random name for the view so they don't overlap. We'll cleanup these views in the nightly cleanup
        // TH: small bug-fix: use Hour in 24h format; views were only deleted once a day
        $uname_clean = preg_replace('/[^a-zA-Z0-9\s]/', '', $_SESSION['username']);
        $_SESSION['viewname'] = date('His') . $uname_clean."_search_results";

        switch ($show_suppressed):
        case "suppressed":
            $sql = "CREATE OR REPLACE VIEW ".$_SESSION['viewname']." AS SELECT $select_columns FROM ".$_SESSION['TBL_MAIN']."_suppressed $where LIMIT $limit";
            // $sql = "SELECT * FROM ".$_SESSION['TBL_MAIN']."_suppressed $where LIMIT $limit";
            break;
        case "unsuppressed":
            $sql = "CREATE OR REPLACE VIEW ".$_SESSION['viewname']." AS SELECT $select_columns FROM ".$_SESSION['TBL_MAIN']."_unsuppressed $where LIMIT $limit";
            // $sql = "SELECT * FROM ".$_SESSION['TBL_MAIN']."_unsuppressed $where LIMIT $limit";
            break;
        default:
            $sql = "CREATE OR REPLACE VIEW ".$_SESSION['viewname']." AS SELECT $select_columns FROM ".$_SESSION['TBL_MAIN']." $where LIMIT $limit";
            // $sql = "SELECT * FROM ".$_SESSION['TBL_MAIN'] ." $where LIMIT $limit";
            endswitch;

            $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']); 

            if ($total_found == 'unknown') {

                switch ($show_suppressed):
                case "suppressed":
                    $sql = "SELECT count(*) as tots FROM ".$_SESSION['TBL_MAIN']."_suppressed $where";
                    break;
                case "unsuppressed":
                    $sql = "SELECT count(*) as tots FROM ".$_SESSION['TBL_MAIN']."_unsuppressed $where";
                    break;
                default:
                    $sql = "SELECT count(*) as tots FROM ".$_SESSION['TBL_MAIN'] ." $where";
                    endswitch;

                    $tots =perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
                    while($row = fetch_array($tots)) {
                        $total_found = $row['tots'];
                    }
            }

            ?>
                <script type="text/javascript" charset="utf-8">
                $(document).ready(function() {
                        $('#results').dataTable( {
			"bProcessing": true,
                	"bServerSide": true,
                	"sScrollX": "100%",
                            "bServerSide": true,
                            // "sScrollXInner": "150%",
                            //"bJQueryUI": true,
                            "aoColumns":[
                            { "sWidth": "2%" }, // ID
                            { "sWidth": "2%" }, // EID
                            { "sWidth": "2%" }, // Host
                            { "sWidth": "2%" }, // Fac
                            { "sWidth": "2%" }, // Sev
                            { "sWidth": "2%" }, // Program
                            { "sWidth": "2%" }, // Mne
                            { "sWidth": "50%" }, // MSG
<?php if ($_SESSION['DEDUP'] == "1") {?>
                            { "sWidth": "10%" }, // FO
                            { "sWidth": "10%" }, // LO
                            { "sWidth": "5%" }, // Counter
                            <?php } else { ?>
                            { "sWidth": "20%" }, // LO
                            <?php } ?>
                            // disabled in this version since there is currently no way to enter notes
                            // { "sWidth": "15%" }, // Notes
                            ],
                            "aaSorting": [[ 0, "desc" ]],
                                "fnServerParams": function ( aoData ) {
                                    var $slider = $('#slider');
                                    if ($slider[0].hasChildNodes()) {
                                        var values = $slider.slider('values');
                                        var startTime = $slider.data('startTime');
                                        aoData.push( { "name": "startTime", "value": startTime + values[0] } );
                                        aoData.push( { "name": "endTime", "value": startTime + values[1] } );
                                    }
                                },

                            "fnServerData": function ( sSource, aoData, fnCallback ) {
                                $.ajax({
                                        "dataType": 'json',
                                        "type": 'GET',
                                        "url": sSource,
                                        "data": aoData,
                                        "success": function (data, textStatus, jqXHR) {
                                        if (data.startTime && data.endTime) {
                                        // create the slider
                                        var $slider = $('#slider');
                                        var startTime = parseInt(data.startTime);
                                        var endTime = parseInt(data.endTime);
                                        $slider.data('startTime', startTime);
                                        var min = 0;
                                        var max = endTime - startTime;
                                        $slider.slider({
range: true,
min: min,
max: max,
values: [min, max],
change: function (event, ui) {
var values = $slider.slider('values');

// refresh the table
var table = $('#results').dataTable();
table.fnDraw(true);

}
});

if (data.startTimeFormatted && data.endTimeFormatted) {
    $('#sliderStart').html(data.startTimeFormatted);
    $('#sliderEnd').html(data.endTimeFormatted);
}
} 

if (data.startTimeFormatted && data.endTimeFormatted) {
    $('#sliderValues').html('Slide below to filter by time.<br>Current range: ' + data.startTimeFormatted + ' - ' + data.endTimeFormatted);
}

fnCallback(data, textStatus, jqXHR);
}
});
},
    "sAjaxSource": "includes/ajax/json.results.php"
    } );
function fnShowHide( iCol )
{
        /* Get the DataTables object again - this is not a recreation, just a get of the object */
        var oTable = $('#results').dataTable();
             
            var bVis = oTable.fnSettings().aoColumns[iCol].bVisible;
                oTable.fnSetColumnVis( iCol, bVis ? false : true );
}
// Hide the Database ID column - we only use it to sort on
fnShowHide(0);
<?php if($_SESSION['SNARE'] == "0") {?>
    // hide the EID column
    fnShowHide(1);
    <?php } ?>


//------------------------------------
// BEGIN auto-refresh 
//------------------------------------
var tail = '<?php echo $tail?>';
if (tail > 0) {
    $('#results_filter').html('<button id="btnPause">Pause</button>');
    $("button").button();

    var time = tail;
    var timerID;
    if (time == tail) {
        startIt();
        time = 99999;
    }
    $("#btnPause").click( function() { 
            if (time == tail) {
            $('#btnPause').text('Running');
            startIt();
            time = 99999;
            } else {
            $('#btnPause').text('Click to Resume');
            stopIt();
            time = tail;
            }
            });
    $("button").button();
    $("#sliderValues").hide();
    $("#slider").hide();
    $("#results_length").hide();
    $("#results_processing").hide();
    $("#results_paginate").hide();
} else {
$("#results_filter").append($('#results_paginate'));
$("#results_processing").hide();
}

}); // end doc ready

var tail = '<?php echo $tail?>';
function fireIt() {
    <?php
if ($tail > 0) {
        $tail_where = preg_replace('/host_crc/','crc32(host)',$tail_where); 
        $sql = "CREATE OR REPLACE VIEW ".$_SESSION['viewname']." AS SELECT ".$select_columns." FROM ".$_SESSION['TBL_MAIN']." ".$tail_where." ORDER BY lo DESC LIMIT ".$limit;
    $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
    if(!$result){
    ?>
        var err = '<?php echo mysql_error($dbLink)?>';
    error("MySQL error in table refresh  for Tail mode" + err);
            stopIt();
    <?php }} ?>
    var table = $('#results').dataTable();
    table.fnDraw(true);
};
function startIt () { 
    timerID = setInterval("fireIt()", tail); 
} 
function stopIt() { 
    clearInterval(timerID); 
} 
//------------------------------------
// END auto-refresh 
//------------------------------------

</script>

<div id="sliderValues"></div>
<div id="slider" style="width: 99.7%;"></div>
<!-- <div id="sliderStart" style="float: left;"></div>
<div id="sliderEnd" style="float: right;"></div> -->
<br />
<div id="table_container">
<div id="dynamic">
<table style="width: 98%" cellpadding="0" cellspacing="0" border="0" class="display" id="results">
<thead>
<tr>
<th>ID</th>
<th>EID</th>
<th>Host</th>
<th>Facility</th>
<th>Severity</th>
<th>Program</th>
<th>Mnemonic</th>
<th>Message</th>
<?php
if ($_SESSION['DEDUP'] == "1") {
echo "<th>First Seen</th>";
echo "<th>Last Seen</th>";
echo "<th>Count</th>";
} else {
echo "<th>Received</th>";
}
?>
<!-- Notes disable for now 
<th>Notes</th>
-->
</tr>
</thead>
<tbody>
<tr>
<td colspan="9" class="dataTables_empty">Loading data from server</td>
</tr>
</tbody>
<tfoot>
<tr>
<th>ID</th>
<th>EID</th>
<th>Host</th>
<th>Facility</th>
<th>Severity</th>
<th>Program</th>
<th>Mnemonic</th>
<th>Message</th>
<?php
if ($_SESSION['DEDUP'] == "1") {
echo "<th>First Seen</th>";
echo "<th>Last Seen</th>";
echo "<th>Count</th>";
} else {
echo "<th>Received</th>";
}
?>
<!-- Notes disable for now 
<th>Notes</th>
-->
</tr>
</tfoot>
</table>

<script type="text/javascript">
$(function(){
        $.contextMenu({
selector: '#esults td', 
items: {
key: {
name: "Menu Clickable", 
callback: function (key, opt) {
alert(opt.$trigger.html());
}
}
}, 
events: {
show: function(opt) {
// this is the trigger element
var $this = this;
// import states from data store 
$.contextMenu.setInputValues(opt, $this.data());
// this basically fills the input commands from an object
// like {name: "foo", yesno: true, radio: "3", …}
}, 
hide: function(opt) {
// this is the trigger element
          var $this = this;
          // export states to data store
          $.contextMenu.getInputValues(opt, $this.data());
          // this basically dumps the input commands' values to an object
          // like {name: "foo", yesno: true, radio: "3", …}
      }
}
});
});
</script>

</div>
</div>
<div class="spacer"></div>
<script type="text/javascript">
//------------------------------------------------------------
// Display the total matching DB entries along with the X of X entries
//------------------------------------------------------------
<?php
if ($total_found < $limit) {
    $limit = $total_found;
}
?>
var total = '<?php echo $total?>'
if (total < 1) {
    total = 'No results found for date range <?php echo "$start - $end<br>Time to search: $time seconds";?>'
} else {
    total = '<?php echo "Displaying Top ".commify($total)." Matches of ".commify($total_found)." possible<br>Date Range: $start - $end<br>Time to search: $time seconds";?>';
}

$("#portlet-header_Search_Results").html("<div style='text-align: center'>" + total + "</div>");
</script>

<!-- BEGIN Add Save URL icon to search results -->
<script type="text/javascript">
$("#portlet-header_Search_Results").prepend('<span id="export" class="ui-icon ui-icon-print"></span>');
$("#portlet-header_Search_Results").prepend('<span id="span_results_save_icon" class="ui-icon ui-icon-disk"></span>');
//---------------------------------------------------------------
// END: Save URL function
//---------------------------------------------------------------
</script>
<?php
require_once ($basePath . "/portlet_footer.php");
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
