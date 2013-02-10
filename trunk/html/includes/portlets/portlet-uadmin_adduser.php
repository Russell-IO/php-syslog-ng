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
if ((has_portlet_access($_SESSION['username'], 'Add User') == TRUE) || ($_SESSION['AUTHTYPE'] == "none")) {
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
            New Username:
        </td>
        <td colspan="2">
		    <input type="text" style="width: 48%;" id="inp_nu" autocomplete="off">
		</td>
    </tr>
    <tr>
        <td width="33%">
		    New Password:
		</td>
        <td colspan="2">
		    <input type="password" style="width: 48%;" id="inp_nupw" autocomplete="off">
		</td>
    </tr>
    <tr>
        <td width="33%">
		    Confirm:
		</td>
        <td colspan="2">
		    <input type="password" style="width: 48%;" id="inp_nupwcnf" autocomplete="off">
		</td>
    </tr>
    <tr>
        <td width="33%">
		    Group:
		</td>
        <td colspan="2">
        <select class="chzn-select" style="width:100%" id="sel_group">
        <?php
        $query = "SELECT DISTINCT(groupname) FROM groups";
        $result = perform_query($query, $dbLink, $_SERVER['PHP_SELF']);
        while($row = fetch_array($result)) {
            $group = $row['groupname'];
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
        <input class='ui-state-default ui-corner-all' id="btnAddUser" type="submit" value="Add User">
    </td>
</table>
<input type='hidden' name='page' value='User'>
<?php } else { ?>
<script type="text/javascript">
$('#portlet_Add_User').remove()
</script>
<?php } ?>
<script type="text/javascript">
$(document).ready(function(){
jQuery("#btnAddUser").click( function() { 
        var group = $('#sel_group :selected').text();
        var nu = $('#inp_nu').val();
        var nupw = $('#inp_nupw').val();
        var nupwcnf = $('#inp_nupwcnf').val();
        if ((nu !== '') && (nupw !== '')) {
        $.get("includes/ajax/json.useradmin.php?action=adduser&nu="+nu+"&nupw="+nupw+"&nupwcnf="+nupwcnf+"&group="+group, function(data){
            notify(data);
            // If no error is returned, update other selects with new user
            if(data.search(/ERROR/) == -1) { 
            $("#sel_deluser").append("<option name='del_user' value="+nu+">"+nu+"</option>");
            $("#sel_user").append("<option name='chpw_user' value="+nu+">"+nu+"</option>");
            $("#sel_portlet_user_perms_user").append("<option name='chpw_user' value="+nu+">"+nu+"</option>");
            $("#sel_user_group_assignments_userlist").append("<option value="+nu+">"+nu+"</option>");
            };
        });
        // Clear inputs
            $('#inp_nu').val("");
            $('#inp_nupw').val("");
            $('#inp_nupwcnf').val("");
        } else {
           error("Missing Username or Password");
        };
        }); 
}); // end doc ready
</script>
