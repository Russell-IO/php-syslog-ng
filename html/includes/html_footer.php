<?php
// Copyright (C) 2010 Clayton Dukes cdukes@logzilla.pro

//------------------------------------------------------------------------
// Determine how long all this stuff took to generate.
//------------------------------------------------------------------------
$time_end = get_microtime();
$exetime = $time_end - $time_start;

?>
<!-- BEGIN show page execution time -->
<div class="footer" id="footer_wrapper">
<?php
// No need to show execution time if logging out...
/*
   // removed - kinda ugly and doesn't really calculate a real number when ajax is inolved.
   if(strcasecmp($pageId, "login") != 0) {
  echo "Executed in <b>".round($exetime, 4)." seconds</b>\n";
}
*/
?>
</div>
<!-- END show page execution time -->
<!-- END HTML Code -->

<!-- BEGIN Import JS Footer -->
<?php include ("js_footer.php");?>
<!-- END JS Footer -->

<!-- END Body -->
</body></html>
