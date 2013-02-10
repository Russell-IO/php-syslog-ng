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
if ((has_portlet_access($_SESSION['username'], 'Severities') == TRUE) || ($_SESSION['AUTHTYPE'] == "none")) {
$dbLink = db_connect_syslog(DBADMIN, DBADMINPW);
?>
<table border="0" width="100%">
    <tr>
        <td width="70%">
            <select name="severities[]" id="severities" multiple size=5>
            <?php
            $sql = "SELECT * FROM severities ORDER BY code DESC";
            $queryresult = perform_query($sql, $dbLink, $_REQUEST['pageId']);
            while ($line = fetch_array($queryresult)) {
   	            $severity = $line['name'];
   	            echo "<option value=".$line['code'].">".htmlentities($severity)."</option>\n";
            }
            ?>
            </select>
        </td>
    </tr>
</table>
<?php } else { ?>
<script type="text/javascript">
$('#portlet_Severities').remove()
</script>
<?php } ?>
