<?php
/*
 *
 * Developed by Clayton Dukes <cdukes@logzilla.pro>
 * Copyright (c) 2010 gdd.net
 * All rights reserved.
 *
 * Changelog:
 * 2010-03-05 - created
 *
 */

session_start();
$basePath = dirname( __FILE__ );
require_once ($basePath . "/../common_funcs.php");
if ((has_portlet_access($_SESSION['username'], 'Portlet User Permissions') == TRUE) || ($_SESSION['AUTHTYPE'] == "none")) {
    $dbLink = db_connect_syslog(DBADMIN, DBADMINPW);
//    $sql = "SELECT COUNT(*) FROM (SELECT host FROM hosts where rbac(".$_SESSION['rbac'].", rbac_key) and hidden='false') AS result";
    $sql = "SELECT COUNT(*) FROM hosts AS result";
    $result = perform_query($sql, $dbLink, $_REQUEST['pageId']);
    $total = mysql_fetch_row($result);
    $count = $total[0];
    ?>

    <table border="0" width="100%" id="tbl_rbac">
        <thead>
            <tr>
                <th width="33%" style="text-align: left;"></th>
                <th width="33%" style="text-align: left;"></th>
                <th width="33%" style="text-align: left;"></th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <div id="div_action">
                        <select id="sel_action" multiple size=0>
                            <option value="rename">Rename Group</option>
                            <option value="remove">Remove</option>
                            <option value="assign">Assign</option>
                            <option value="chkaccess">Check Access</option>
                        </select>
                    </div>
                </td>
                <td style="vertical-align: top;">
                </td>
                <td style="vertical-align: top;">
                </td>
            </tr>
            <tr>
                <td>
                    <div id="div_components">
                        <select id="sel_components" multiple size=0>
                            <option value="users">Users</option>
<?php
//                            <option value="groups">Groups</option>
?>
                            <option value="hosts">Hosts</option>
                        </select>
                    </div>
                </td>
                <td style="vertical-align: top;">
                    <div id="div_users">
                        <select id="sel_users" multiple size=1>
                            <?php
                            $sql = "SELECT username FROM users where username !='local_noauth'";
                            $res = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
                            while($row = fetch_array($res)) {
                            $user = $row['username'];
                            echo "<option value=\"$user\">$user</option>\n";
                            }
                            ?>
                        </select>
                    </div>
                    <div id="div_users_single">
                        <select id="sel_users_single" multiple size=0>
                            <?php
                            $sql = "SELECT username FROM users where username !='local_noauth'";
                            $res = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
                            while($row = fetch_array($res)) {
                            $user = $row['username'];
                            echo "<option value=\"$user\">$user</option>\n";
                            }
                            ?>
                        </select>
                    </div>
                    <div id="div_groups2">
                        <select id="sel_groups2" multiple size=1>
                            <?php
                            $query = "SELECT id,rbac_bit,rbac_text FROM rbac";
                            $result = perform_query($query, $dbLink, $_SERVER['PHP_SELF']);
                            while($row = fetch_array($result)) {
                            $group = $row['rbac_text'];
                            $bit = $row['rbac_bit'];
                            echo "<option name=\"group\" value=\"$bit\">".htmlentities($group)."</option>\n";
                            }
                            ?>
                        </select>
                    </div>
                    <div id="div_togrp">
                        <select id="sel_togrp" multiple size=1>
                            <?php
                            $query = "SELECT rbac_text FROM rbac";
                            $result = perform_query($query, $dbLink, $_SERVER['PHP_SELF']);
                            while($row = fetch_array($result)) {
                            $group = $row['rbac_text'];
                            echo "<option name=\"togrp\" value=\"$group\">".htmlentities($group)."</option>\n";
                            }
                            ?>
                        </select>
                    </div>
                    <div id="div_rengroup">
                        Enter new group name:
                        <input type="text" style="width: 99%;" id="togrp" autocomplete="off">
                        <button id="btnRenGrp">Rename Group</button>
                    </div>
                    <div id="div_hosts">
                        <!-- BEGIN Large Host Selector Modal -->
                            <div id="host_dialog" title="Host Selector">
                                <?php require ($basePath . "/../grid/rbac-hosts.php");?> 
                            </div>
                        <!-- END Large Host Selector Modal -->
                    </div>
                    <div id="div_curgroups"></div>
                </td>
                <td style="vertical-align: top;">
                    <div id="div_groups">
                        <select id="sel_groups" multiple size=1>
                            <?php
                            $query = "SELECT id,rbac_bit,rbac_text FROM rbac";
                            $result = perform_query($query, $dbLink, $_SERVER['PHP_SELF']);
                            while($row = fetch_array($result)) {
                            $group = $row['rbac_text'];
                            $bit = $row['rbac_bit'];
                            echo "<option name=\"group\" value=\"$bit\">".htmlentities($group)."</option>\n";
                            }
                            ?>
                        </select>
                    </div>
                    <div id="div_groups_single">
                        <select id="sel_groups_single" multiple size=0>
                            <?php
                            $sql = "SELECT rbac_text FROM rbac";
                            $res = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
                            while($row = fetch_array($res)) {
                            $group = $row['rbac_text'];
                            echo "<option value=\"$group\">$group</option>\n";
                            }
                            ?>
                        </select>
                    </div>
                    <!--
                    <div id="div_delgrp">
                        <select id="sel_delgrp" multiple size=1>
                            <?php
                            $query = "SELECT id,rbac_bit,rbac_text FROM rbac where rbac_bit>0";
                            $result = perform_query($query, $dbLink, $_SERVER['PHP_SELF']);
                            while($row = fetch_array($result)) {
                            $id = $row['id'];
                            $group = $row['rbac_text'];
                            echo "<option name=\"group\" value=\"$group\">".htmlentities($group)."</option>\n";
                            }
                            ?>
                        </select>
                    </div>
                    -->
                </td>
            </tr>
            <tr>
                <td style="vertical-align: top;">
                </td>
                <td style="vertical-align: top;">
                </td>
                <td style="vertical-align: top;">
                    <button id="btnRmUser">Remove User(s)</button>
                    <!--<button id="btnRmGrp">Remove Group(s)</button>-->
                    <button style=" position: relative; left: 50%;" id="btnSubmit">Submit</button>
                </td>
            </tr>
        </tbody>
    </table>
<!-- BEGIN Notification bars -->
<script type="text/javascript">
// [[ticket:335]] had to add functions here fromm js_footer
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
</script>
<script type="text/javascript"> 
    $(document).ready(function(){
            var action;
            var component;
            var hostcount = '<?php echo $count?>';
            function hideAll() {
            // Hide dropdowns until ready
            $("#div_components").hide();
            $("#div_users").hide();
            $("#div_users_single").hide();
            $("#div_groups_single").hide();
            $("#div_groups2").hide();
            $("#div_groups").hide();
            $("#div_togrp").hide();
            $("#div_hosts").hide();
            $("#div_delgrp").hide();
            $("#div_rengroup").hide();
            $("#div_curgroups").hide();
            $("#btnRmUser").hide();
            $("#btnRmGrp").hide();
            $("#btnSubmit").hide();
            $("#btnRenGrp").hide();
            }
            hideAll();

            // Change select boxes to nice looking ones
            $("#sel_action").multiselect({
                show: ["blind", 200],
                hide: ["blind", 500],
                multiple: false,
                selectedList: 1,
                noneSelectedText: "Select Action",
                open: function(event, ui) {
                hideAll();
                },
                close: function(event, ui) {
                    var arr = [];
                    $('#sel_action :selected').each(function(i, selected) {
                        arr[i] = $(selected).val();
                    });
                    for(var i=0; i<arr.length; i++){
                        switch (arr[i]) {
                        case "rename":
                        $("#btnRenGrp").show("slide");
                        $("#div_rengroup").show("slide");
                        $("#div_togrp").show("slide");
                        action = arr[i];
                        break;
                        case "remove":
                        $("#div_components").show();
                        action = arr[i];
                        break;
                        case "assign":
                        $("#div_components").show();
                        action = arr[i];
                        break;
                        case "chkaccess":
                        $("#div_components").show();
                        action = arr[i];
                        break;
                        }
                    }
                }
            });
            $("#sel_components").multiselect({
                show: ["blind", 200],
                hide: ["blind", 500],
                multiple: false,
                selectedList: 1,
                noneSelectedText: "Select Component",
                open: function(event, ui) {
                    var w = $("#sel_components").next().width();
                    $(".ui-multiselect-menu").css("width",w);
                },
                close: function(event, ui) {
                    hideAll();
                    var arr = [];
                    $('#sel_components :selected').each(function(i, selected) {
                        arr[i] = $(selected).val();
                    });
                    for(var i=0; i<arr.length; i++){
                        switch (arr[i]) {
                            case "users":
                                component = arr[i];
                                $(".descriptions").remove();
                                if (action === "remove") {
                                    $("#div_users").prepend("<span class='descriptions'>Remove one or more </span>");
                                    $("#div_groups").prepend("<span class='descriptions'>From one or more </span>");
                                $("#div_users").show("slide", 500);
                                $("#div_groups").show("slide", 500);
                                $("#btnSubmit").show("slide", 500);
                                }
                                if (action === "assign") {
                                    $("#div_users").prepend("<span class='descriptions'>Assign one or more </span>");
                                    $("#div_groups").prepend("<span class='descriptions'>To one or more </span>");
                                $("#div_users").show("slide", 500);
                                $("#div_groups").show("slide", 500);
                                $("#btnSubmit").show("slide", 500);
                                }
                                if (action === "chkaccess") {
                                    $("#div_users_single").show("slide", 500);
                                    $("#div_users_single").prepend("<span class='descriptions'>Select </span>");
                                }
                            break;
                            case "groups":
                                component = arr[i];
                                $(".descriptions").remove();
                                if (action === "remove") {
                                    $("#div_groups").prepend("<span class='descriptions'>Remove one or more</span>");
                                    $("#div_groups").show("slide", 500);
                                    $("#btnSubmit").show("slide", 500);
                                }
                                if (action === "assign") {
                                    $("#div_groups2").prepend("<span class='descriptions'>Assign one or more </span>");
                                    $("#div_groups").prepend("<span class='descriptions'>To one or more </span>");
                                    $("#div_groups2").show("slide", 500);
                                    $("#div_groups").show("slide", 500);
                                    $("#btnSubmit").show("slide", 500);
                                }
                                if (action === "chkaccess") {
                                    $("#div_groups_single").prepend("<span class='descriptions'>Select </span>");
                                    $("#div_groups_single").show("slide", 500);
                                }
                            break;
                            case "hosts":
                                component = arr[i];
                                $(".descriptions").remove();
                                if (action === "remove") {
                                $("#div_hosts").prepend("<span class='descriptions'>Remove one or more </span>");
                                $("#div_groups").prepend("<span class='descriptions'>From one or more </span>");
                                $("#div_groups").show("slide", 500);
                                $("#div_hosts").show("slide", 500);
                                $("#btnSubmit").show("slide", 500);
                                }
                                if (action === "assign") {
                                $("#div_hosts").prepend("<span class='descriptions'>Add one or more </span>");
                                $("#div_groups").prepend("<span class='descriptions'>To one or more </span>");
                                $("#div_groups").show("slide", 500);
                                $("#div_hosts").show("slide", 500);
                                $("#btnSubmit").show("slide", 500);
                                }
                                if (action === "chkaccess") {
                                    $("#div_hosts").prepend("<span class='descriptions'>Select </span>");
                                    $("#div_hosts").show("slide", 500);
                                    $("#btnSubmit").show("slide", 500);
                                }
                            break;
                        } // end switch
                    } // end for loop
                    $("#div_components").hide();
                } // end close function
            }); // end multiselect
            $("#sel_groups").multiselect({
                show: ["blind", 200],
                hide: ["blind", 500],
                selectedList: 2,
                noneSelectedText: "Groups",
            });
            $("#sel_users").multiselect({
                show: ["blind", 200],
                hide: ["blind", 500],
                selectedList: 2,
                noneSelectedText: "Users",
            }); // end multiselect
            $("#sel_groups_single").multiselect({
                show: ["blind", 200],
                hide: ["blind", 500],
                multiple: false,
                selectedList: 1,
                noneSelectedText: "Group",
                close: function(event, ui) {
                    var arr = [];
                        $("#div_curgroups").html('<div id="div_curgroups"></div>');
                    $('#sel_groups_single :selected').each(function(i, selected) {
                        arr[i] = $(selected).val();
                    });
                        for(var i=0; i<arr.length; i++){
                        $.get("includes/ajax/rbac.php?action=chkaccess&type=group&group="+ arr[i], function(data){
                        $("#div_curgroups").show("slide");
                        $("#btnSubmit").hide();
                             notify(data);
                            });
                    }
                } // end close function
            }); // end multiselect
            $("#sel_users_single").multiselect({
                show: ["blind", 200],
                hide: ["blind", 500],
                multiple: false,
                selectedList: 1,
                noneSelectedText: "User",
                close: function(event, ui) {
                    var arr = [];
                        $("#div_curgroups").html('<div id="div_curgroups"></div>');
                    $('#sel_users_single :selected').each(function(i, selected) {
                        arr[i] = $(selected).val();
                    });
                        for(var i=0; i<arr.length; i++){
                         // alert(arr[i]);
                        $.get("includes/ajax/rbac.php?action=chkaccess&type=user&user="+ arr[i], function(data){
                        $("#div_curgroups").show("slide");
                        // $("#div_curgroups").html(data);
                        $("#btnSubmit").hide();
                             notify(data);
                            });
                    }
                } // end close function
            }); // end multiselect
            $("#sel_togrp").multiselect({
                show: ["blind", 200],
                hide: ["blind", 500],
                multiple: false,
                selectedList: 1,
                noneSelectedText: "Group",
            }); // end multiselect
            $("#sel_groups2").multiselect({
                show: ["blind", 200],
                hide: ["blind", 500],
                selectedList: 2,
                noneSelectedText: "Groups",
            });


// Make the buttons jQuery styled buttons
$("button").button();

// Set click action for buttons
$("#btnSubmit").click( function() { 
        // alert("About to " + action + " " + component);
        var users = []; 
        $('#sel_users :selected').each(function(i, selected){ 
            users[i] = $(selected).text(); 
            });
        var group_bits = []; 
        $('#sel_groups :selected').each(function(i, selected){ 
            group_bits[i] = $(selected).val(); 
            });
        var groups = []; 
        $('#sel_groups :selected').each(function(i, selected){ 
            groups[i] = $(selected).text(); 
            });
        var hosts = []; 
        hosts = $("#hostsgrid").jqGrid('getGridParam','selarrrow'); 
        var groups2 = []; 
        $('#sel_groups2 :selected').each(function(i, selected){ 
            groups2[i] = $(selected).text(); 
            });
        var uri = "action=" + action + "&component=" + component + "&users="+ users +"&group_bits="+group_bits+"&groups="+groups +"&hosts="+hosts +"&groups2="+groups2;
        $.ajax({
                url: "includes/ajax/rbac.php",
                data: uri,
                type: "POST",
                proccessData: false, 
                success: function(data){
                notify(data);
                }
                });
});
$("#btnRenGrp").click( function() { 
        var group = "";
        var togrp = $("#togrp").val();
        $("#sel_togrp option:selected").each(function () {
            group += $(this).val();
            });
        var uri = "action=rename&type=group&group="+group+"&togrp="+togrp
        $.ajax({
                url: "includes/ajax/rbac.php",
                data: uri,
                type: "POST",
                proccessData: false, 
                success: function(data){
        var pieces = data.split(',');
        var res = pieces[0];
        var bit = pieces[1];
        $("#sel_togrp option[value='"+group+"']").replaceWith("<option value="+togrp+">"+togrp+"</option>");
        $("#sel_togrp").multiselect("refresh");
            $("#div_rengrp").show("slide");
            $("#btnSubmit").hide();
        $("#sel_groups option[value='"+bit+"']").replaceWith("<option value="+bit+">"+togrp+"</option>");
        $("#sel_groups").multiselect("refresh");
                notify(res);
                }
                });
        }); // end btn
}); // end doc ready
</script>
<?php } else { ?>
<script type="text/javascript">
    $('#portlet_Group_Assignments').remove()
    </script>
    <?php } ?>
