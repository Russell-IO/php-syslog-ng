<?php
// Copyright (c) 2010 LogZilla, LLC, cdukes@logzilla.pro
// Last updated on 2010-06-15

//----------------------------------------------------------------------------------------
// This is a simple menu layout for the top menu on the search page
// Basic usage:
// 	<li><a href"link">Menu Item</a></li>
// 
// Or, for submenus, just create a new <ul> and close the first </li> after the submenu, like this:
// 	<li><a href"link">Menu Item</a>
// 		<ul>
// 			<li>Sub Menu Item</li>
// 		</ul>
//  </li>
//----------------------------------------------------------------------------------------
session_start();
?>
<!-- BEGIN News -->
<script type="text/javascript" src="includes/js/jquery/plugins/highslide/highslide-full.packed.js"></script>
<!-- END News -->

<!-- BEGIN News -->
<script type="text/javascript">
$(document).ready(function(){
    hs.graphicsDir = 'includes/js/jquery/plugins/highslide/graphics/';
    hs.showCredits = false;
    hs.outlineType = 'rounded-white';
    hs.wrapperClassName = 'draggable-header';
    hs.align = 'center';
    hs.outlineWhileAnimating = true;
});
</script>
<!-- END News -->
    <script type="text/javascript" src="includes/menu/fg.menu.js"></script>
    <link type="text/css" href="includes/menu/fg.menu.css" media="screen" rel="stylesheet" />

	<style type="text/css">

/* Sparklines */
#ticker_container {
position: absolute;
top: 0px;
right: 40px;
}
.ticker_text {
  font-size:10px; 
  font-family:Trebuchet MS; 
  color: white;
  position: relative;
  top: 10px;
  left: 10px;
}
.ticker {
  font-size:10px; 
  font-family:Trebuchet MS; 
  color: white;
  position: relative;
  top: 0px;
  left: 10px;
}


/* Menu */
	#menuLog { font-size:1.4em; margin:10px; }
	.hidden { position:absolute; top:0; left:-9999px; width:1px; height:1px; overflow:hidden; }
	
	.fg-button { clear:left; margin:0 4px 40px 20px; padding: .4em 1em; text-decoration:none !important; cursor:pointer; position: relative; text-align: center; zoom: 1; }
	.fg-button .ui-icon { position: absolute; top: 50%; margin-top: -8px; left: 50%; margin-left: -8px; }
	a.fg-button { float:left;  }
	button.fg-button { width:auto; overflow:visible; } /* removes extra button width in IE */
	
	.fg-button-icon-left { padding-left: 2.1em; }
	.fg-button-icon-right { padding-right: 2.1em; }
	.fg-button-icon-left .ui-icon { right: auto; left: .2em; margin-left: 0; }
	.fg-button-icon-right .ui-icon { left: auto; right: .2em; margin-left: 0; }
	.fg-button-icon-solo { display:block; width:8px; text-indent: -9999px; }	 /* solo icon buttons must have block properties for the text-indent to work */	
	
	.fg-button.ui-state-loading .ui-icon { background: url(includes/menu/spinner_bar.gif) no-repeat 0 0; }
    .fg-menu-container { z-index: 3; -moz-box-shadow: 10px 10px 5px #888; -webkit-box-shadow: 10px 10px 5px #888; box-shadow: 10px 10px 5px #888;}
	</style>
	
	<!-- style exceptions for IE 6 -->
	<!--[if IE 6]>
	<style type="text/css">
		.fg-menu-ipod .fg-menu li { width: 95%; }
		.fg-menu-ipod .ui-widget-content { border:0; }
	</style>
	<![endif]-->	

<a tabindex="0" href="#menuItems" style="z-index: 999; position: absolute; top: 0px; left: -5px;" class="fg-button fg-button-icon-right ui-widget ui-state-default ui-corner-all" id="navmenu"><span style="z-index: 999;" class="ui-icon ui-icon-triangle-1-s"></span><img style='border: 0 none; width: 15px; padding-right:15px; position: relative; top: 2px;' src='images/lztri.png' alt='Back to Main page'/>Menu</a>
<div id="menuItems" class="hidden">

<ul>

    <li><a href="<?php echo $_SESSION['SITE_URL']?>?page=Main">Return to Main Search Page</a>
    <li><a href="#">Admin</a>
        <ul>
            <?php 
            if ($_SESSION['AUTHTYPE'] != "none") { 
            ?>
            <li><a href="<?php echo $_SESSION['SITE_URL']?>?page=User">User Admin</a></li>
            <?php } ?>
            <?php 
            if ((has_portlet_access($_SESSION['username'], 'Server Settings') == TRUE) || ($_SESSION['AUTHTYPE'] == "none")) { 
            ?>
            <li><a href="<?php echo $_SESSION['SITE_URL']?>?page=Admin">Server Admin</a></li>
            <li><a href="<?php echo $_SESSION['SITE_URL']?>?page=Email_Alerts">Email Alerts</a></li>
            <li><a href="<?php echo $_SESSION['SITE_URL']?>?page=Security">RBAC</a></li>
            <?php } ?>
            <?php 
            if ((has_portlet_access($_SESSION['username'], 'Import') == TRUE) || ($_SESSION['AUTHTYPE'] == "none")) { 
            ?>
            <li><a href="<?php echo $_SESSION['SITE_URL']?>?page=Import">Import</a></li>         <?php } ?>
            <?php 
            if ((has_portlet_access($_SESSION['username'], 'Portlet Group Permissions') == TRUE) && ($_SESSION['AUTHTYPE'] != "none")) { 
            ?>
            <li><a href="<?php echo $_SESSION['SITE_URL']?>?page=Portlet_Admin">Portlet Admin</a></li>
            <?php } ?>
        </ul>
    </li>
    <!-- END Top Level with 2nd Level -->

    <!-- BEGIN Top Level with 2nd Level -->
     <li><a href="#">Charts</a>
        <ul>
        <li><a href="#">Top 10's</a>
            <ul>
            <li><a href="#">Today</a>
                <ul>
                <li><a href="<?php echo $_SESSION['SITE_URL']?>?page=Graph&show_suppressed=all&limit=10&orderby=counter&order=DESC&groupby=host_crc&chart_type=pie&chartdays=today">Hosts</a></li>
                <li><a href="<?php echo $_SESSION['SITE_URL']?>?page=Graph&show_suppressed=all&limit=10&orderby=counter&order=DESC&groupby=program&chart_type=pie&chartdays=today">Programs</a></li>
                <li><a href="<?php echo $_SESSION['SITE_URL']?>?page=Graph&show_suppressed=all&limit=10&orderby=counter&order=DESC&groupby=severity&chart_type=pie&chartdays=today">Severities</a></li>
                <li><a href="<?php echo $_SESSION['SITE_URL']?>?page=Graph&show_suppressed=all&limit=10&orderby=counter&order=DESC&groupby=facility&chart_type=pie&chartdays=today">Facilities</a></li>
                <li><a href="<?php echo $_SESSION['SITE_URL']?>?page=Graph&show_suppressed=all&limit=10&orderby=counter&order=DESC&groupby=mne&chart_type=pie&chartdays=today">Cisco Mnemonics</a></li>
        <?php if($_SESSION['SNARE'] == "1") {?>
                <li><a href="<?php echo $_SESSION['SITE_URL']?>?page=Graph&show_suppressed=all&limit=10&orderby=counter&order=DESC&groupby=eid&chart_type=pie&chartdays=today">Windows EventId</a></li>
                    <?php } ?>
                </ul>
            </li>
            <li><a href="#">Yesterday</a>
                <ul>
                <li><a href="<?php echo $_SESSION['SITE_URL']?>?page=Graph&show_suppressed=all&limit=10&orderby=counter&order=DESC&groupby=host_crc&chart_type=pie&lo_checkbox=on&chartdays=yesterday">Hosts</a></li>
                <li><a href="<?php echo $_SESSION['SITE_URL']?>?page=Graph&show_suppressed=all&limit=10&orderby=counter&order=DESC&groupby=program&chart_type=pie&lo_checkbox=on&chartdays=yesterday">Programs</a></li>
                <li><a href="<?php echo $_SESSION['SITE_URL']?>?page=Graph&show_suppressed=all&limit=10&orderby=counter&order=DESC&groupby=severity&chart_type=pie&lo_checkbox=on&chartdays=yesterday">Severities</a></li>
                <li><a href="<?php echo $_SESSION['SITE_URL']?>?page=Graph&show_suppressed=all&limit=10&orderby=counter&order=DESC&groupby=facility&chart_type=pie&lo_checkbox=on&chartdays=yesterday">Facilities</a></li>
                <li><a href="<?php echo $_SESSION['SITE_URL']?>?page=Graph&show_suppressed=all&limit=10&orderby=counter&order=DESC&groupby=mne&chart_type=pie&lo_checkbox=on&chartdays=yesterday">Cisco Mnemonics</a></li>
        <?php if($_SESSION['SNARE'] == "1") {?>
                <li><a href="<?php echo $_SESSION['SITE_URL']?>?page=Graph&show_suppressed=all&limit=10&orderby=counter&order=DESC&groupby=eid&chart_type=pie&lo_checkbox=on&chartdays=yesterday">Windows EventId</a></li>
                    <?php } ?>
                </ul>
            </li>
            <li><a href="#">This Week</a>
                <ul>
                <li><a href="<?php echo $_SESSION['SITE_URL']?>?page=Graph&show_suppressed=all&limit=10&orderby=counter&order=DESC&groupby=host_crc&chart_type=pie&lo_checkbox=on&chartdays=this%20week">Hosts</a></li>
                <li><a href="<?php echo $_SESSION['SITE_URL']?>?page=Graph&show_suppressed=all&limit=10&orderby=counter&order=DESC&groupby=program&chart_type=pie&lo_checkbox=on&chartdays=this%20week">Programs</a></li>
                <li><a href="<?php echo $_SESSION['SITE_URL']?>?page=Graph&show_suppressed=all&limit=10&orderby=counter&order=DESC&groupby=severity&chart_type=pie&lo_checkbox=on&chartdays=this%20week">Severities</a></li>
                <li><a href="<?php echo $_SESSION['SITE_URL']?>?page=Graph&show_suppressed=all&limit=10&orderby=counter&order=DESC&groupby=facility&chart_type=pie&lo_checkbox=on&chartdays=this%20week">Facilities</a></li>
                <li><a href="<?php echo $_SESSION['SITE_URL']?>?page=Graph&show_suppressed=all&limit=10&orderby=counter&order=DESC&groupby=mne&chart_type=pie&lo_checkbox=on&chartdays=this%20week">Cisco Mnemonics</a></li>
        <?php if($_SESSION['SNARE'] == "1") {?>
                <li><a href="<?php echo $_SESSION['SITE_URL']?>?page=Graph&show_suppressed=all&limit=10&orderby=counter&order=DESC&groupby=eid&chart_type=pie&lo_checkbox=on&chartdays=this%20week">Windows EventId</a></li>
                    <?php } ?>
                </ul>
            </li>
            <?php if ($_SESSION['RETENTION'] > 27) {?>
            <li><a href="#">This Month</a>
                <ul>
                <li><a href="<?php echo $_SESSION['SITE_URL']?>?page=Graph&show_suppressed=all&limit=10&orderby=counter&order=DESC&groupby=host_crc&chart_type=pie&lo_checkbox=on&chartdays=thismonth">Hosts</a></li>
                <li><a href="<?php echo $_SESSION['SITE_URL']?>?page=Graph&show_suppressed=all&limit=10&orderby=counter&order=DESC&groupby=program&chart_type=pie&lo_checkbox=on&chartdays=thismonth">Programs</a></li>
                <li><a href="<?php echo $_SESSION['SITE_URL']?>?page=Graph&show_suppressed=all&limit=10&orderby=counter&order=DESC&groupby=severity&chart_type=pie&lo_checkbox=on&chartdays=thismonth">Severities</a></li>
                <li><a href="<?php echo $_SESSION['SITE_URL']?>?page=Graph&show_suppressed=all&limit=10&orderby=counter&order=DESC&groupby=facility&chart_type=pie&lo_checkbox=on&chartdays=thismonth">Facilities</a></li>
                <li><a href="<?php echo $_SESSION['SITE_URL']?>?page=Graph&show_suppressed=all&limit=10&orderby=counter&order=DESC&groupby=mne&chart_type=pie&lo_checkbox=on&chartdays=thismonth">Cisco Mnemonics</a></li>
        <?php if($_SESSION['SNARE'] == "1") {?>
                <li><a href="<?php echo $_SESSION['SITE_URL']?>?page=Graph&show_suppressed=all&limit=10&orderby=counter&order=DESC&groupby=eid&chart_type=pie&lo_checkbox=on&chartdays=thismonoth">Windows EventId</a></li>
                    <?php } ?>
                </ul>
            </li>
            <?php } ?>
            </ul>
        </li>
        <li><a href="#">MPx</a>
            <ul>
                <li><a href="<?php echo $_SESSION['SITE_URL']?>?page=EPS">Events Per Second</a></li>
                <li><a href="<?php echo $_SESSION['SITE_URL']?>?page=EPM">Events Per Minute</a></li>
                <li><a href="<?php echo $_SESSION['SITE_URL']?>?page=EPH">Events Per Hour</a></li>
                <li><a href="<?php echo $_SESSION['SITE_URL']?>?page=EPD">Events Per Day</a></li>
                <li><a href="<?php echo $_SESSION['SITE_URL']?>?page=EPW">Events Per Week</a></li>
                <li><a href="<?php echo $_SESSION['SITE_URL']?>?page=EPMo">Events Per Month</a></li>
            </ul>
        </ul>
    </li>
    <!-- END Top Level with 2nd Level -->

    <!-- BEGIN Top Level with 2nd Level -->
     <li><a href="#">Help</a>
        <ul>
            <li><a href="#">Online</a>
                <ul>
                    <li><a href="http://docs.logzilla.pro" target="_blank">Documentation</a></li>
                    <li><a href="http://demo.logzilla.pro/login.php" target="_blank">Demo Site</a></li>
                    <li><a href="http://nms.gdd.net" target="_blank">Clayton's NMS Wiki</a></li>
                    <li><a href="http://forum.logzilla.pro" target="_blank">LogZilla Forum</a></li>
                    <li><a href="http://www.logzilla.pro/licensing" target="_blank">Get Licenses</a></li>
                    <li><a href="http://www.logzilla.pro/packs" target="_blank">Order Upgrades</a></li>
                    <li><a href="http://www.cisco.com/en/US/technologies/collateral/tk869/tk769/white_paper_c11-557812.html" target="_blank">Syslog Whitepaper</a></li>
                </ul>
            </li>
            <!-- END 2nd Level with 3nd Level -->
            <!-- BEGIN 2nd Level -->
            <li><a href="<?php echo $_SESSION['SITE_URL']?>?page=Bugs">Changelog/History</a></li>
            <li><a href="<?php echo $_SESSION['SITE_URL']?>?page=About">About <?php echo $_SESSION['PROGNAME']?></a></li>
            <!-- END 2nd Level -->
        </ul>
    </li>
    <!-- END Top Level with 2nd Level -->

    <!-- BEGIN Top Level with 2nd Level -->
    <li><a href="#">Favorites</a>
        <ul>
            <!-- BEGIN 2nd Level with 3nd Level -->
            <li><a href="#">Saved Searches</a>
                <ul>
                <div id="search_history">
                <?php
                $sql = "SELECT * FROM history WHERE userid=(SELECT id FROM users WHERE username='".$_SESSION['username']."') ORDER BY lastupdate DESC";
                $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
                if($result) {
                    while($row = fetch_array($result)) {
                        if ($row['spanid'] == 'search_history') {
                            echo "<li><a href=".$row['url'].">".$row['urlname']."</a></li>\n";
                        }
                    }
                }
                ?>
                </div>
                </ul>
             </li>
             <li><a href="#">Saved Charts</a>
                <ul>
                <div id="chart_history">
                <?php
                $sql = "SELECT * FROM history WHERE userid=(SELECT id FROM users WHERE username='".$_SESSION['username']."') ORDER BY lastupdate DESC";
                $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
                if($result) {
                    while($row = fetch_array($result)) {
                        if ($row['spanid'] == 'chart_history') {
                            echo "<li><a href=".$row['url'].">".$row['urlname']."</a></li>\n";
                        }
                    }
                }
                ?>
                </div>
                </ul>
              </li>
<?php if ((has_portlet_access($_SESSION['username'], 'Edit Favorites') == TRUE) || ($_SESSION['AUTHTYPE'] == "none")) { ?>
            <li><a href="<?php echo $_SESSION['SITE_URL']?>?page=Favorites">Favorites Admin</a></li>
                <?php } ?>
         </ul>
            <!-- END 2nd Level with 3nd Level -->
    <?php 
    if($_SESSION['AUTHTYPE'] != 'none')  { 
    echo "<li><a href=$_SERVER[SITE_URL]?pageId=logout>Logout</a></li>";
    } else {
    echo "<li></li>";
    }
        ?>
            <li><a onclick="reset_layout();return false;" href="#">Reset Layout</a></li>
</ul>
    <!-- END Top Level with 2nd Level -->
</div>
<!-- Uncomment to show clicked link
<p id="menuLog" style="z-index: 100; position: absolute; top: 0px; left: 90px;">You chose: <span id="menuSelection"></span></p>
-->

    <script type="text/javascript">    
    $(function(){
    	$('.fg-button').hover(
    		function(){ $(this).removeClass('ui-state-default').addClass('ui-state-focus'); },
    		function(){ $(this).removeClass('ui-state-focus').addClass('ui-state-default'); }
    	);
    	
		$('#navmenu').menu({
			content: $('#navmenu').next().html(),
            maxHeight: "200px",
            crumbDefaultText: '',
            backLink: false
		});
    });
    </script>

<!-- BEGIN Reset UI Layout -->
<script type="text/javascript">
function reset_layout()
{
    $.get("includes/ajax/json.useradmin.php?action=reset_layout", function(data){
            window.location = '<?php echo $_SESSION['SITE_URL']?>';
            // No point in displaying the message since the page will reload
            // notify(data);
            });
}; 
</script>
<!-- END Reset UI Layout -->
<!-- BEGIN Sparkline -->
<div id='ticker_container'>
<span class="ticker"></span> <span class="ticker_text"></span>
</div>
<script type="text/javascript" src="includes/js/jquery/plugins/jquery.sparkline.min.js"></script>

<?php if (!preg_match('/Results/', $_REQUEST["page"] )) {?>
<script type="text/javascript">
$(document).ready(function(){
function isNumber(n) {
    return !isNaN(parseFloat(n)) && isFinite(n);
}
/** 
 ** Draws the Messages per second ticker
 **/
var enabled = <?php echo ($_SESSION['SPARKLINES'])?>;
if (enabled == 1) {
average = function(a){
    var r = {mean: 0, variance: 0, deviation: 0}, t = a.length;
    for(var m, s = 0, l = t; l--; s += a[l]);
    for(m = r.mean = s / t, l = t, s = 0; l--; s += Math.pow(a[l] - m, 2));
    return r.deviation = Math.sqrt(r.variance = s / t), r;
}
var average = function(a){
    var r = {mean: 0, variance: 0, deviation: 0}, t = a.length;
    for(var m, s = 0, l = t; l--; s += a[l]);
    for(m = r.mean = s / t, l = t, s = 0; l--; s += Math.pow(a[l] - m, 2));
    return r.deviation = Math.sqrt(r.variance = s / t), r;
}
var refreshinterval = <?php echo ($_SESSION['Q_TIME']*1000)?>; // update display every 1 second
var lasttime;
var travel = 0;
var points = [];
var points_max = 60;
mdraw = function() {
    var md = new Date();
    var data = { };
    var timenow = md.getTime();
    if (lasttime && lasttime!=timenow) {
        var pps = Math.round(travel / (timenow - lasttime) * 1000);
        points.push(pps);
        if (points.length > points_max)
            points.splice(0,1);
        travel = 0;
        $.getJSON('includes/ajax/json.sparkline.mps.php', function(data) {
            // $('.dynamicsparkline').sparkline(data, {width: points.length*20, height: '45px'});
            $('.ticker_text').text("");
            if(data) {
                // Add sparkline:
                $('.ticker').sparkline(data, {width: ((data.length - 1) * 2), height: '30px', type: 'line'});
                // Added average MPS text if data exists:
                var total = 0;
                for(var i = 0; i < data.length; i++){
                    var thisVal = parseInt(data[i]);
                    if(!isNaN(thisVal)){
                        total += thisVal;
                    };
                };
                var eps = Math.round(total / (data.length - 1));
                var epm = Math.round(total / (data.length - 1) * 60 );
                if (isNumber(eps) && (eps >0)){
                    $('.ticker_text').text('Average Events Per Second: ' + eps);
                } else if (isNumber(epm) && (epm>0)) {
                    $('.ticker_text').text('Average Events Per Minute: ' + epm);
                } else {
                    $('.ticker_text').text("No Incoming Messages");
                };
            } else {
                $('.ticker_text').text("No Incoming Messages");
                $('.ticker').sparkline(data, {width: '0px', height: '30px', type: 'line'});
            };
        });
    }
    lasttime = timenow;
    mtimer = setTimeout(mdraw, refreshinterval);
}
var mtimer = setTimeout(mdraw, refreshinterval); 
$.sparkline_display_visible(); 
} else {
    $('.ticker_text').text("");
}

// $('.ticker_text').draggable();
});
</script>
<?php } ?>
<!-- END Sparkline -->

<!-- BEGIN Notify -->
<div id="notify-container">
<div id="notifyBar">
            <a class="ui-notify-close ui-notify-cross" href="#">x</a>
            <div style="float:left;margin:0 10px 0 0"><img src="#{icon}" alt="warning" /></div>
            <h1>#{title}</h1>
            <p>#{text}</p>
        </div>
</div>
<script type="text/javascript">
$('#notifyBar').hide();
</script>
<!-- END Notify -->
