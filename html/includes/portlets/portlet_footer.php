<?php
/*
 * portlets/portlet_footer.php
 *
 * Developed by Clayton Dukes <cdukes@logzilla.pro>
 * Copyright (c) 2011 logzilla.pro
 * All rights reserved.
 *
 * Changelog:
 * 2011-12-16 - created
 *
 */


?>

<script type="text/javascript">
    $("#portlet-header_Graph_Results").prepend('<div id="btn"></div><span class="ui-icon ui-icon-disk"></span>');

$(document).ready(function(){
//---------------------------------------------------------------
// BEGIN: Save URL function
//---------------------------------------------------------------
$(".portlet-header .ui-icon-disk").click(function() {
        var url = '<?php echo $qstring?>';
        $("#div_save_dialog").dialog({
bgiframe: true,
resizable: true,
height: '300',
width: '33%',
autoOpen:false,
modal: false,
open: function() {
$("#url").val(url);
},
overlay: {
backgroundColor: '#000',
opacity: 0.5
},
buttons: {
'Show Full URL': function() {
            var fullurl = '<?php echo myUrl() . $qstring?>';
               $( "<div id='div_show_url'><center><br><br>" + fullurl + "</div></center>" ).dialog({
modal: true,
width: "90%", 
height: 240, 
buttons: {
Ok: function() {
$( this ).dialog( "close" );
}
}
});
},
'Save to Favorites': function() {
$(this).dialog('close');
var urlname = $("#urlname").val();
if (urlname !== '') {
var spanid = '<?php echo $spanid?>';
var page = '<?php echo $page?>';
$.get("includes/ajax/qhistory.php?action=save&url="+ escape(url) +"&urlname="+urlname+"&spanid=" + spanid, function(data){
    notify(data);
    $("#chart_history").append("<li><a href='"+url+"'>" + urlname + "</a></li>\n");
    });
} else {
    warn('Unable to save URL<br>Did you forget to add a name?');
}
},
Cancel: function() {
            $(this).dialog('close');
        }
}
});
$("#div_save_dialog").dialog('open');     
//return false;
});
}); // end doc ready
//---------------------------------------------------------------
// END: Save URL function
//---------------------------------------------------------------
</script>

<script type="text/javascript">
//---------------------------------------------------------------
// BEGIN: PDF and Excel Export
//---------------------------------------------------------------
$(document).ready(function(){
$("#export").click(function() {
        $("#div_export").dialog({
            bgiframe: true,
            resizable: false,
            height: '150',
            width: '20%',
            autoOpen:false,
            modal: false,
            open: function() {
            $("#url").val(url);
            },
            overlay: {
            backgroundColor: '#000',
            opacity: 0.5
            },
            buttons: {
            'Generate Report': function() {
            $(this).dialog('close');
            var rpt_type = $("#rpt_type").val();
            var url= "includes/excel.php?rpt_type=" + rpt_type;
            window.location.href=url;
            },
            Cancel: function() {
                        $(this).dialog('close');
                    }
            }
            });
            $("#div_export").dialog('open');     
            //return false;
            });
}); // end doc ready
</script>

<div class="dialog_hide">
    <div id="div_save_dialog" title='Results will be saved to the "Favorites" menu.'>
        <form>
            <fieldset>
                <label for="urlname">Enter a short name for this search:</label>
                <input type="text" name="urlname" id="urlname" class="text ui-widget-content ui-corner-all" />
            </fieldset>
        </thead>
        <tbody>
            <td>
                <br />
                <b>Set the query date range</b>
            </td>
            <td width="90%" colspan="2">
                <div id="lo_date_wrapper">
                    <input type="text" size="25" value="<?php echo $lo_date?>" name="lo_date" id="lo_date">
                </div>
            </td>
        </tr>
    </tbody>
</table>
        </form>
    </div>
</div>
<div class="dialog_hide">
    <div id="div_export" title='Select Report Type'>
        <form>
        <table style="width: 100%;" border="0">
        <td width="50%">
        Export Results to 
        </td>
        <td width="50%">
            <select style="width: 100%"; id="rpt_type">
            <option selected value="xls">Excel</option>
            <option value="xml">Excel 2007 (xml)</option>
            <option value="csv">CSV</option>
            <!-- Removed for now - too ugly! <option value="pdf">Adobe PDF</option>-->
            </select>
            </td>
            </table>
        </form>
    </div>
</div>
