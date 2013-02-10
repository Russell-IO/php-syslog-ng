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
if ((has_portlet_access($_SESSION['username'], 'Facilities') == TRUE) || ($_SESSION['AUTHTYPE'] == "none")) { 
$dbLink = db_connect_syslog(DBADMIN, DBADMINPW);

?>
<table border="0" width="100%">
    <thead>
        <tr>
            <th width="100%"></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>
                <select name="facilities[]" id="facilities" multiple size=5>
                    <?php
                    $sql = "SELECT * FROM facilities ORDER BY code DESC";
                    $queryresult = perform_query($sql, $dbLink, $_REQUEST['pageId']);
                    while ($line = fetch_array($queryresult)) {
                    $facility = $line['name'];
                    echo "<option value=".$line['code'].">".htmlentities($facility)."</option>\n";
                    }
                    ?>
                </select>
            </td>
        </tr>
    </tbody>
</table>
<?php } else { ?>
<script type="text/javascript">
    $('#portlet_Facilities').remove()
    </script>
    <?php } ?>
