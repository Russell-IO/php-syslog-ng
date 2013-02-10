<?php

/*
 * grid/snare_eid.php
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
$grid->SelectCommand = "SELECT eid AS EventId, seen AS Seen, lastseen AS LastSeen FROM snare_eid where eid>0 and hidden='false'";
// set the ouput format to json
$grid->dataType = 'json';
// Let the grid create the model
$grid->setColModel();
// Set the url from where we obtain the data
$grid->setUrl('includes/grid/snare_eid.php');
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

$grid->navigator = true;
$grid->setNavOptions('navigator', array("pdf"=>true,"excel"=>true,"add"=>false,"edit"=>false,"del"=>false,"view"=>false, "search"=>true));

$gridComplete = <<<ONCOMPLETE
	function ()
	{	
 setRememberedCheckboxesForDialog('snare_EventId','gbox_eidgrid',12,'portlet-content_Snare_EventId'); 
	}
ONCOMPLETE;
$grid->setGridEvent('loadComplete', $gridComplete); 
$custom = <<<CUSTOM

//---------------------------------------------------------------
// BEGIN: EventId Select Dialog
//---------------------------------------------------------------
$("#portlet-header_Snare_EventId .ui-icon-search").click(function() {
    $("#eid_dialog").dialog({
                bgiframe: true,
                resizable: false,
                height: '600',
                width: '90%',
                position: "center",
                autoOpen:false,
                modal: false,
                title: "Windows Event ID Selector",
                overlay: {
                        backgroundColor: '#000',
                        opacity: 0.5
                },     
                buttons: {
                        'Add Selected Event ID': function() {
                                $(this).dialog('close');
                        },
                },
            open: function(event, ui) { 
                       // CDUKES: BEGIN #370 - Checkboxes in extended search doesn't check that in portlet
                       $('#eidgrid > tbody  > tr').each(function() {
                               var id=$(this).attr('id'); // the id here will be the eid
                               if($("#" + id).is(':checked')) {
                               // console.log("Checking box for " + id + " in the grid portlet");
                               // check the eid in the grid
                               $('input[id=jqg_eidgrid_'+ id +']').attr('checked', true);

                               // place at top and highlight
                               // Commented out because the jqgrid insists on resizing the columns and they look crappy!
                               // var row = $('input[id=jqg_eidgrid_'+ id +']').parents("tr:first");
                               // if (row.length) { // if the row is already in the table, just move it up to the top
                               // var firstRow = row.parent().find("tr:first").not(row);
                               // row.insertBefore(firstRow).addClass("TopRow");
                               // $(row).effect("pulsate", { times:1 }, 1000);
                               // }
                               // end place at top

                               } else {
                               // UNcheck the eid on the main page
                               $('input[id=jqg_eidgrid_'+ id +']').attr('checked', false);
                               }
                       });             
                       // END #370 - Checkboxes in extended search doesn't check that in portlet
		//start code(by abani)
		 setRememberedCheckboxesForDialog('snare_EventId','gbox_eidgrid',12,'portlet-content_Snare_EventId'); 
		//end code (by abani)
		$('#eid_dialog').css('overflow','hidden');$('.ui-widget-overlay').css('width','99%') },
            close: function(event, ui) { 
                       // CDUKES: BEGIN #370 - Checkboxes in extended search doesn't check that in portlet
                       $('#eidgrid > tbody  > tr').each(function() {
                               var id=$(this).attr('id'); // the id here will be the eid
                               if($("#jqg_eidgrid_" + id).is(':checked')) {
                               // check the eid on the main page
                               $('input[id='+ id +']').attr('checked', true);

                               // place at top and highlight
                               var row = $('input[id='+ id +']').parents("tr:first");
                               if (row.length) { // if the row is already in the table, just move it up to the top
                               var firstRow = row.parent().find("tr:first").not(row);
                               row.insertBefore(firstRow).addClass("TopRow");
                               // console.log("Row already exists for id " + id);
                               } else { // Add a new row in the main table since it's not there yet
                               // console.log("Adding new row for id: " + id);
                               var addrow = $('input[id=jqg_eidgrid_'+ id +']').parents("tr:first");
                               $('#portlet-content_Snare_EventId > table > tbody:first').prepend(addrow);
                               // make it purty
                               $(addrow).effect("pulsate", { times:1 }, 1000);
                               addrow.removeClass("ui-widget-content jqgrow ui-row-ltr ui-priority-secondary ui-state-highlight");
                               }
                               // end place at top

                               } else {
                               // UNcheck the eid on the main page
                               $('input[id='+ id +']').attr('checked', false);

                               // remove from top placement
                               var row = $('input[id='+ id +']').parents("tr:first");
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
                       });             
                       // END #370 - Checkboxes in extended search doesn't check that in portlet
		//start code(by abani)
		 setRememberedCheckboxes('snare_EventId','portlet-content_Snare_EventId');
		//end code (by abani)
		$('#eid_dialog').css('overflow','auto') }
        });             
        $("#eid_dialog").dialog('open');
        $("#eid_dialog").ready(function(){
        // Some magic to set the proper width of the grid inside a Modal window
        var modalWidth = $("#eid_dialog").width();
        var modalHeight = $("#eid_dialog").height() - 52;
        $('#eidgrid').jqGrid('setGridWidth',modalWidth);
        $('#eidgrid').jqGrid('setGridHeight',modalHeight);
        $('#eidgrid').fluidGrid({base:'#eid_dialog', offset:-25});
        });
//---------------------------------------------------------------
// END: EventId Select Dialog
//---------------------------------------------------------------


});

$(window).resize(function()
{
        $('#eidgrid').fluidGrid({base:'#ui-dialog-title-eid_dialog', offset:-25});
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
        "header_title"=>"                         Snare/Windows Event ID Report"
    ));
} 

// Enjoy
$summaryrows=array("Seen"=>array("Seen"=>"SUM")); 
$grid->renderGrid('#eidgrid','#eidpager',true, $summaryrows, null, true,true);
$conn = null;
?>
