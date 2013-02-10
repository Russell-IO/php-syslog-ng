<?php
$basePath = dirname( __FILE__ );
require_once ($basePath . "/../common_funcs.php");
$dbLink = db_connect_syslog(DBADMIN, DBADMINPW);

echo '<select id="sel_hosts" name="sel_hosts[]" multiple="multiple" size="25" style="width:800px">';
$sql = "SELECT * FROM (SELECT host FROM hosts) AS result"; 
$result = perform_query($sql, $dbLink, "portlet-hosts.php"); 
while($row = fetch_array($result)) { 
    echo "<option value=\"$row[host]\">$row[host]</option>";
} 
echo "</select>";
mysql_close($dbLink);
?>
