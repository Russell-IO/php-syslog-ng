<?php
/*
* portlet-table.php
*
* Developed by Clayton Dukes <cdukes@logzilla.pro>
* Copyright (c) 2010 LogZilla, LLC
* All rights reserved.
* Last updated on 2010-06-15
*
* Pagination and table formatting created using 
* http://www.frequency-decoder.com/2007/10/19/client-side-table-pagination-script/
* Changelog:
* 2010-02-28 - created
*
*/

$basePath = dirname( __FILE__ );
require_once ($basePath . "/../common_funcs.php");
$dbLink = db_connect_syslog(DBADMIN, DBADMINPW);

//---------------------------------------------------
// The get_input statements below are used to get
// POST, GET, COOKIE or SESSION variables.
// Note that PLURAL words below are arrays.
//---------------------------------------------------

if ((has_portlet_access($_SESSION['username'], 'Server Settings') == TRUE) || ($_SESSION['AUTHTYPE'] == "none")) { 
?>

<script>
    $(function() {
        $( "#div_admin_accordian" ).accordion({
            navigation: true,
            collapsible: true
        });
    });
</script>

<h3 class="docs">Changing some of these settings will render your server unusable, proceed with CAUTION!!!</h3>

<div id="div_adminMenu" style="padding:2px; width:20%; height:600px;" class="ui-widget-content">

    <div id="div_admin_accordian">
        <h3><a href="#">Basic Settings</a></h3>
        <div>
            <a href="#" class='adminItem' id='ADMIN_NAME'>Admin Name</a><br />
            <a href="#" class='adminItem' id='ADMIN_EMAIL'>Admin Email</a><br />
            <a href="#" class='adminItem' id='FEEDBACK'>Feedback Button (Idea Box)</a><br />
            <a href="#" class='adminItem' id='SESS_EXP'>Login Session Timeout</a><br />
            <a href="#" class='adminItem' id='SITE_NAME'>Website Name</a><br />
            <a href="#" class='adminItem' id='SPARKLINES'>EPS Stock Ticker</a><br />
            <a href="#" class='adminItem' id='TOOLTIP_GLOBAL'>LogZilla Tips</a><br />
            <a href="#" class='adminItem' id='TOOLTIP_REPEAT'>Tip Timer</a><br />
        </div>
        <h3><a href="#">Alerts</a></h3>
        <div>
            <a href="#" class='adminItem' id='MAILHOST'>Mail Host</a><br />
            <a href="#" class='adminItem' id='MAILHOST_PORT'>Mail Host Port</a><br />
            <a href="#" class='adminItem' id='MAILHOST_USER'>Mail Host User (Optional)</a><br />
            <a href="#" class='adminItem' id='MAILHOST_PASS'>Mail Host Password (Optional)</a><br />
            <a href="#" class='adminItem' id='SNMP_SENDTRAPS'>Send Alerts to SNMP Trap Manager</a><br />
            <a href="#" class='adminItem' id='SNMP_COMMUNITY'>SNMP Community</a><br />
            <a href="#" class='adminItem' id='SNMP_TRAPDEST'>SNMP Destination</a><br />
        </div>
        <h3><a href="#">RBAC</a></h3>
        <div>
            <a href="#" class='adminItem' id='RBAC_ALLOW_DEFAULT'>New User Security</a><br />
        </div>
        <h3><a href="#">Timing</a></h3>
        <div>
            <a href="#" class='adminItem' id='Q_LIMIT'>Message Queue Limit</a><br />
            <a href="#" class='adminItem' id='Q_TIME'>Message Queue Time Limit</a><br />
        </div>
        <h3><a href="#">Archival</a></h3>
        <div>
            <a href="#" class='adminItem' id='RETENTION'>Retention Policy</a><br />
            <a href="#" class='adminItem' id='RETENTION_DROPS_HOSTS'>Stale Host Purging</a><br />
            <a href="#" class='adminItem' id='ARCHIVE_PATH'>Archive Path</a><br />
            <a href="#" class='adminItem' id='ARCHIVE_BACKUP'>Archival Command</a><br />
            <a href="#" class='adminItem' id='ARCHIVE_RESTORE'>Archival Restore Command</a><br />
        </div>
        <h3><a href="#">Authentication</a></h3>
        <div>
            <a href="#" class='adminItem' id='AUTHTYPE'>Authorization Type</a><br />
            <a href="#" class='adminItem' id='LDAP_BASE_DN'>LDAP Base DN</a><br />
            <a href="#" class='adminItem' id='LDAP_CN'>LDAP CN</a><br />
            <a href="#" class='adminItem' id='LDAP_DNU_GRP'>LDAP Group</a><br />
            <a href="#" class='adminItem' id='LDAP_DOMAIN'>LDAP Domain</a><br />
            <a href="#" class='adminItem' id='LDAP_MS'>Use Microsoft LDAP Type</a><br />
            <a href="#" class='adminItem' id='LDAP_SRV'>LDAP Server Address</a><br />
        </div>
        <h3><a href="#">Chart Options</a></h3>
        <div>
            <a href="#" class='adminItem' id='CACHE_CHART_MPH'>MPH Chart</a><br />
            <a href="#" class='adminItem' id='CACHE_CHART_MPW'>MPW Chart</a><br />
            <!-- not used anymore?
            <a href="#" class='adminItem' id='CACHE_CHART_TOPHOSTS'>Hosts Chart Cache</a><br />
            <a href="#" class='adminItem' id='CACHE_CHART_TOPMSGS'>Messages Chart Cache</a><br />
            -->
            <a href="#" class='adminItem' id='CHART_MPD_DAYS'>MPD Chart</a><br />
            <a href="#" class='adminItem' id='CHART_SOW'>Week Start</a><br />
        </div>
        <h3><a href="#">Debugging</a></h3>
        <div>
            <a href="#" class='adminItem' id='DEBUG'>Set Debug Level</a><br />
        </div>
        <h3><a href="#">Deduplication</a></h3>
        <div>
            <a href="#" class='adminItem' id='DEDUP'>Deduplication</a><br />
            <a href="#" class='adminItem' id='DEDUP_WINDOW'>Lookback Window</a><br />
        </div>
        <h3><a href="#">Paths</a></h3>
        <div>
            <a href="#" class='adminItem' id='PATH_BASE'>Path To LogZilla (on disk)</a><br />
            <a href="#" class='adminItem' id='PATH_LOGS'>Path To logging directory (on disk)</a><br />
            <a href="#" class='adminItem' id='SITE_URL'>Relative Site URL</a><br />
        </div>
        <h3><a href="#">Portlets</a></h3>
        <div>
            <a href="#" class='adminItem' id='PORTLET_EID_LIMIT'>Snare Portlet Limit</a><br />
            <a href="#" class='adminItem' id='PORTLET_HOSTS_LIMIT'>Hosts Portlet Limit</a><br />
            <a href="#" class='adminItem' id='PORTLET_MNE_LIMIT'>Mnemonics Portlet Limit</a><br />
            <a href="#" class='adminItem' id='PORTLET_PROGRAMS_LIMIT'>Programs Portlet Limit</a><br />
            <a href="#" class='adminItem' id='SHOWCOUNTS'>Show Portlet Counts</a><br />
        </div>
        <h3><a href="#">Sphinx Tuning</a></h3>
        <div>
            <a href="#" class='adminItem' id='SPX_MAX_MATCHES'>Maximum Result Set</a><br />
            <a href="#" class='adminItem' id='SPX_MEM_LIMIT'>Memory Limit</a><br />
            <a href="#" class='adminItem' id='SPX_PORT'>Port</a><br />
            <a href="#" class='adminItem' id='SPX_SRV'>Server IP</a><br />
        </div>
        <h3><a href="#">Audit Logging</a></h3>
        <div>
            <a href="#" class='adminItem' id='SYSTEM_LOG_DB'>Audit Logs to Database</a><br />
            <a href="#" class='adminItem' id='SYSTEM_LOG_FILE'>Audit Logs to File</a><br />
            <a href="#" class='adminItem' id='SYSTEM_LOG_SYSLOG'>Audit Logs to Syslog</a><br />
        </div>
        <h3><a href="#">Windows Events</a></h3>
        <div>
            <a href="#" class='adminItem' id='SNARE'>SNARE Windows Event Processing</a><br />
        </div>
    </div>

</div><!-- End div_adminMenu -->
    <div id="dlg" class="dialog"><span id="dlg_content"></span></div>
    <div id="dlg_desc" class="dialog"><span id="dlg_desc_content"></span></div>

<script type="text/javascript"> 
    $(document).ready(function(){
            var name = "";
            var value = "";

            $('.adminItem').click(function() {
                name = $(this).attr('id');
                $.get("includes/ajax/admin.php?action=get&name=" +name,
                    function(data){
                    value = data.value;
                    switch (data.type) {
                    case "varchar":
                    $("#dlg_content").html('<input type=text class="rounded ui-widget ui-corner-all" id="inp_'+name+'" value="'+value+'"  /><span id="result"></span>');
                    $( "#dlg" ).dialog({ title: name, buttons: [
                        {
text: "Modify",
click: function() { 
var selected = $('#inp_'+name).val();
$.get("includes/ajax/admin.php?action=save&name=" +name+"&orig_value="+value+"&new_value="+selected,
    function(data){
$("#result").html(data);
    })
}
}
] });
$("#sel_value").multiselect({
show: ["blind", 200],
hide: ["drop", 200],
selectedList: 1,
multiple: false,
noneSelectedText: 'Select',
});
break;
                    case "enum":
                    var opts = "";
                    var options = data.options.split(",");

                    for(i = 0; i < options.length; i++){
                    if(options[i] == value) {
                        opts += '<option selected value="'+options[i]+'">'+options[i]+'</option>';
                    } else {
                        opts += '<option value="'+options[i]+'">'+options[i]+'</option>';
                    }
                    }
$("#dlg_content").html('<select id="sel_value" multiple size=0>'+opts+'</select><span id="result"></span>');
$( "#dlg" ).dialog({ title: name, buttons: [
        {
text: "Modify",
click: function() { 
var selected = $('#sel_value').val();
$.get("includes/ajax/admin.php?action=save&name=" +name+"&orig_value="+value+"&new_value="+selected,
    function(data){
$("#result").html(data);
    })
}
}
] });
$("#sel_value").multiselect({
show: ["blind", 200],
hide: ["drop", 200],
selectedList: 1,
multiple: false,
noneSelectedText: 'Select',
});
break;
                    case "int":
                    $("#dlg_content").html('<input type=text class="rounded ui-widget ui-corner-all" id="inp_'+name+'" value="'+value+'"  /><span id="result"></span>');
                    $( "#dlg" ).dialog({ title: name, buttons: [
                        {
text: "Modify",
click: function() { 
var selected = $('#inp_'+name).val();
$.get("includes/ajax/admin.php?action=save&name=" +name+"&orig_value="+value+"&new_value="+selected,
    function(data){
$("#result").html(data);
    })
}
}
] });
$("#sel_value").multiselect({
show: ["blind", 200],
hide: ["drop", 200],
selectedList: 1,
multiple: false,
noneSelectedText: 'Select',
});
break;
}
$( "#dlg_desc" ).dialog({ title: "Description", width: 350, height: 350 });
$("#dlg_desc_content").html("Default: " + data.def + "<br /><br />" +data.description);
$(".ui-dialog-titlebar-close").remove();
}, "json");

//------------------------------------------------------------------------
// Set Dialog box positions
//------------------------------------------------------------------------
$(".dialog").each(function(index){
        // Get adminmenu's position
        var p = $("#div_adminMenu").position();
        var t = p.top;
        var w = $("#div_adminMenu").width();
        var dialogW=350 ;
        // set position from left side of the admin menu's width (w)
        if (index < 1) {
        var posX= index *dialogW + (w + 30);   
        } else {
        // Pad a little between portlets
        var posX= index *dialogW + (w + 45);   
        }
        // set position = to the top of the admin menu (t)
        // note: for some reason, this retuens a value lower than the actual top, so I padded it.
        var posY= t + 75;

        $(this).dialog({
width: dialogW,
position: [ posX, posY]
});
        $(".ui-dialog-titlebar-close").remove();

        })
})

$('td').click(function () {
        // http://josephscott.org/code/javascript/jquery-edit-in-place/
        var id = $(this).attr("id");
        switch (id) {
        case "value":
        $('#'+id).eip( "includes/ajax/admin.php?action=save&name="+name, { select_text: true } );
        break;
        }
        })
}); // end doc ready
</script>

<?php } else { ?>
<script type="text/javascript">
    $('#portlet_Server_Settings').remove()
    </script>
    <?php } ?>
