<?php

/*
 *
 * Developed by Clayton Dukes <cdukes@logzilla.pro>
 * Copyright (c) 2010 LogZilla, LLC
 * All rights reserved.
 * Last updated on 2010-06-15
 *
 * Changelog:
 * 2009-12-13 - created
 *
 */

session_start();
$basePath = dirname( __FILE__ );
require_once ($basePath . "/../common_funcs.php");
if ((has_portlet_access($_SESSION['username'], 'Snare EventId') == TRUE) || ($_SESSION['AUTHTYPE'] == "none")) {
$dbLink = db_connect_syslog(DBADMIN, DBADMINPW);
// -------------------------
// Get EventId
// -------------------------
$sql = "SELECT COUNT(*) FROM (SELECT eid FROM snare_eid where eid>0 and hidden='false') AS result";
$result = perform_query($sql, $dbLink, $_REQUEST['pageId']);
$total = mysql_fetch_row($result);
$count = $total[0];
if( $count >0 ) { 
?>
<script type="text/javascript">
var limit = <?php echo ($_SESSION['PORTLET_EID_LIMIT'])?>;
var cnt = <?php echo $count?>;
if (cnt < 11) {
    $('#portlet-header_Snare_EventId').text("Last " + cnt + " Windows Event ID's");
    } else {
    $('#portlet-header_Snare_EventId').text("Last " + limit + " Windows Event ID's");
    $('#portlet-header_Snare_EventId').append(" (<?php echo commify($count)?> total)")
};
</script>
<table class="hoverTable">
<thead class="ui-widget-header">
  <tr>
    <th width="5%" style="text-align:left"></th>
    <th width="45%" style="text-align:left">Windows Event Id</th>
    <th width="25%" style="text-align:left">Seen</th>
    <th width="25%" style="text-align:left">Last Seen</th>
  </tr>
</thead>
  <tbody>
<?php
        $sql = "SELECT * FROM (SELECT * FROM snare_eid where eid>0 and hidden='false' ORDER BY lastseen DESC) AS result LIMIT ". $_SESSION['PORTLET_EID_LIMIT']; 
        $result = perform_query($sql, $dbLink, "portlet-snare_eid.php"); 
        $i=0; 
        while($row = fetch_array($result)) { 
        echo "<tr>";
        echo "<td id='eid_sel'>";
        echo "<input type=\"checkbox\" name=\"sel_eid[]\" value=\"$row[eid]\" id='$row[eid]'";
        echo "</td>";
        echo "<td id='eid'>";
        if (strlen($row['eid']) < 26) {
            echo "$row[eid]";
        } else {
            if (strlen($row['eid']) > 39) {
                echo "<span style=\"font-size: xx-small\">$row[eid]</span>";
            } else {
                echo "<span style=\"font-size: x-small\">$row[eid]</span>";
            }
        }
        echo "</td>";
        echo "<td id='seen'>";
        echo humanReadable($row['seen']) . " times\n";
        echo "</td>";
        echo "<td id='lastseen'>";
        echo getRelativeTime($row['lastseen']) . "\n";
        echo "</td>";
        echo "</tr>";
            $i++; 
        } 
        echo "</tbody>";
        echo "</table>";
} else { 
    echo "<b><u>No Windows Event ID's</u></b><br>";
} 
?>

<!-- BEGIN EventIds Selector Modal -->
<div class="dialog_hide">
    <div id="eid_dialog" title="EventId Selector">
          <?php require ($basePath . "/../grid/snare_eid.php");?> 
    </div>
</div>
<!-- END EventIds Selector Modal -->

<?php
} else { ?>
<script type="text/javascript">
$('#portlet_Snare_EventId').remove()
</script>
<?php } ?>
