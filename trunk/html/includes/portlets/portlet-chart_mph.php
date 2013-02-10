<?php

/*
 *
 * Developed by Clayton Dukes <cdukes@logzilla.pro>
 * Copyright (c) 2009 gdd.net
 * All rights reserved.
 *
 * Changelog:
 * 2010-03-05 - created
 *
 */

session_start();
$basePath = dirname( __FILE__ );
require_once ($basePath . "/../common_funcs.php");
if ((has_portlet_access($_SESSION['username'], 'Messages Per Hour') == TRUE) || ($_SESSION['AUTHTYPE'] == "none")) { 
?>
<div id="chart_mph"></div>
<?php } else { ?>
<script type="text/javascript">
$('#portlet_Messages_Per_Hour').remove()
</script>
<?php } ?>
