<?php

/*
 *
 * Developed by Clayton Dukes <cdukes@logzilla.pro>
 * Copyright (c) 2009 gdd.net
 * All rights reserved.
 *
 * Changelog:
 * 2009-12-13 - created
 *
 */
session_start();
$basePath = dirname( __FILE__ );
require_once ($basePath . "/../common_funcs.php");
if ((has_portlet_access($_SESSION['username'], 'Hosts') == TRUE) || ($_SESSION['AUTHTYPE'] == "none")) { 
$dbLink = db_connect_syslog(DBADMIN, DBADMINPW);

// dirty rbac fix for AUTHTYPE = "none"
if ($_SESSION['AUTHTYPE'] == "none") $_SESSION['rbac'] = 4294967295;


$sql = "SELECT COUNT(*) FROM (SELECT host FROM hosts where rbac(".$_SESSION['rbac'].", rbac_key) and hidden='false') AS result";
$result = perform_query($sql, $dbLink, $_REQUEST['pageId']);
$total = mysql_fetch_row($result);
$count = $total[0];
// echo "<pre>";
// echo(print_r($_COOKIE));
// echo "</pre>";
// die();
if( $count >0 ) { 
?>
<script type="text/javascript">
var limit = <?php echo ($_SESSION['PORTLET_HOSTS_LIMIT'])?>;
var cnt = <?php echo $count?>;
if (cnt < 11) {
    $('#portlet-header_Hosts').text("Last " + cnt + " Hosts");
    } else {
    $('#portlet-header_Hosts').text("Last " + limit + " Hosts");
    $('#portlet-header_Hosts').append(" (<?php echo commify($count)?> total)")
};
</script>
<table class="hoverTable" id="tblHosts">
<thead class="ui-widget-header">
  <tr>
    <th width="5%" style="text-align:left"></th>
    <th width="45%" style="text-align:left">Host</th>
    <th width="25%" style="text-align:left">Seen</th>
    <th width="25%" style="text-align:left">Last Seen</th>
  </tr>
</thead>
  <tbody>
<?php
        $sql = "SELECT * FROM (SELECT * FROM hosts where rbac(".$_SESSION['rbac'].", rbac_key) and hidden='false' ORDER BY lastseen DESC) AS result LIMIT ". $_SESSION['PORTLET_HOSTS_LIMIT']; 
        $result = perform_query($sql, $dbLink, "portlet-hosts.php"); 
        $i=0; 
        while($row = fetch_array($result)) { 
        echo "<tr>";
        echo "<td id='host_sel'>";
        echo "<input type=\"checkbox\" name=\"sel_hosts[]\" id=\"$row[host]\" value=\"$row[host]\"";
        echo "</td>";
        echo "<td id='host'>";
          echo "$row[host]";
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
    echo "<b><u>No Hosts</u></b><br>";
    echo "Possible causes:<br>";
    echo "<li>No hosts in the system yet  - is your syslog daemon running? Has your admin enabled udp in the syslog config?<br>";
    echo "<li>Verify that " . $_SESSION['username'] . " has proper access. Note that new users are denied access to all hosts until the admin allows them.\n<br>";

} 
?>

<!-- BEGIN Large Host Selector Modal -->
<div class="dialog_hide">
    <div id="host_dialog" title="Host Selector">
          <?php require ($basePath . "/../grid/hosts.php");?> 
    </div>
</div>
<!-- END Large Host Selector Modal -->


<?php
} else { ?>
<script type="text/javascript">
$('#portlet_Hosts').remove()
</script>
<?php } ?>
