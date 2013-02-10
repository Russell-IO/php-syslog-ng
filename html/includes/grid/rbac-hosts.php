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
$grid->setUrl("includes/grid/rbac-hosts.php?securi=".$securi);
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

$grid->setColProperty('Seen', array('width'=>'10'));
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
$("#btn_search_hosts").click(function() {
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
                                // $("#div_hostlist").show('slide', 500);
                                var str = $("#hostsgrid").jqGrid('getGridParam','selarrrow'); 
                                var str = str.toString();
                                var a = str.split(',');
                                $('#btn_search_hosts').text(a.length + " Hosts Currently Selected");
                                /* Was going to list all hosts in a table, but that simply won't scale well.
                                for (var i = 0; i < a.length; i++) {
                              // $('#hostlist').append("<tr><td><span class='ui-icon ui-icon-close'></span></td><td>" + a[i] + "</td></tr>");
                                }
                                */
                                $(this).dialog('close');
                        },
                },
            open: function(event, ui) { 
		
	//start code(by abani)
		setRememberedCheckboxesForDialog('hosts','host_dialog',14,'portlet-content_Hosts'); 
	//end code (by abani)
		$('#host_dialog').css('overflow','hidden');$('.ui-widget-overlay').css('width','99%') 
		},
            close: function(event, ui) {

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
