<?php

/*
 *
 * Developed by Clayton Dukes <cdukes@logzilla.pro>
 * Copyright (c) 2009 gdd.net
 * All rights reserved.
 *
 * Changelog:
 * 2009-12-08 - created
 *
 */


//------------------------------------------------------------------------------
// Only javascript code should go in this file
// This allows you to place the code in the head or the body using an include();
// The recommended method for best performance
// is to place all js code just before the closing </body> tag.
// However, some JS may require head loading...
//------------------------------------------------------------------------------

?>
<!-- BEGIN JQUERY This needs to be first -->
<script type="text/javascript" src="<?php echo $_SESSION['SITE_URL']?>includes/js/jquery/jquery-1.7.1.min.js"></script>
<!-- END JQUERY -->

<!-- BEGIN JqGrid -->
<script src="<?php echo $_SESSION['SITE_URL']?>includes/grid/js/i18n/grid.locale-en.js" type="text/javascript"></script>
<script src="<?php echo $_SESSION['SITE_URL']?>includes/grid/js/jquery.jqGrid.min.js" type="text/javascript"></script>
<script src="<?php echo $_SESSION['SITE_URL']?>includes/grid/js/jquery.jqChart.js" type="text/javascript"></script>
<!-- END JqGrid -->

<!-- BEGIN JQuery UI -->
<script src="<?php echo $_SESSION['SITE_URL']?>includes/js/jquery/ui/js/jquery-ui-1.8.16.custom.min.js" type="text/javascript"></script>
<!-- END JQuery UI -->

<!-- BEGIN Prevent FOUC 
http://www.learningjquery.com/2008/10/1-way-to-avoid-the-flash-of-unstyled-content
http://monc.se/kitchen/152/avoiding-flickering-in-jquery
-->
<script type="text/javascript">
document.write('<style type="text/css">body{display:none}</style>');
jQuery(function($) {
$('body').css('display','block');
});
</script>
<!-- END Prevent FOUC -->

<!-- BEGIN Date Range Selector - moved to head to support event suppression dialog -->
<script type="text/javascript" src="<?php echo $_SESSION['SITE_URL']?>includes/js/jquery/plugins/daterangepicker.jQuery.js"></script>
<script type="text/javascript" src="<?php echo $_SESSION['SITE_URL']?>includes/js/jquery/plugins/date.js"></script>
<!-- END Date Range Selector -->

<!-- BEGIN Context Menu -->
<script type="text/javascript" src="<?php echo $_SESSION['SITE_URL']?>includes/js/jquery/plugins/jquery.ui.position.js"></script>
<script type="text/javascript" src="<?php echo $_SESSION['SITE_URL']?>includes/js/jquery/plugins/jquery.contextMenu.js"></script>
<script type="text/javascript" src="<?php echo $_SESSION['SITE_URL']?>includes/js/jquery/plugins/screen.js"></script>
<script type="text/javascript" src="<?php echo $_SESSION['SITE_URL']?>includes/js/jquery/plugins/prettify.js"></script>
<!-- END Date Context Menu -->

<!-- BEGIN Common -->
<script type="text/javascript" src="includes/js/common_funcs.js"></script>
<!-- END Common -->

<!-- BEGIN Demo Site Tracker -->
<script type="text/javascript"> 
/*
var _gaq = _gaq || [];
_gaq.push(['_setAccount', 'UA-20310964-8']);
_gaq.push(['_trackPageview']);

(function() {
 var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
 ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
 var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
 })();
 */
</script>


