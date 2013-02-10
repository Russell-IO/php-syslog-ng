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
if ((has_portlet_access($_SESSION['username'], 'Date and Time') == trUE) || ($_SESSION['AUTHTYPE'] == "none")) { 
?>
<table width="100%" border="0">
        <?php if ($_SESSION['DEDUP'] == "1") { ?>
    <tr id="fotr">
        <td width="10%">
            <input type="checkbox" name="fo_checkbox" id="fo_checkbox">
            <b>FO</b>
        </td>
        <td width="90%" COLSPAN="2">
            <div id="fo_date_wrapper">
                <input type="text" size="25" value="<?php echo date("Y-m-d")?>" name="fo_date" id="fo_date">
            </div>
            <!--The fo_time_wrapper div is referenced in jquery.timePicker.js -->
            <div id="fo_time_wrapper"> 
                <input type="text" class="rounded_textbox watermark ui-widget ui-corner-all" name="fo_time_start" id="fo_time_start" size="10" value="00:00:00" /> 
                <input type="text" class="rounded_textbox watermark ui-widget ui-corner-all" name="fo_time_end" id="fo_time_end" size="10" value="23:59:59" />
            </div>
        </td>
    </tr>
    <?php } ?>
    <tr>
        <?php if ($_SESSION['DEDUP'] == "1") { ?>
        <td width="10%">
            <?php } else {?>
            <td width="25%">
                <?php } ?>
                <input type="checkbox" name="lo_checkbox" id="lo_checkbox" checked>
                <?php if ($_SESSION['DEDUP'] == "0") { ?>
                Date/Time
                <input type="hidden" name="lo_checkbox" value="on">
                <?php } else {?>
                <b>LO</b>
                <?php } ?>
            </td>
            <td width="90%" COLSPAN="2">
                <div id="lo_date_wrapper">
                    <input type="text" size="25" value="<?php echo date("Y-m-d")?>" name="lo_date" id="lo_date">
                </div>
                <!--The lo_time_wrapper div is referenced in jquery.timePicker.js -->
                <div id="lo_time_wrapper"> 
                    <input type="text" class="rounded_textbox watermark ui-widget ui-corner-all" name="lo_time_start" id="lo_time_start" size="10" value="00:00:00" /> 
                    <input type="text" class="rounded_textbox watermark ui-widget ui-corner-all" name="lo_time_end" id="lo_time_end" size="10" value="23:59:59" />
                </div>
            </td>
        </tr>
    </table>

<!-- BEGIN Date Picker Functions -->
<script type="text/javascript">
$(document).ready(function(){
	
    if($(window.parent.document).find('iframe').size()){
        var inframe = true;
    }
    var dedup = <?php echo ($_SESSION['DEDUP'])?>; 
    if (dedup == "1") {
        $('#fo_date').daterangepicker({
        arrows: 'true',
        dateFormat: 'yy-mm-dd',
        rangeSplitter: 'to',
        });
    } else {
        $('#fotr').remove()
        $('#trandor').remove()
        $('#lo_checkbox').remove()
        $('#lotext').remove()
    };
    $('#lo_date').daterangepicker({
    arrows: 'true',
    dateFormat: 'yy-mm-dd',
    rangeSplitter: 'to',
    });
}); //end doc ready
</script>
<!-- END Date Picker Functions -->

<!-- BEGIN Time Range Selector -->
<script type="text/javascript" src="includes/js/jquery/plugins/jquery.timePicker.js"></script>
<!-- END Time Range Selector -->

<!-- BEGIN Time Picker Functions -->
<script type="text/javascript">
$(document).ready(function(){
    var dedup = <?php echo ($_SESSION['DEDUP'])?>; 
    if (dedup == "1") {
        $("#fo_time_start, #fo_time_end").timePicker();
        // Store time used by duration.
        var oldTime = $.timePicker("#fo_time_start").getTime();

        // Keep the duration between the two inputs.
        $("#fo_time_start").change(function() {
            // Added '!= '23:59:59'' so the time would not cycle to the next day
            if ($("#fo_time_end").val() != '23:59:59') { // Only update when second input has a value.
            // Calculate duration.
            var duration = ($.timePicker("#fo_time_end").getTime() - oldTime);
            var time = $.timePicker("#fo_time_start").getTime();
            // Calculate and update the time in the second input.
            $.timePicker("#fo_time_end").setTime(new Date(new Date(time.getTime() + duration)));
            oldTime = time;
            }
            });
        // Validate.
        $("#fo_time_end").change(function() {
            if($.timePicker("#fo_time_start").getTime() > $.timePicker(this).getTime()) {
            $(this).addClass("error");
            }
            else {
            $(this).removeClass("error");
            }
            });
        // Clear watermark when the user clicks in the time entry field
        $("#fo_time_start").focus(function() {
            $(this).removeClass("watermark");
            });
        $("#fo_time_end").focus(function() {
            $(this).removeClass("watermark");
            });
    };
        });
$(document).ready(function(){
        $("#lo_time_start, #lo_time_end").timePicker();
        // Store time used by duration.
        var oldTime = $.timePicker("#lo_time_start").getTime();

        // Keep the duration between the two inputs.
        $("#lo_time_start").change(function() {
            // Added '!= '23:59:59'' so the time would not cycle to the next day
            if ($("#lo_time_end").val() != '23:59:59') { // Only update when second input has a value.
            // Calculate duration.
            var duration = ($.timePicker("#lo_time_end").getTime() - oldTime);
            var time = $.timePicker("#lo_time_start").getTime();
            // Calculate and update the time in the second input.
            $.timePicker("#lo_time_end").setTime(new Date(new Date(time.getTime() + duration)));
            oldTime = time;
            }
            });
        // Validate.
        $("#lo_time_end").change(function() {
            if($.timePicker("#lo_time_start").getTime() > $.timePicker(this).getTime()) {
            $(this).addClass("error");
            }
            else {
            $(this).removeClass("error");
            }
            });
        // Clear watermark when the user clicks in the time entry field
        $("#lo_time_start").focus(function() {
            $(this).removeClass("watermark");
            });
        $("#lo_time_end").focus(function() {
            $(this).removeClass("watermark");
            });
        });
</script>
<!-- END Time Picker Functions -->



    <?php } else { ?>
    <script type="text/javascript">
        $('#portlet_Date_and_Time').remove()
        </script>
        <?php } ?>
