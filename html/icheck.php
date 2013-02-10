<?php
/*
 * icheck.php
 *
 * Developed by Clayton Dukes <cdukes@logzilla.pro>
 * Copyright (c) 2012 logzilla.pro
 * All rights reserved.
 *
 * Changelog:
 * 2012-01-23 - created
 *
 */

session_start();
include_once ("config/config.php");
include_once ("includes/html_header.php");
?>
	<!-- BEGIN JQUERY This needs to be first -->
	<script type="text/javascript" src="includes/js/jquery/jquery-1.7.1.min.js"></script>
	
	<!-- BEGIN JqGrid -->
	<script src="<?php echo $_SESSION['SITE_URL']?>includes/grid/js/i18n/grid.locale-en.js" type="text/javascript"></script>
	<script src="<?php echo $_SESSION['SITE_URL']?>includes/grid/js/jquery.jqGrid.min.js" type="text/javascript"></script>
	<script src="<?php echo $_SESSION['SITE_URL']?>includes/grid/js/jquery.jqChart.js" type="text/javascript"></script>
	<!-- END JqGrid -->
	
	<!-- BEGIN JQuery UI -->
	<script src="<?php echo $_SESSION['SITE_URL']?>includes/js/jquery/ui/js/jquery-ui-1.8.16.custom.min.js" type="text/javascript"></script>
	<!-- END JQuery UI -->
	
	<script type="text/javascript" language="javascript">
	$(document).ready(function()
	{
		$(window).scroll(function()
		{
			$('#message_box').animate({top:$(window).scrollTop()+"px" },{queue: false, duration: 3350});  
		});
		
		$("#license_form").dialog();
		
		$( "#license_form" ).dialog({
			bgiframe: true,
			resizable: true,
			height: 'auto',
			width: '30%',
			autoOpen:true,
			modal: true,
			position: 'center',
			overlay: {
				backgroundColor: '#000',
				opacity: 0.5
			}
    	});
	});
 	</script>

	<style>
		#vworkerSimon-install-button{width: 160px;margin: 0px auto;text-align: center;margin-top: 30px;}
		#vworkerSimon-submit-button, #vworkerSimon-next-button{float: right;margin-top: 8px;width: 60px;text-align: center;}
		#vworkerSimon-submit-button{width:100px;}
		#install-report{color:#f00;}
		#vworkerSimon-install-report{width:100%;}
		.vworkerSimon-btnStyle{ 
		margin: .5em .4em .5em 0;
		cursor: pointer;
		padding: .2em .6em .3em .6em;
		line-height: 1.4em;
		width: 200px;
		overflow: visible;
		padding:10px; display:block; color: #333; height:20px; line-height:20px; text-decoration: none;
		border: 1px solid #D19405;
		background: #FECE2F;
		font-weight: bold;
		color: #4C3000;
		outline: none;
		}
		.clear{clear:both;}
		#message_box {
			position: absolute;
			top: 0; left: 0;
			z-index: 10;
			background:#ffc;
			padding:5px;
			border:1px solid #CCCCCC;
			text-align:center;
			font-weight:bold;
			width:99%;
			font-size:15px; 
			font-family:Trebuchet MS; 
			color: black;
		}
	</style>
<?php 
function ioncube_event_handler($err_code, $params) { 
$support_url = "Please <a href='http://www.logzilla.pro/licensing'> click here </a>to order a new license, or <a href='http://support.logzilla.pro'> here</a> to open ticket with support.";
$refresh_lic = "<a href='".$_SESSION['SITE_URL']."?pageId=License'> Click here </a> to refresh your license from the global server once you have renewed.<br>";
    switch($err_code) { 
        case ION_CORRUPT_FILE: 
            $error = "Your license is corrupt.";
            break; 
        case ION_EXPIRED_FILE: 
            $error = "Your license has expired."; 
            break; 
        case ION_NO_PERMISSIONS: 
            $error = "Invalid Permissions."; 
            break; 
        case ION_CLOCK_SKEW: 
            $error = "Invalid Clock."; 
            break; 
        case ION_UNTRUSTED_EXTENSION: 
            $error = "Untrusted Extension."; 
            break; 
        case ION_LICENSE_NOT_FOUND: 
            $error = $params['license_file'] . " is missing."; 
            break; 
        case ION_LICENSE_CORRUPT: 
            $error = $params['license_file'] . " is corrupt."; 
            break; 
        case ION_LICENSE_EXPIRED: 
            $error = "Expired License."; 
            break; 
        case ION_LICENSE_PROPERTY_INVALID: 
            $error = "Invalid License Property."; 
            break; 
        case ION_LICENSE_HEADER_INVALID: 
            $error = "Invalid License Header."; 
            break; 
        case ION_LICENSE_SERVER_INVALID: 
            $error = "Invalid License Server. Try restarting Apache."; 
            break; 
        case ION_UNAUTH_INCLUDING_FILE: 
            $error = "Unauthorized Including File."; 
            break; 
        case ION_UNAUTH_INCLUDED_FILE: 
            $error = "Unauthorized Include File."; 
            break; 
        case ION_UNAUTH_APPEND_PREPEND_FILE: 
            $error = "Unauthorized append/prepend."; 
            break; 
        default:
            $error = "Invalid License.";
            break;
    } 
    echo "<div id='message_box'>ERROR: $error<br>$support_url<br>$refresh_lic</div>";
} 
?>
