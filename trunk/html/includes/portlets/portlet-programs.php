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
if ((has_portlet_access($_SESSION['username'], 'Programs') == TRUE) || ($_SESSION['AUTHTYPE'] == "none")) {
$dbLink = db_connect_syslog(DBADMIN, DBADMINPW);
// -------------------------
// Get Programs
// -------------------------
$sql = "SELECT COUNT(*) FROM (SELECT crc FROM programs where hidden='false') AS result";
$result = perform_query($sql, $dbLink, $_REQUEST['pageId']);
$total = mysql_fetch_row($result);
$count = $total[0];
if( $count >0 ) { 
?>
<script type="text/javascript">
var limit = <?php echo ($_SESSION['PORTLET_PROGRAMS_LIMIT'])?>;
var cnt = <?php echo $count?>;
if (cnt < 11) {
    $('#portlet-header_Programs').text("Last " + cnt + " Programs");
    } else {
    $('#portlet-header_Programs').text("Last " + limit + " Programs");
    $('#portlet-header_Programs').append(" (<?php echo commify($count)?> total)")
};
</script>
<table class="hoverTable">
<thead class="ui-widget-header">
  <tr>
    <th width="5%" style="text-align:left"></th>
    <th width="45%" style="text-align:left">Program</th>
    <th width="25%" style="text-align:left">Seen</th>
    <th width="25%" style="text-align:left">Last Seen</th>
  </tr>
</thead>
  <tbody>
<?php
        $sql = "SELECT * FROM (SELECT * FROM programs where hidden='false' ORDER BY lastseen DESC) AS result LIMIT ". $_SESSION['PORTLET_PROGRAMS_LIMIT']; 
        $result = perform_query($sql, $dbLink, "portlet-programs.php"); 
        $i=0; 
        while($row = fetch_array($result)) { 
        echo "<tr>";
        echo "<td id='prg_sel'>";
        echo "<input type=\"checkbox\" name=\"sel_prg[]\" value=\"$row[name]\" id='$row[name]'>";
        echo "</td>";
        echo "<td id='prg'>";
        if (strlen($row['name']) < 26) {
            echo "$row[name]";
        } else {
            if (strlen($row['name']) > 39) {
                echo "<span style=\"font-size: xx-small\">$row[name]</span>";
            } else {
                echo "<span style=\"font-size: x-small\">$row[name]</span>";
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
    echo "<b><u>No Programs</u></b><br>";
    echo "Either wait for caches to update, or restart your syslog daemon.\n<br>";
} 
?>

<!-- BEGIN Programs Selector Modal -->
<div class="dialog_hide">
    <div id="prg_dialog" title="Program Selector">
          <?php require ($basePath . "/../grid/prg.php");?> 
            </div>
</div>
<!-- END Programs Selector Modal -->


<?php
} else { ?>
<script type="text/javascript">
$('#portlet_Programs').remove()
</script>
<?php } ?>
