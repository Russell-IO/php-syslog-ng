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
require_once ($basePath . "/../jqNewChart.php");
require_once ($basePath . "/../ajax/ChartFunctions.php");
?>
<div id="chart_mpm"></div>
<?php
if ((has_portlet_access($_SESSION['username'], 'Events Per Minute') == TRUE) || ($_SESSION['AUTHTYPE'] == "none")) { 
	mpm(); 
} else { 
    ?>
<script type="text/javascript">
$('#portlet_Events_Per_Minute').remove()
$(document).ready(function(){
    error("Access Denied");
    });
</script>
<?php } ?>
