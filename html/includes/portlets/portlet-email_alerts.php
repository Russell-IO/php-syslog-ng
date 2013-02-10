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
if ((has_portlet_access($_SESSION['username'], 'Email Alerts') == TRUE) || ($_SESSION['AUTHTYPE'] == "none")) { 
?>

<div id="email_alerts_wrapper">
    <?php require ($basePath . "/../grid/email_alerts.php");?> 
</div>

<script type="text/javascript">
$("#portlet-header_Email_Alerts").prepend('<div id="btn"></div><span class="ui-icon ui-icon-disk"></span>');

//---------------------------------------------------------------
// BEGIN: Save URL function
//---------------------------------------------------------------
// [[ticket:328]] Added doc ready
$(document).ready(function(){
$(".portlet-header .ui-icon-disk").click(function() {
    $("#syslog_hup").dialog({
                        bgiframe: true,
                        resizable: true,
                        height: 'auto',
                        width: '20%',
                        autoOpen:false,
                        modal: true,
                        open: function() {
                         },
                        overlay: {
                                backgroundColor: '#000',
                                opacity: 0.5
                        },
                        buttons: {
                                'Yes, I\'m sure': function() {
                                        $(this).dialog('close');
                                        $.get("includes/ajax/sighup.php", function(data){
                                            notify(data);
                                           });
                                },
                                Cancel: function() {
                                        $(this).dialog('close');
                                }
                        }
                });
                $("#syslog_hup").dialog('open');     
                //return false;
     });
}); // end doc ready
//---------------------------------------------------------------
// END: Save URL function
//---------------------------------------------------------------
</script>

<div class="dialog_hide">
    <div id="syslog_hup" title='Save Email Alerts'>
    This will RESTART your syslog-ng daemon, are you sure?
    </div>
</div>

<?php } else { ?>
<script type="text/javascript">
$('#portlet_Email_Alerts').remove()
</script>
<?php } ?>
