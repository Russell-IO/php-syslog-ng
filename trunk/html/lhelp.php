<?php
/*
 * form.php
 *
 * Developed by Clayton Dukes <cdukes@logzilla.pro>
 * Copyright (c) 2010 LogZilla, LLC
 * All rights reserved.
 *
 * 2010-12-10 - created
 *
 */

$basePath = dirname( __FILE__ );
require_once ($basePath . "/includes/common_funcs.php");
require_once ($basePath . "/../js_header.php");
require_once ($basePath . "/../css.php");
require_once ($basePath . "/../html_header.php");
$err = get_input('err');
$v = get_input('v');
// die($err);
?>
<style>
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

<script type="text/javascript" language="javascript">
//  Developed by Roshan Bhattarai 
//  Visit http://roshanbh.com.np for this script and more.
//  This notice MUST stay intact for legal use
$(document).ready(function()
{
    //scroll the message box to the top offset of browser's scrool bar
    $(window).scroll(function()
    {
        $('#message_box').animate({top:$(window).scrollTop()+"px" },{queue: false, duration: 3350});  
    });
    //when the close button at right corner of the message box is clicked 
    /*$('#close_message').click(function()
    {
        //the messagebox gets scrool down with top property and gets hidden with zero opacity 
        $('#message_box').animate({ top:"+=15px",opacity:0 }, "slow");
    });
    */
        // $('#message_box').animate({ top:"+=0px",opacity:0 }, {queue: false, duration: 5500});
});
 </script>

<div id="msgbox_tl" class="jGrowl top-left"></div>
<div id="msgbox_br" class="jGrowl bottom-right"></div>
<div id="msgbox_bl" class="jGrowl bottom-left"></div>
<div id="msgbox_tc" class="jGrowl center"></div>

<?php
switch ($err) {
    case "license":
        echo '<div id="message_box">Your license file is either expired or invalid</div>';
    break;
    case "hosts":
        echo "<div id=\"message_box\">The ".humanReadable($v)." host limit for your license has been exceeded<br />
        <a href=\"http://www.logzilla.pro/licensing\">Click here to upgrade</a>
        </div>";
    break;
    case "mpd":
        echo "<div id=\"message_box\">The ".humanReadable($v)." message limit for your license has been exceeded<br />
        <a href=\"http://www.logzilla.pro/licensing\">Click here to upgrade</a>
        </div>";
    break;
    case "noauth":
        echo "<div id=\"message_box\">Your license does not allow for auth types other than \"local\" or \"none\"<br />
        <a href=\"http://www.logzilla.pro/packs/ldap\">Click here to upgrade</a>
        </div>";
    break;
    case "nocharts":
        echo "<div id=\"message_box\">Your license does not allow for ad-hoc charts<br />
        <a href=\"http://www.logzilla.pro/packs/charts\">Click here to upgrade for only $49.00</a>
        </div>";
    break;
    case "noalerts":
        echo "<div id=\"message_box\">Your license does not allow for email alerts<br />
        <a href=\"http://www.logzilla.pro/packs/email\">Click here to upgrade</a>
        </div>";
    break;
}
?>
<script type="text/javascript">
$(document).ready(function(){
    var url = "<?php echo ($_SESSION['SITE_URL'])?>";
    $("#license_form").dialog();
});
</script>

<script type="text/javascript">
$(function() {
    $( "#license_form" ).dialog({
        bgiframe: true,
        resizable: true,
        height: 'auto',
        width: '30%',
        autoOpen:false,
        modal: true,
        position: 'center',
        overlay: {
        backgroundColor: '#000',
        opacity: 0.5
    },
        buttons: {
            "Submit": function() {
            var text = $("#licdata").val();
            text = text.replace(/\+/g, "PLUS");
// alert(text);
            text = escape(text);
            if (text) {
            $.get("includes/ajax/lpost.php?txt="+text, function(data){
            $('#message_box').replaceWith('<div id="message_box">'+ data + '</div>');

                });
            $( this ).dialog( "close" );
            }
            window.location = "logout.php";
            },
        }
    });
});
</script>

<div class="dialog_hide">
<div id="license_form" title='Paste New License'>
Please paste the data from your license.txt in the area below.<br />Be sure to <u>include the full text</u> of the license including the date and ending dashes.<br /><br />
You can obtain a free license from <a href="http://www.logzilla.pro/licensing/eval">LogZilla.pro</a>
<form>
<textarea style="width:100%;" id="licdata" rows="15" cols="15" class="text ui-widget-content ui-corner-all" /></textarea>
</form>
</div>
<?php
require_once ($basePath . "/html_footer.php");
?>
