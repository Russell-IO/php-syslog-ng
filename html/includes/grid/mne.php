<?php

/*
 * grid/mne.php
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
$grid->SelectCommand = "SELECT name as Mnemonic, seen as Seen, lastseen as LastSeen FROM mne where hidden='false'";
// set the ouput format to json
$grid->dataType = 'json';
// Let the grid create the model
$grid->setColModel();
// Set the url from where we obtain the data
$grid->setUrl('includes/grid/mne.php');
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
	 setRememberedCheckboxesForDialog('mnemonics','gbox_mnegrid',12,'portlet_Mnemonics');

	}
ONCOMPLETE;
$grid->setGridEvent('loadComplete', $gridComplete); 
$custom = <<<CUSTOM

//---------------------------------------------------------------
// BEGIN: Mnemonic Select Dialog
//---------------------------------------------------------------
$("#portlet-header_Mnemonics .ui-icon-search").click(function() {
    $("#mne_dialog").dialog({
                bgiframe: true,
                resizable: false,
                height: '600',
                width: '90%',
                position: "center",
                autoOpen:false,
                modal: false,
                title: "Mnemonic Selector",
                overlay: {
                        backgroundColor: '#000',
                        opacity: 0.5
                },     
                buttons: {
                        'Add Selected Mnemonic': function() {
                                $(this).dialog('close');
                        },
                },
            open: function(event, ui) { 
                       // CDUKES: BEGIN #370 - Checkboxes in extended search doesn't check that in portlet
                       $('#mnegrid > tbody  > tr').each(function() {
                               var id=$(this).attr('id'); // the id here will be the mnemonic
                               if($("#" + id).is(':checked')) {
                               // console.log("Checking box for " + id + " in the grid portlet");
                               // check the mnemonic in the grid
                               $('input[id=jqg_mnegrid_'+ id +']').attr('checked', true);

                               // place at top and highlight
                               // Commented out because the jqgrid insists on resizing the columns and they look crappy!
                               // var row = $('input[id=jqg_mnegrid_'+ id +']').parents("tr:first");
                               // if (row.length) { // if the row is already in the table, just move it up to the top
                               // var firstRow = row.parent().find("tr:first").not(row);
                               // row.insertBefore(firstRow).addClass("TopRow");
                               // $(row).effect("pulsate", { times:1 }, 1000);
                               // }
                               // end place at top

                               } else {
                               // UNcheck the mnemonic on the main page
                               $('input[id=jqg_mnegrid_'+ id +']').attr('checked', false);
                               }
                       });             
                       // END #370 - Checkboxes in extended search doesn't check that in portlet
	//start code (by abani)
	setRememberedCheckboxesForDialog('mnemonics','gbox_mnegrid',12,'portlet_Mnemonics');
	//end code(by abani)
	$('#mne_dialog').css('overflow','hidden');$('.ui-widget-overlay').css('width','99%') },
            close: function(event, ui) { 
                       // CDUKES: BEGIN #370 - Checkboxes in extended search doesn't check that in portlet
                       $('#mnegrid > tbody  > tr').each(function() {
                               var id=$(this).attr('id'); // the id here will be the mnemonic
                               if($("#jqg_mnegrid_" + id).is(':checked')) {
                               // check the mnemonic on the main page
                               $('input[id='+ id +']').attr('checked', true);

                               // place at top and highlight
                               var row = $('input[id='+ id +']').parents("tr:first");
                               if (row.length) { // if the row is already in the table, just move it up to the top
                               var firstRow = row.parent().find("tr:first").not(row);
                               row.insertBefore(firstRow).addClass("TopRow");
                               } else { // Add a new row in the main table since it's not there yet
                               // console.log("Adding new row");
                               var addrow = $('input[id=jqg_mnegrid_'+ id +']').parents("tr:first");
                               $('#portlet-content_Mnemonics > table > tbody:first').prepend(addrow);
                               // make it purty
                               $(addrow).effect("pulsate", { times:1 }, 1000);
                               addrow.removeClass("ui-widget-content jqgrow ui-row-ltr ui-priority-secondary ui-state-highlight");
                               }
                               // end place at top

                               } else {
                               // UNcheck the mnemonic on the main page
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
		setRememberedCheckboxes('mnemonics','portlet_Mnemonics');
		//end code(by abani)
		$('#mne_dialog').css('overflow','auto') }
        });             
        $("#mne_dialog").dialog('open');
        $("#mne_dialog").ready(function(){
        // Some magic to set the proper width of the grid inside a Modal window
        var modalWidth = $("#mne_dialog").width();
        var modalHeight = $("#mne_dialog").height() - 52;
        $('#mnegrid').jqGrid('setGridWidth',modalWidth);
        $('#mnegrid').jqGrid('setGridHeight',modalHeight);
        $('#mnegrid').fluidGrid({base:'#mne_dialog', offset:-25});
        });
//---------------------------------------------------------------
// END: Mnemonic Select Dialog
//---------------------------------------------------------------


});

$(window).resize(function()
{
        $('#mnegrid').fluidGrid({base:'#ui-dialog-title-mne_dialog', offset:-25});
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
        "header_title"=>"                         Mnemonics Report"
    ));
} 

// Enjoy
$summaryrows=array("Seen"=>array("Seen"=>"SUM")); 
$grid->renderGrid('#mnegrid','#mnepager',true, $summaryrows, null, true,true);
$conn = null;
?>
