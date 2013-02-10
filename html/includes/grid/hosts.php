<?php

/*
 * grid/hostgrid.php
 *
 * Developed by Clayton Dukes <cdukes@logzilla.pro>
 * Copyright (c) 2011 LogZilla, LLC
 * All rights reserved.
 *
 * Changelog:
 * 2011-01-03 - created
 *
 */
define('ABSPATH', dirname(__FILE__).'/');
require_once (ABSPATH . "../common_funcs.php");
$securi = getrbac($_SESSION["username"]); 
if (empty($securi)) { $securi = $_GET["securi"]; }
define('DB_DSN',"mysql:host=".DBHOST.";dbname=".DBNAME);
define('DB_USER', DBADMIN);    
define('DB_PASSWORD', DBADMINPW); 
// include the jqGrid Class
require_once ABSPATH."php/jqGrid.php";
// include the driver class
require_once ABSPATH."php/jqGridPdo.php";
// include pdf
require_once(ABSPATH.'/php/tcpdf/config/lang/eng.php'); 
// Connection to the server
$conn = new PDO(DB_DSN,DB_USER,DB_PASSWORD);
// Tell the db that we use utf-8
$conn->query("SET NAMES utf8");

// Create the jqGrid instance
$grid = new jqGridRender($conn);
// Write the SQL Query
$sel_command = "SELECT host as Host, seen as Seen, lastseen as LastSeen, rbac_key as Rbac_Key  FROM hosts where rbac( ? , rbac_key ) and hidden='false'";
	$grid->SelectCommand = $sel_command;

// set the ouput format to json
$grid->dataType = 'json';
// Let the grid create the model
$grid->setColModel(null, array($securi));
// Set the url from where we obtain the data
$grid->setUrl("includes/grid/hosts.php?securi=".$securi);
// Set some grid options
$grid->setGridOptions(array(
    "rowNum"=>19,
    "sortname"=>"LastSeen",
    "sortorder"=>"desc",
    "altRows"=>true,
    "multiselect"=>true,
    "scrollOffset"=>25,
    "shrinkToFit"=>true,
    "setGridHeight"=>"100%",
    "rowList"=>array(20,40,60,75,100,500,750,1000),
    "loadComplete"=>"js:"
    ));

$grid->setColProperty('Seen', array('width'=>'20','formatter'=>'js:grid_formatSeen'));
$grid->setColProperty('LastSeen', array('width'=>'35','formatter'=>'js:easyDate'));
$grid->setColProperty('Rbac_Key', array('hidden'=>true));

$grid->navigator = true;
$grid->setNavOptions('navigator', array("pdf"=>true,"excel"=>true,"add"=>false,"edit"=>false,"del"=>false,"view"=>false, "search"=>true));

$gridComplete = <<<ONCOMPLETE
	function ()
	{	
setRememberedCheckboxesForDialog('hosts','host_dialog',14,'portlet-content_Hosts'); 

	}
ONCOMPLETE;
$grid->setGridEvent('loadComplete', $gridComplete);
$custom = <<<CUSTOM

//---------------------------------------------------------------
// BEGIN: Host Select Dialog
//---------------------------------------------------------------
$("#portlet-header_Hosts .ui-icon-search").click(function() {
    $("#host_dialog").dialog({
                bgiframe: true,
                resizable: false,
                height: '600',
                width: '90%',
                position: "center",
                autoOpen:false,
                modal: false,
                title: "Host Selector",
                overlay: {
                        backgroundColor: '#000',
                        opacity: 0.5
                },     
                buttons: {
                        'Add Selected Hosts': function() {
                                $(this).dialog('close');
                        },
                },
            open: function(event, ui) { 
		
                       // CDUKES: BEGIN #370 - Checkboxes in extended search doesn't check that in portlet
                       $('#tblHosts > tbody  > tr').each(function() {
                               var id = $(this).find('input:checkbox:first').attr('id');
                                // console.log("Testing the id: " + id + " in the grid");
                               // Note on below: you can just do "is('checked')" here!
                               // because the id may have periods (hostnames) and jquery will barf
                               // so instead of using the id, I just grabbed the status of the first checkbox
                               if ($(this).find('input:checkbox:first').attr('checked') == "checked") {
                               // check the host in the grid
                               $('input[id="jqg_hostsgrid_'+ id +'"]').attr('checked', true);

                               // place at top and highlight
                               // Commented out because the jqgrid insists on resizing the columns and they look crappy!
                               // var row = $('input[id="jqg_hostsgrid_'+ id +'"]').parents("tr:first");
                               // if (row.length) { // if the row is already in the table, just move it up to the top
                               // var firstRow = row.parent().find("tr:first").not(row);
                               // row.insertBefore(firstRow).addClass("TopRow");
                               // $(row).effect("pulsate", { times:1 }, 1000);
                               // }
                               // end place at top

                               } else {
                               // UNcheck the host on the main page
                               $('input[id="jqg_hostsgrid_'+ id +'"]').attr('checked', false);
                               }
                       });             
                       // END #370 - Checkboxes in extended search doesn't check that in portlet

	//start code(by abani)
		setRememberedCheckboxesForDialog('hosts','host_dialog',14,'portlet-content_Hosts'); 
	//end code (by abani)
		$('#host_dialog').css('overflow','hidden');$('.ui-widget-overlay').css('width','99%') 
		},
            close: function(event, ui) {
                       // CDUKES: BEGIN #370 - Checkboxes in extended search doesn't check that in portlet
                       $('#hostsgrid > tbody  > tr').each(function(i) {
                               if (i > 0) { // skip first row (the check all box)
                               var id=$(this).attr('id'); // the id here will be the hostname
                               // console.log("Testing id: " + id + " for checkbox");
                               var test = $(this).find('input:checkbox:first').attr('checked');
                               // Note on below: you can just do "is('checked')" here!
                               // because the id may have periods (hostnames) and jquery will barf
                               // so instead of using the id, I just grabbed the status of the first checkbox
                               if ($(this).find('input:checkbox:first').attr('checked') == "checked") {
                               // console.log("Row id is checked:" + id);
                               // check the host on the main page
                               $('input[id="'+ id +'"]').attr('checked', true);

                               // place at top and highlight
                               var row = $('input[id="'+ id +'"]').parents("tr:first");
                               if (row.length) { // if the row is already in the table, just move it up to the top
                               var firstRow = row.parent().find("tr:first").not(row);
                               row.insertBefore(firstRow).addClass("TopRow");
                               // console.log("Row already exists for id " + id);
                               } else { // Add a new row in the main table since it's not there yet
                                // console.log("Adding new row for id: " + id);
                               var addrow = $('input[id="jqg_hostsgrid_'+ id +'"]').parents("tr:first");
                               $('#portlet-content_Hosts > table > tbody:first').prepend(addrow);
                               // make it purty
                               $(addrow).effect("pulsate", { times:1 }, 1000);
                               addrow.removeClass("ui-widget-content jqgrow ui-row-ltr ui-priority-secondary ui-state-highlight");
                               }
                               // end place at top

                               } else {
                               // UNcheck the host on the main page
                               $('input[id="'+ id +'"]').attr('checked', false);

                               // remove from top placement
                               var row = $('input[id="'+ id +'"]').parents("tr:first");
                                 if (row.hasClass('TopRow')){
                                    var nonTopRows = row.siblings().not('.TopRow');
                                    // console.log(nonTopRows);
                                    var found = false;
                                    nonTopRows.each(function(){
                                        // console.log('rowPos: ' + row.data('pos'));
                                        // console.log('current compare: ' + $(this).data('pos'));
                                        if (row.data('pos')<$(this).data('pos') && !found){
                                            found = true;
                                            row.insertBefore($(this));
                                    }
                                    });
                                    if (!found)
                                        row.appendTo(row.parent());
                                    row.removeClass("TopRow");
                                }
                               // end remove from top placement
                               }
                               }
                       });             
                       // END #370 - Checkboxes in extended search doesn't check that in portlet

 setRememberedCheckboxes('hosts','portlet-content_Hosts');	
 $('#host_dialog').css('overflow','auto') }
        });             
        $("#host_dialog").dialog('open');
        $("#host_dialog").ready(function(){
        // Some magic to set the proper width of the grid inside a Modal window


        var modalWidth = $("#host_dialog").width();
        var modalHeight = $("#host_dialog").height() - 52;
        $('#hostsgrid').jqGrid('setGridWidth',modalWidth);
        $('#hostsgrid').jqGrid('setGridHeight',modalHeight);
        $('#hostsgrid').fluidGrid({base:'#host_dialog', offset:-25});
        });
//---------------------------------------------------------------
// END: Host Select Dialog
//---------------------------------------------------------------


});

$(window).resize(function()
{
        $('#hostsgrid').fluidGrid({base:'#ui-dialog-title-host_dialog', offset:-25});
});


CUSTOM;

$grid->setJSCode($custom);


$oper = jqGridUtils::GetParam("oper");
if($oper == "pdf") {
    $grid->setPdfOptions(array(
        "header"=>true,
        "margin_top"=>25,
        "page_orientation"=>"P",
		"header_logo"=>"../../../../../images/Logo_450x123_24bit_color.jpg",
        // set logo image width
        "header_logo_width"=>45,
        //header title
        "header_title"=>"                         Hosts Report"
    ));
} 

// Enjoy
# $summaryrows=array("Seen"=>array("Seen"=>"SUM")); 
$grid->renderGrid('#hostsgrid','#hostspager',true, null , array($securi), true,true);

$conn = null;
?>
