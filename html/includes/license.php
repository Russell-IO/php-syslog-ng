<?php                   
/* License Downloader       
   */                   
$basePath = dirname( __FILE__ );
require_once ($basePath . "/html_header.php");
$err = get_input('err');
$license_url = "<a href='http://www.logzilla.pro/licensing'> Click here </a> to order a new license or upgrade.<br>";
switch ($err) {
    case "hosts":
        $err = "You have reached the maximum number of hosts for your license.<br>$license_url";
        break;
    case "msg":
        $err = "You have reached the maximum number of messages for your license.<br>$license_url";
        break;
    case "auth":
        $err = "You are not licensed for this authentication type.<br>$license_url";
        break;
    case "charts":
        $err = "You are not licensed for Charts.<br>$license_url";
        break;
    case "alerts":
        $err = "You are not licensed for Email Alerts.<br>$license_url";
        break;
    case "rbac":
        $err = "You are not licensed for Role-Based Access Controls (RBAC).<br>$license_url";
        break;
default:
$err = "Your license is either expired or invalid.<br>$license_url";
}

//require_once ($basePath . "/js_footer.php");
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
// First, let's just make sure the web user can write files:
$filename = $basePath . '/ajax/license.log';
if (!is_writable($filename)) {
	echo "<div id='message_box'>ERROR: $filename is not writable by the Apache user!<br>Please make sure that the Apache user owns all directories under html/<br>e.g: chown www-data:www-data $filename</div>";
        exit;
}
$filename = $_SERVER['DOCUMENT_ROOT'];
if (!is_writable($filename)) {
	echo "<div id='message_box'>ERROR: $filename is not writable by the Apache user!<br>Please make sure that the Apache is able to write your license.txt to $filename<br>e.g: chown www-data:www-data /var/www/logzilla/html/</div>";
        exit;
}
?>
	<div id="message_box"><?php echo $err?>If you have already ordered a license from http://www.logzilla.pro, you may try auto-installing.<br>Note that the IP Address to use during registration for this server is <?php echo $_SERVER['SERVER_ADDR']?></div>
	
	<div id="license_form">
		<a href="#" id="vworkerSimon-install-button" class="vworkerSimon-btnStyle">Click for auto-install</a>
		<p id="vworkerSimon-install-report" style="display:none;"></p>
		<div id="vworkerSimon-license-form-container" style="display:none;">
			Please paste the data from your license.txt in the area below.<br>Be sure to <u>include the full text</u> of the license including the date and ending dashes.<br><br>
			You can obtain a free license from <a href="http://www.logzilla.pro/licensing/eval">LogZilla.pro</a>
			<form id="vworkerSimon-license-form">
				<textarea style="width:98%;" id="licdata" rows="15" cols="15" class="text"></textarea>
				<a href="#" id="vworkerSimon-submit-button" class="vworkerSimon-btnStyle">Click to Submit</a>
				<div class="clear"></div>
			</form>
		</div>
		<div id="vworkerSimon-install-success-container" style="display:none;">
			Your license has been successfully installed<br><b>NOTE:</b> You may need to restart Apache before the new license can be read (Apache will return a 500 internal server error as an indication).
			<a href="logout.php" id="vworkerSimon-next-button" class="vworkerSimon-btnStyle">Finish</a>
			<div class="clear"></div> 
		</div>
	</div>
	
	<script type="text/javascript" language="javascript">
	
		$(document).ready(function(){
						
			function checkLiveConnection(){
				$('#message_box').html('Submitting Request');
				$('#vworkerSimon-install-report').show();
				$.ajax({
					type: "GET",
					url: 'includes/ajax/lic.php',
					data: 'exe=checklive',
					dataType: "html",
					success: function( data ) {
						if (data.search(/Success/) == 1){
							requestAutoInstall();
						}else{
                        alert(data);
							requestManualInstall();
						}
					}
				});	
			}

			function requestAutoInstall(){
				$('#message_box').html('Please wait, LogZilla will attempt to contact the global licensing server...<br>');
				$.ajax({
					url: 'includes/ajax/lic.php',
					type: "GET",
					data: 'exe=startinstall',
					dataType: "html",
					success: function( data ) {
						if (data.search(/Success/) == 1){
							$('#vworkerSimon-install-report').html('License installed successfully.');
				            $('#message_box').html('Success!');
							$('#vworkerSimon-install-success-container').show();
						}else{
							$('#message_box').append(data + 'You may try manually pasting your license below.');
							requestManualInstall();
							$('#vworkerSimon-install-report').html('');
						}
					}
				});
				
			}
			
			function requestManualInstall(){
				$('#vworkerSimon-license-form-container').show();
			}

			$('#vworkerSimon-install-button').click(function(){
				$('#vworkerSimon-install-button').hide();
				checkLiveConnection();
			});
			
			$('#vworkerSimon-submit-button').click(function(){
			
				if ($("#licdata").val()){
                function nl2br (str, is_xhtml) {
                    // http://kevin.vanzonneveld.net
                    // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
                    // +   improved by: Philip Peterson
                    // +   improved by: Onno Marsman
                    // +   improved by: Atli Þr
                    // +   bugfixed by: Onno Marsman
                    // +      input by: Brett Zamir (http://brett-zamir.me)
                    // +   bugfixed by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
                    // +   improved by: Brett Zamir (http://brett-zamir.me)
                    // +   improved by: Maximusya
                    // *     example 1: nl2br('Kevin\nvan\nZonneveld');
                    // *     returns 1: 'Kevin<br />\nvan<br />\nZonneveld'
                    // *     example 2: nl2br("\nOne\nTwo\n\nThree\n", false);
                    // *     returns 2: '<br>\nOne<br>\nTwo<br>\n<br>\nThree<br>\n'
                    // *     example 3: nl2br("\nOne\nTwo\n\nThree\n", true);
                    // *     returns 3: '<br />\nOne<br />\nTwo<br />\n<br />\nThree<br />\n'
                    var breakTag = (is_xhtml || typeof is_xhtml === 'undefined') ? '<br />' : '<br>';

                        return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1' + breakTag + '$2');
                }
			
                    var chk_top = $("#licdata").val().match(/<licdata>|BEGIN/);
                    var chk_btm = $("#licdata").val().match(/<\/licdata>|END/);
                    if ((!chk_top) || (!chk_btm)) {
								$('#vworkerSimon-install-success-container').hide();
								$('#vworkerSimon-license-form-container').hide();
								$('#message_box').html('Invalid License Text.');
                                $( "<div id='error' title='Invalid Text'><br><br>You've entered invalid license text.<br>Make sure you include everything between the &lt;licdata&gt; tags.</div>" ).dialog({
                                    width: "50%", 
                                    height: 240, 
                                    buttons: {
                                    Ok: function() {
                                        $( this ).dialog( "close" );
								$('#vworkerSimon-license-form-container').show();
                                        }
                                    }
                                });
                    } else {
					$.ajax({
						url: 'includes/ajax/lic.php?exe=uploadfrominput',
						type: "POST",
						data: { licenseData: $("#licdata").val()},
						dataType: "html",
						success: function( data ) {
						if (data.search(/Success/) == 1){
								$('#message_box').html('License install successful.');
								$('#vworkerSimon-install-success-container').show();
								$('#vworkerSimon-license-form-container').hide();
							}else{
								$('#message_box').html('License Validation Failed.');
								$('#vworkerSimon-license-form-container').hide();
                                $( "<div id='error' title='Error'><br><br>" + data + "<br><br>Would you like to view the transaction log?</div>" ).dialog({
                                    width: "50%", 
                                    height: 240, 
                                    buttons: {
                                    No: function() {
                                        $( this ).dialog( "close" );
								$('.ui-dialog').hide();
								$('#message_box').html('License Validation Failed.<br>Please try using the console method by running <pre>cd /var/www/logzilla/scripts && ./install.pl install_license</pre>');
                                        },
                                    Yes: function() {
                                        $( this ).dialog( "close" );
                                        var file = "includes/ajax/license.log";
                                            $.get(file,function(txt){
                                            $( "<div id='tail_error' title='Debug Error Output'><br><br>"+ nl2br(txt) + "</div>" ).dialog({
                                                width: "75%", 
                                                height: 340, 
                                                });
                                            }); 
                                        }
                                    }

                                });
								// requestManualInstall();
							}
						}
					});
                    };
				}else{
					alert ('Please enter your license key before clicking submit');
				}
			});
		});
	</script>
