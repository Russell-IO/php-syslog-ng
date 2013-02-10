<?php
/*
 * portlet-triggers.php
 *
 * Developed by Clayton Dukes <cdukes@logzilla.pro>
 * Copyright (c) 2010 LogZilla, LLC
 * All rights reserved.
 *
 * 2010-12-10 - created
 *
 */

$basePath = dirname( __FILE__ );
require_once ($basePath . "/../common_funcs.php");
if ((has_portlet_access($_SESSION['username'], 'Edit Favorites') == TRUE) || ($_SESSION['AUTHTYPE'] == "none")) { 
?>

<div id="favorites_wrapper">
    <?php require ($basePath . "/../grid/favorites.php");?> 
</div>

<?php } else { ?>
<script type="text/javascript">
$('#portlet_Edit_Favorites').remove()
</script>
<?php } ?>
