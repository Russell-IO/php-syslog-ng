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
if ((has_portlet_access($_SESSION['username'], 'Groups') == TRUE) || ($_SESSION['AUTHTYPE'] == "none")) { 
$dbLink = db_connect_syslog(DBADMIN, DBADMINPW);
?>
<table border="0" width="100%">
<thead>
  <tr>
    <th></th>
    <th></th>
  <tr>
</thead>

<tbody>
    <tr>
        <td>
            Add Group:
        </td>
        <td colspan="2">
		    <input type="text" style="width: 48%;" id="rbac_groupadd" autocomplete="off">
		</td>
    </tr>
    <tr>
        <td width="33%">
		    Delete Group:
		</td>
        <td colspan="2">
        <?php
        echo "<select style=\"width:100%\" id=\"rbac_groupdelete\">\n";
        $query = "SELECT id,rbac_bit,rbac_text FROM rbac where rbac_bit>0";
        $result = perform_query($query, $dbLink, $_SERVER['PHP_SELF']);
        while($row = fetch_array($result)) {
            $group = $row['rbac_text'];
            echo "<option name=\"group\ value=\"$group\">".htmlentities($group)."</option>\n";
        }
        echo "</select>\n";
        ?>
		</td>
    </tr>
</tbody>
</table>

<table width="100%" border="0">
<td width="33%">
</td>
    <td>
        <input class='ui-state-default ui-corner-all' id="btnGroupAdd" type="submit" value="Add Group">
        <input class='ui-state-default ui-corner-all' id="btnGroupDel" type="submit" value="Delete Group">
    </td>
</table>
<?php
} else { ?>
<script type="text/javascript">
$('#portlet_Groups').remove()
</script>
<?php } ?>
<script type="text/javascript">
jQuery("#btnGroupAdd").click( function() { 
        var group = $('#rbac_groupadd').val();
        if (group != '') {
        $.get("includes/ajax/json.rbac-groups.php?action=add&name="+group, function(data){
            notify(data);
            // If no error is returned, update other selects with new user
            if(data.search(/ERROR/) == -1) { 
            $("#rbac_groupdelete").append("<option name='group' value="+group+">"+group+"</option>");
            $("#sel_group").append("<option name='group' value="+group+">"+group+"</option>");
            $("#sel_user_group_assignments_grouplist").append("<option name='group' value="+group+">"+group+"</option>");
            $("#sel_portlet_groups").each( function() {
                $(this).append("<option value="+group+">"+group+"</option>");
                });
            };
            });
        // Clear input
        $('#rbac_groupadd').val("");
        } else {
        error("Missing Group Name");
        };
        }); 
jQuery("#btnGroupDel").click( function() { 
        var grp_del = $('#rbac_groupdelete :selected').text();
        if (grp_del != '') {
        $.get("includes/ajax/json.useradmin.php?action=delgrp&grp_del="+grp_del, function(data){
            notify(data);
            });
        $("#rbac_groupdelete :selected").remove();
        $("#sel_group").val(grp_del).attr("selected", "selected");
        $("#sel_group :selected").remove();
        $("#sel_portlet_groups").each( function() {
            $('#sel_portlet_groups').find('option:contains('+grp_del+')').remove()
                });
        $("#sel_user_group_assignments_grouplist").each( function() {
            $('#sel_user_group_assignments_grouplist').find('option:contains('+grp_del+')').remove()
                });
            } else {
            error("Missing Group Name");
            };
            }); 
</script>
