<?php

/*
 *
 * Developed by Clayton Dukes <cdukes@logzilla.pro>
 * Copyright (c) 2010 LogZilla, LLC
 * All rights reserved.
 * Last updated on 2010-06-15
 *
 * Changelog:
 * 2009-12-08 - created
 *
 */

//------------------------------------------------------------------------------
// All javascript code should go in this file
// This allows you to place the code in the head or the body using an include();
// The recommended method for best performance
// is to place all js code just before the closing </body> tag, 
// but some js may need to be in the head (there's also a js_header.php file)
//------------------------------------------------------------------------------

?>
<script src="includes/notify/jquery.notify.js" type="text/javascript"></script>
<!-- BEGIN Table Sorter -->
<script src="includes/js/tablesort.js" type="text/javascript"></script>
<script src="includes/js/paginate.js" type="text/javascript"></script>
<!-- END Table Sorter -->

<!-- BEGIN Charts -->
<script type="text/javascript" src="includes/js/hc/js/highcharts.js"></script>
<!-- 1b) Optional: the exporting module -->
<!-- disabled because of POSTs to external URL (highcharts.com)
    Please see here: http://www.highcharts.com/documentation/how-to-use#exporting
    <script type="text/javascript" src="includes/js/hc/js/modules/exporting.js"></script>-->
<!-- END Charts -->

<!-- BEGIN Help Modal -->
<div class="dialog_hide">
    <div id="help_dialog" title="Help">
            <span id="help_text" class="text ui-corner-all"></span>
    </div>
</div>
<!-- END Help Modal -->

<!-- BEGIN JQuery Portlets -->
<script type="text/javascript">
$(document).ready(function(){
		$(".column").sortable({
		connectWith: ".column",
        opacity: "0.8",
		update: savelayout
		});
		$(".portlet").addClass("ui-widget ui-widget-content ui-helper-clearfix ui-corner-all")
	   	.find(".portlet-header")
	   	.addClass("ui-widget-header ui-corner-all")
	   	// .prepend('<span class="ui-icon ui-icon-plusthick"></span>')
	   	// Future - for release 4.5?: .prepend('<span class="ui-icon ui-icon-help"></span><span class="ui-icon ui-icon-carat-2-n-s"></span>')
	   	.prepend('<span class="ui-icon ui-icon-help"></span>')
	   	.end()
	   	.find(".portlet-content");
        //---------------------------------------------------------------
        // BEGIN: Context sensitive help
        //---------------------------------------------------------------
        $(".portlet-header .ui-icon-help").click(function() {
            var pname = $(this).closest("div").attr("id");
            pname = pname.replace("portlet-header_", "");
            pname = pname.replace(/_/g, " ");
            $("#help_dialog").dialog({
                        bgiframe: true,
                        resizable: true,
                        height: '600',
                        width: '80%',
                        position: [100,100],
                        autoOpen:false,
                        modal: false,
                        show: "slide",
                        hide: "clip",
                        title: pname+ " help",
                        open: function() {
                           $.get("includes/ajax/json.help.php?pname="+pname, function(data){
                               if (data !== '') {
                         $("#help_text").html(data);
                         } else {
                         $("#help_text").text("No Help Available");
                         }
                           });
                         },
                        overlay: {
                                backgroundColor: '#000',
                                opacity: 0.5
                        },
                        buttons: {
                                'Close': function() {
                                        $(this).dialog('close');
                                },
                        }
                });
                $("#help_dialog").dialog('open');     
		   	});
        //---------------------------------------------------------------
        // END: Context sensitive help
        //---------------------------------------------------------------
        // removed to fix bug id #248
        // not sure what the implication of removing this is though :-(
 		// $(".column").disableSelection();
}); // end doc ready
function savelayout(){
	var positions = "";
	var rowindex = 0;
	$(".portlet").each(function(){rowindex++;positions+=(this.id + "=" + this.parentNode.id + "|" + rowindex + "&");});
	$.ajax({
		type: "POST",
		url: "includes/savelayout.php",
		data: positions
});
}
</script>
<!-- END JQuery Portlets -->

<!-- BEGIN JQuery Multiselect Filter -->
<script src="includes/js/jquery/plugins/jquery.multiselect.min.js" type="text/javascript"></script>
<script src="includes/js/jquery/plugins/jquery.multiselect.filter.min.js" type="text/javascript"></script>
<!-- END JQuery Multiselect Filter -->

<!-- BEGIN menu -->
<script type="text/javascript" src="includes/js/jquery/menu.js"></script>
<!-- END menu -->

<!-- BEGIN jqgrid resize -->
<script type="text/javascript" src="includes/js/jquery/plugins/jquery.jqGrid.fluid.js"></script>
<!-- END jqgrid resize -->

<!-- BEGIN Selectbox -->
<script type="text/javascript" src="includes/js/jquery/plugins/jquery.selectbox-1.2.js"></script>
<!-- END Selectbox -->

<!-- BEGIN Timeago -->
<script type="text/javascript" src="includes/js/jquery/plugins/jquery.timeago.js"></script>
<!-- END Timeago -->

<!-- BEGIN Cookies -->
<script type="text/javascript" src="includes/js/jquery/jquery.cookie.js"></script>
 <!--END Cookies -->

<!-- BEGIN Chosen -->
<script type="text/javascript" src="includes/js/jquery/plugins/chosen.jquery.min.js"></script>
<!-- END Chosen -->

<!-- BEGIN Datatable -->
<script type="text/javascript" src="includes/js/jquery/plugins/datatables/js/jquery.dataTables.min.js"></script>
<!-- END Datatable -->

<!-- BEGIN Tabs -->
<script type="text/javascript">
$(document).ready(function(){
        var $tabs = $('#tabs').tabs({
        tabTemplate: '<li><a href="#{href}">#{label}</a> <span class="ui-icon ui-icon-close">Remove Tab</span></li>'
});

// close icon: removing the tab on click
// note: closable tabs gonna be an option in the future - see http://dev.jqueryui.com/ticket/3924
$('#tabs span.ui-icon-close').live('click', function() {
        var index = $('li',$tabs).index($(this).parent());
        // var tabName =  $("#tabs").find("li>a").eq(index).attr("href") 
        var tabName = $("#tabs>ul>li>a").eq(index).attr("href")
        $(tabName).css("display","none");
        $("a[href^="+ tabName +"]").parent().remove();
        var sindex = getSelectedTabIndex();
        // if the user is on the same tab he/she deletes, then select the previous tab
        if ( index == sindex ) {
        $("#tabs").tabs('select',index-1);
        } else {
        // else, stay on the selected tab
        $("#tabs").tabs('select',sindex);
        }
        tabName = tabName.replace("#tab-", "");
        tabName = tabName.replace(/_/g, " ");
        notify('Deleted the ' + tabName + ' tab');
        });
});

$(function() {
        //-----------------------------------------------------------------------------------
        // BEGIN Tab Bookmarking
        // This allows someone to email you a direct URL to a specific tab
        // Code borrowed from http://www.insideria.com/2009/03/playing-with-jquery-tabs.html
        $("#tabs").bind('tabsselect', function(event, ui) {
            document.location='#'+(ui.index+1);
            });
        if(document.location.hash!='') {
        //get the index
        indexToLoad = document.location.hash.substr(1,document.location.hash.length);
        $("#tabs").tabs('select',indexToLoad-1);
        }
        // END Tab Bookmarking
        //-----------------------------------------------------------------------------------
});
//-----------------------------------------------------------------------------------
// BEGIN Functions to add and remove tabs
// This really just hides them
// If I remove them using the tabs(remove) function, I can't get the content back
// because it destroys it completely.
//-----------------------------------------------------------------------------------
function getIndexForId( tabsDivId, searchedId )
{
        var index = -1;
        var i = 0, els = $(tabsDivId).find("a");
        var l = els.length, e;

        while ( i < l && index == -1 )
        {
                e = els[i];

                if ( searchedId == $(e).attr('href') )
                {
                        index = i;
                }
                i++;
        };

        return index;
} 
function getSelectedTabIndex() { 
    return $('#tabs').tabs('option', 'selected');
}
function addTabFromFile(fileName, tabName)
{
    $("#tabs").tabs("add", fileName , tabName);

}
// http://blog.qumsieh.ca/2009/10/27/building-jquery-tabs-that-open-close/
function showTab(tabID, tabName)
{
    tabID = tabID.replace(/ /g, "_");
    var index = getIndexForId('#tabs', '#tab-' + tabID);
    if (index != "-1") {
    // if the tab was removed, re-add it so we can show the results
    $("#tabs").tabs("select",index);
    notify(tabName + ' tab already exists...');
    } else {
    // this will add a tab via the standard method
    $("#tabs").tabs("add","#tab-" + tabID, tabName);
    $("#tab-" + tabID).css("display","list-item");

    $("#tabs").tabs('select', tabID);
    notify('Added the ' + tabName + ' tab');
    }
}

function hideTab(tabName) 
{
    $("#tab-" + tabName).css("display","none");
    $("a[href^=#tab-"+ tabName +"]").parent().remove();
    $("#tabs").tabs('select', 0);
}

//-----------------------------------------------------------------------------------
// END Functions to add and remove tabs
//-----------------------------------------------------------------------------------
</script>
<!-- END Tabs -->

<!-- BEGIN Overlib (deprecated)
<script src="includes/js/overlib.js" language="Javascript" type="text/javascript"></script>
<DIV id=overDiv	style="Z-INDEX: 1000; VISIBILITY: hidden; POSITION: absolute"></DIV>
-->
<!-- END Overlib -->

<!-- BEGIN Themeswitcher -->
<!-- removed because jquery is hardcoding live URL's...WTF?!?!?
<script type="text/javascript" src="includes/js/jquery/themeswitch.js"></script>
<script type="text/javascript">
$(document).ready(function(){
	   	$('#switcher').themeswitcher();
	   	});
var first = true;

// IE doesn't update the display immediately, so reload the page
function reloadIE(id, display, url) {
   	if (!first && $.browser.msie) {
	   	window.location.href = window.location.href;
   	}
   	first = false;
}
</script>
-->

<!-- BEGIN Select All Checkboxes -->
<script type="text/javascript">
function toggleCheck(status) {
    // This will toggle inputs with a class of "checkbox"
    // Note that there is not 'css' class defined
    // it is just used to ID checkboxes
    $(".checkbox").each( function() {
            $(this).attr("checked",status);
            })
};
function togglePortlet_Groups(status) {
    // This will toggle inputs with a class of "checkbox"
    // Note that there is not 'css' class defined
    // it is just used to ID checkboxes
    $(".chk_portlet_groups").each( function() {
            $(this).attr("checked",status);
            })
};
function toggleChecked(oElement) 
{ 
    oForm = oElement.form; 
    oElement = oForm.elements[oElement.name]; 
    if(oElement.length) 
    { 
        bChecked = oElement[0].checked; 
        for(i = 1; i < oElement.length; i++) 
            oElement[i].checked = bChecked; 
    } 
}

function toggleController(oElement)
{
    oForm=oElement.form;oElement=oForm.elements[oElement.name];
    if(oElement.length)
    {
        bChecked=true;nChecked=0;for(i=1;i<oElement.length;i++)
            if(oElement[i].checked)
                nChecked++;
        if(nChecked<oElement.length-1)
            bChecked=false;
        oElement[0].checked=bChecked;
    }
}
</script>
<!-- END Select All Checkboxes -->

<!-- BEGIN Commify -->
<script type="text/javascript">
function commify(nStr)
{
    nStr += '';
    x = nStr.split('.');
    x1 = x[0];
    x2 = x.length > 1 ? '.' + x[1] : '';
    var rgx = /(\d+)(\d{3})/;
    while (rgx.test(x1)) {
        x1 = x1.replace(rgx, '$1' + ',' + '$2');
    }
    return x1 + x2;
}
</script>
<!-- END Commify -->

<!-- BEGIN Watermark Function -->
<script type="text/javascript">
//-----------------------------------------------------------
// The following function is used to set a watermark
// in various text inputs.
// usage: watermark("#id_of_target_text_input","Text To Enter Into Field");
// sample input box to use this on:
// <input type="text" value="Host Filter" class="rounded_textbox watermark ui-widget ui-corner-all" 
//    name="hostsFilter" id="hostsFilter" size=30>
//-----------------------------------------------------------

// This function removes the watermark when the field is clicked in
function watermark(target, value) {
    // Set focus
    $(target).focus(function() {
            $(this).filter(function() {
                // We only want this to apply if there's not
                // something actually entered
                return $(this).val() == "" || $(this).val() == value
                }).removeClass("watermark").val("");
            });
// This function adds the watermark when the focus is lost
    $(target).blur(function() {
            $(this).filter(function() {
                // We only want this to apply if there's not
                // something actually entered
                return $(this).val() == ""
                }).addClass("watermark").val(value);
            });
}
// Apply watermarks to various text fields
$(document).ready(function() {
// watermark("#hostsFilter","Host Filter");
});
</script>
<!-- END Watermark Function -->

<!-- BEGIN Portal Counts -->
<script type="text/javascript">
function humanReadable(size) {  
    var suffix = ['','K','M','B','T','P','E','Z','Y'], 
        tier = 0;   while(size >= 1024) {
            size = size / 1000;
            tier++; }
            return Math.round(size * Math.pow(10, 2)) / Math.pow(10, 2) + suffix[tier];
}
$(document).ready(function() {
        var enabled = <?php print $_SESSION['SHOWCOUNTS']; ?>;
        var SPX = <?php print $_SESSION['SPX_ENABLE']; ?>;
        if (enabled == "1") {
        $.get("includes/ajax/counts.php?data=msgs", function(data){
           if(data.search(/^\d+/) != -1) {
            $("#portlet-header_Messages").append(' ('+humanReadable(data)+" total)");
                } else {
				error(data);
                };
            });
        };
        var count = $("#facilities option").size()
            $("#portlet-header_Facilities").prepend(commify(count)+" ");
        var count = $("#severities option").size()
            $("#portlet-header_Severities").prepend(commify(count)+" ");
        watermark("#dupcount","0");
});
</script>
<!-- END Portal Counts -->

<script type="text/javascript">
//---------------------------------------------------------------
// BEGIN: Submit Buttons
//---------------------------------------------------------------
$("#portlet-header_Hosts").prepend('<a href="#"><span class="ui-icon ui-icon-search"></span></a>');
$("#portlet-header_Mnemonics").prepend('<a href="#"><span class="ui-icon ui-icon-search"></span></a>');
$("#portlet-header_Snare_EventId").prepend('<a href="#"><span class="ui-icon ui-icon-search"></span></a>');
$("#portlet-header_Programs").prepend('<a href="#"><span class="ui-icon ui-icon-search"></span></a>');
// **Special** - this will append to the search form on the main page for any checkboxes clicked on the hosts grid
$("#btnSearch").click( function() { 
        $(this).effect('explode');
        var hosts = $("#hostsgrid").jqGrid('getGridParam','selarrrow'); 
        var mne = $("#mnegrid").jqGrid('getGridParam','selarrrow'); 
        var eid = $("#eidgrid").jqGrid('getGridParam','selarrrow');
        var prg = $("#prggrid").jqGrid('getGridParam','selarrrow');
        if (hosts) {
        $("#results").append("<input type='hidden' name='hosts' value='"+hosts+"'>");
        }
        $("#results").append("<input type='hidden' name='mnemonics' value='"+mne+"'>");
        if (prg) {
        $("#results").append("<input type='hidden' name='programs' value='"+prg+"'>");
        }
        if (eid) {
        $("#results").append("<input type='hidden' name='eids' value='"+eid+"'>");
        }
        if (mne) {
        $("#results").append("<input type='hidden' name='page' value='Results'>");
        }
        }); 
$("#btnGraph").click( function() { 
        $(this).effect('explode');
        var hosts = $("#hostsgrid").jqGrid('getGridParam','selarrrow'); 
        var mne = $("#mnegrid").jqGrid('getGridParam','selarrrow'); 
        var eid = $("#eidgrid").jqGrid('getGridParam','selarrrow'); 
        var prg = $("#prggrid").jqGrid('getGridParam','selarrrow'); 
        var groupby = $("#groupby").val();
        var chart_type = $("#chart_type").val();
	var limit = $("#limit").find('option[selected]').val();
    // #246 Manually set orderby and order
        $("#results").append("<input type='hidden' name='orderby' value='counter'>");
        $("#results").append("<input type='hidden' name='order' value='DESC'>");
 // alert(limit);
        if (groupby == "" && chart_type == "") {
        $("#results").append("<input type='hidden' name='groupby' value='host_crc'>");
        $("#results").append("<input type='hidden' name='chart_type' value='pie'>");
        $("#results").append("<input type='hidden' name='limit' value='" + limit + "'>");
        $("#results").append("<input type='hidden' name='orderby' value='counter'>");
        }
        if (groupby == "") {
        // [[ticket:431]] set default groupby to host_crc is it is missing
        $("#results").append("<input type='hidden' name='groupby' value='host_crc'>");
        //error("When clicking \"Graph\", you must specify a \"Group By\" in the search options portlet.<br>Please refresh the page and try again or click the ? icon for proper syntax.");
        //$("#results").submit(function (e) {
        //e.preventDefault(); // this will prevent from submitting the form.
        //});   
        }
        if (hosts) {
        $("#results").append("<input type='hidden' name='hosts' value='"+hosts+"'>");
        }
        if (mne) {
        $("#results").append("<input type='hidden' name='mnemonics' value='"+mne+"'>");
        }
        if (eid) {
        $("#results").append("<input type='hidden' name='eids' value='"+eid+"'>");
        }
        if (prg) {
        $("#results").append("<input type='hidden' name='programs' value='"+prg+"'>");
        }
        $("#results").append("<input type='hidden' name='page' value='Graph'>");
        }); 

//---------------------------------------------------------------
// END: Submit Buttons
//---------------------------------------------------------------
</script>
<!-- END Add Save URL icon to search results -->

<!-- BEGIN Severity Filters -->
<script type="text/javascript">
function showAll()
{
    $('#theTable tr').show()
}
function filter(sev)
{
    $('#theTable tr').hide()

$('#theTable tr').each(function() 
{
        if(this.id == sev)
        {
                $(this).show()
        }
});
//     $('#' + sev).show()
$('.HeaderRow').show();
}
</script>
<!-- END Severity Filters -->

<!-- BEGIN Paginator and Table formatter -->
<!--[if IE]>
<style type="text/css">
ul.fdtablePaginater {display:inline-block;}
ul.fdtablePaginater {display:inline;}
ul.fdtablePaginater li {float:top;}
ul.fdtablePaginater {text-align:center;}
table { border-bottom:1px solid #C1DAD7; }
</style>
<![endif]-->

<script type="text/javascript">
var callbackTest = {
        displayTextInfo:function(opts) {
                if(!("currentPage" in opts)) { return; }
                
                var p = document.createElement('p'),
                    t = document.getElementById('theTable-fdtablePaginaterWrapTop'),
                    b = document.getElementById('theTable-fdtablePaginaterWrapBottom');
                
                p.className = "paginationText";    
                p.appendChild(document.createTextNode("Showing page " + opts.currentPage + " of " + Math.ceil(opts.totalRows / opts.rowsPerPage)));
                
                t.insertBefore(p.cloneNode(true), t.firstChild);
                b.appendChild(p);
        }
};
</script>
<!-- END Paginator and Table formatter -->

<!-- BEGIN Clock -->
<script type="text/javascript">
/***********************************************
* Local Time script- Dynamic Drive (http://www.dynamicdrive.com)
* This notice MUST stay intact for legal use
* Visit http://www.dynamicdrive.com/ for this script and 100s more.
***********************************************/

// CDUKES: Modified for use with 24 hour display

var weekdaystxt=["Sun", "Mon", "Tues", "Wed", "Thurs", "Fri", "Sat"]

function showLocalTime(container, servermode, offsetMinutes, displayversion){
if (!document.getElementById || !document.getElementById(container)) return
this.container=document.getElementById(container)
this.displayversion=displayversion
var servertimestring=(servermode=="server-php")? '<?php print date("F d, Y H:i:s", time())?>' : (servermode=="server-ssi")? '<!--#config timefmt="%B %d, %Y %H:%M:%S"--><!--#echo var="DATE_LOCAL" -->' : '<%= Now() %>'
this.localtime=this.serverdate=new Date(servertimestring)
this.localtime.setTime(this.serverdate.getTime()+offsetMinutes*60*1000) //add user offset to server time
this.updateTime()
this.updateContainer()
}

showLocalTime.prototype.updateTime=function(){
var thisobj=this
this.localtime.setSeconds(this.localtime.getSeconds()+1)
setTimeout(function(){thisobj.updateTime()}, 1000) //update time every second
}

showLocalTime.prototype.updateContainer=function(){
var thisobj=this
if (this.displayversion=="long")
this.container.innerHTML=this.localtime.toLocaleString()
else{
var hour=this.localtime.getHours()
var minutes=this.localtime.getMinutes()
var seconds=this.localtime.getSeconds()
var ampm=(hour>=12)? "PM" : "AM"
var dayofweek=weekdaystxt[this.localtime.getDay()]
// this.container.innerHTML=formatField(hour, 1)+":"+formatField(minutes)+":"+formatField(seconds)+" "+ampm+" ("+dayofweek+")"
this.container.innerHTML=formatField(hour)+":"+formatField(minutes)+":"+formatField(seconds)+" ("+dayofweek+")"
}
setTimeout(function(){thisobj.updateContainer()}, 1000) //update container every second
}

function formatField(num, isHour){
if (typeof isHour!="undefined"){ //if this is the hour field
var hour=(num>12)? num-12 : num
return (hour==0)? 12 : hour
}
return (num<=9)? "0"+num : num//if this is minute or sec field
}
$("#portlet-header_Date_and_Time").replaceWith("<div class=\"portlet-header\" id=\"portlet-header_Date_and_Time\">Current Server Time: <span id=\"timecontainer\"></span></div>");
new showLocalTime("timecontainer", "server-php", 0, "short")

</script>
<!-- END Clock -->

<!-- BEGIN Tip Modal -->
<script type="text/javascript">
<?php if ($_SESSION['TOOLTIP_GLOBAL'] == "1") { ?>
<?php if ($_SERVER["REQUEST_URI"] == $_SESSION['SITE_URL'] . "index.php") { ?>
$(document).ready(function() {  
        $("#tipmodal").dialog({
                        bgiframe: true,
                        resizable: true,
                        height: '400',
                        width: '50%',
                        autoOpen:false,
                        modal: true,
                        open: function() {
                           $.get("includes/ajax/totd.php?action=get", function(data){
                         $("#tiptext").html(data);
                           });
                         },
                        overlay: {
                                backgroundColor: '#000',
                                opacity: 0.5
                        },
                        buttons: {
                                'Next Tip': function() {
                                        $.get("includes/ajax/totd.php?action=get", function(data){
                                            if (data != '') {
                                            $("#tiptext").html(data);
                                            } else {
                                            notify("No more tips available...");
                                            $("#tipmodal").dialog('close');
                                            }
                                           });
                                },
                                'Close': function() {
                                        $(this).dialog('close');
                                },
                                'Disable Tips': function() {
                                        $.get("includes/ajax/totd.php?action=disable", function(data){
                                            notify(data);
                                            $("#tipmodal").dialog('close');
                                           });
                                },
                        }
                });
                $("#tipmodal").dialog('open');     
        });
<?php }} ?>
        </script>
<!-- END Tip Modal -->


<!-- BEGIN Load DOM with timeago converter -->
<script type="text/javascript">
$(document).ready(function() {
  $("abbr.timeago").timeago();
});
</script>
<!-- END Load DOM with timeago converter -->

<!-- BEGIN Grid Width/Height functions -->
<script type="text/javascript">
function easyDate (cellValue, options, rowdata)
{
    var t = $.timeago(cellValue);
    var cellHtml = "<span>" + t + "</span>";
    return cellHtml;
}
function grid_formatSeen (cellValue, options, rowdata)
{
    var suffix = ['','K','M','B','T','P','E','Z','Y'], 
        tier = 0;   while(cellValue >= 1000) {
            cellValue = cellValue / 1000;
            tier++; }
            return Math.round(cellValue * Math.pow(10, 2)) / Math.pow(10, 2) + suffix[tier] + " times";
}

function setWidth(percent){
        screen_res = ($(window).width())*0.99;
        col = parseInt((percent*(screen_res/100)));
        return col;
};
function setHeight(percent){
        screen_res = ($(window).height())*0.99;
        col = parseInt((percent*(screen_res/100)));
        return col;
};
</script>
<!-- END Grid Width/Height functions -->

<!-- BEGIN portlet table highlight -->
<script type="text/javascript">
$(document).ready(function(){
        /* no longer used?
    $(".hoverTable input").click(function() {
        if ($(this).attr("checked") == true) {
            $(this).parent().parent().addClass("ui-state-highlight");
        } else {
            $(this).parent().parent().removeClass("ui-state-highlight");
        }
    });
    */
});
</script>
<!-- END portlet table highlight -->
<script type="text/javascript">
$(document).ready(function(){
$("#reset_placeholder").html("<input class='ui-state-default ui-corner-all' type='button' id='btnReset' value='Unlock Portlets'>");
}); // end doc ready
</script>
<!-- End Cookies -->

<!-- Begin Feedback Button -->
<?php if ($_SESSION['FEEDBACK'] == "1") { ?>
<script type="text/javascript">
  (function() {
    var uv = document.createElement('script'); uv.type = 'text/javascript'; uv.async = true;
    uv.src = ('https:' == document.location.protocol ? 'https://' : 'http://') + 'widget.uservoice.com/HyvsbYr0NYlAPK9IiN6BQ.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(uv, s);
  })();
</script>
        <?php
} ?>
<!-- End Feedback Button -->


<script type="text/javascript">
function is_int(input){
  // return typeof(input)=='number'&&parseInt(input)==input;
 return !isNaN(input)&&parseInt(input)==input;
}
</script>

<!-- BEGIN Check browser width -->
<script type="text/javascript">
$(document).ready(function(){
        screen_res = $(window).width();
        if(screen_res < 1200) {
                $( "<div id='help_dialog'><center>LogZilla works best with a browser width of 1200 pixels or more (yours is set to " + screen_res + ").<br /></center></div>" ).dialog({
modal: true,
width: "50%", 
height: 240, 
buttons: {
Ok: function() {
$( this ).dialog( "close" );
}
}
});
                }
}); // end doc ready
</script>
<!-- END Check browser width -->

<!-- BEGIN Cookies  this include MUST be at the bottom!-->
<script type="text/javascript" src="includes/cookie_Remember.js"></script>
<!-- END Cookies -->

<!-- BEGIN Multiselect (this MUST be after the Cookie js above!-->
<script type="text/javascript"> 
$(document).ready(function(){
        var arr = new Array("#orderby","#order","#limit","#groupby","#chart_type","#tail","#show_suppressed", "#dupop");
        for(var i=0; i<arr.length; i++) {
        $(arr[i]).multiselect({
            show: ["blind", 200],
            hide: ["drop", 200],
            selectedList: 1,
            multiple: false,
            noneSelectedText: 'Select',
        });
        }
}); // end doc ready
</script>
<script type="text/javascript"> 
    $(document).ready(function(){
            $("#facilities").multiselect({
                show: ["blind", 200],
                hide: ["drop", 200],
                selectedList: 2,
                noneSelectedText: 'Select one or more facilities...',
                open: function(event, ui) {
                    var w = $("#facilities").next().width();
                    $(".ui-multiselect-menu").css("width",w);
                },
            });
// special: find next button (because the multiselect changes selects to buttons) and resize it to fit the portlet
$("#facilities").next().css("position","relative").css("width","100%");
}); // end doc ready
</script>
<script type="text/javascript"> 
    $(document).ready(function(){
            $("#severities").multiselect({
                show: ["blind", 200],
                hide: ["drop", 200],
                noneSelectedText: 'Select one or more severities...',
                selectedList: 2,
                open: function(event, ui) {
                    var w = $("#severities").next().width();
                    $(".ui-multiselect-menu").css("width",w);
                },
            });
// special: find next button (because the multiselect changes selects to buttons) and resize it to fit the portlet
$("#severities").next().css("position","relative").css("width","100%");
}); // end doc ready
</script>
<!-- END Multiselect (this MUST be after the Cookie js above!-->

<!-- BEGIN Notification bars -->
<script type="text/javascript">
function notify( message ){
$('#notifyBar').show();
var template = "notifyBar";
var vars = { title:'Information', text:'<br />' +message, icon:'includes/notify/info.png' };
var opts = { expires:false };
    $container = $("#notify-container").notify();
    $container.notify("create", template, vars, opts);
}
function warn( message ){
var template = "notifyBar";
var vars = { title:'Warning!', text:'<br />' +message, icon:'includes/notify/alert.png' };
var opts = { expires:false };
    $container = $("#notify-container").notify();
    $container.notify("create", template, vars, opts);
}
function question( message ){
var template = "notifyBar";
var vars = { title:'', text:'<br />' +message, icon:'includes/notify/question.png' };
var opts = { expires:false };
    $container = $("#notify-container").notify();
    $container.notify("create", template, vars, opts);
}
function error( message ){
var template = "notifyBar";
var vars = { title:'Error!', text:'<br />' +message, icon:'includes/notify/error.png' };
var opts = { expires:false };
    $container = $("#notify-container").notify();
    $container.notify("create", template, vars, opts);
}
// notify = info (synonym)
var info = notify;
/* Usage:
notify('message');
warn('message');
question('message');
error('message');
*/
// [[ticket:348]] - Portlet admin button clicks not working
</script>
<!-- END Notification bars -->
