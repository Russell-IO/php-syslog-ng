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
if ((has_portlet_access($_SESSION['username'], 'Messages Per Week') == TRUE) || ($_SESSION['AUTHTYPE'] == "none")) { 
?>
<div id="chart_mpw"></div>
<?php } else { ?>
<script type="text/javascript">
$('#portlet_Messages_Per_Week').remove()
</script>
<?php } ?>
