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
?>

<table border="0" width="100%" id="tbl_user_group_assignments">
<thead>
  <tr>
    <th width="50%" style="text-align: left;">User</th>
    <th width="50%" style="text-align: left;">Group</th>
  </tr>
</thead>
  <tbody>
    <tr>
        <td>
            <select style="width: 100%;" class="sel_user_group_assignments_userlist" id="sel_user_group_assignments_userlist" multiple size=5>
        <?php
	    $sql = "SELECT username FROM users where username !='local_noauth'";
	    $res = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
        while($row = fetch_array($res)) {
            $user = $row['username'];
                if (preg_match("/$user/", $_SESSION['username'])) {
                    echo "<option selected value=\"$user\">$user</option>\n";
                } else {
                    echo "<option value=\"$user\">$user</option>\n";
                }
            }
            ?>
                </select>
        </td>
        <td>
            <select style="width: 100%;" class="sel_user_group_assignments_grouplist" id="sel_user_group_assignments_grouplist">
        <?php
	    $sql = "SELECT DISTINCT(groupname) FROM groups ORDER BY groupname ASC";
	    $res = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
        while($row = fetch_array($res)) {
            $group = $row['groupname'];
                if (preg_match("/$group/", getgroup($_SESSION['username']))) {
                    echo "<option selected value=\"$group\">$group</option>\n";
                } else {
                    echo "<option value=\"$group\">$group</option>\n";
                }
            }
            ?>
                </select>
        </td>
    </tr>
    <tr>
        <td colspan="2">
        <div style="position: relative; left: 25%;">
        <input class='ui-state-default ui-corner-all' id="btnUserGroupAssignments" type="submit" value="Assign To Group">
        </div>
        </td>
    </tr>
  </tbody>
</table>
<?php } else { ?>
<script type="text/javascript">
$('#portlet_Group_Assignments').remove()
</script>
<?php } ?>
<script type="text/javascript">
$(function(){
        $("#btnUserGroupAssignments").click( function() { 
           var users = $("#sel_user_group_assignments_userlist").val();
           var groups = $("#sel_user_group_assignments_grouplist").val();
         $.get("includes/ajax/json.useradmin.php?action=group_assignments&users="+ users +"&groups="+groups, function(data){
              notify(data);
              });
        });
});
$(function(){
        $('select.sel_user_group_assignments_userlist').change(function(){
            var users = $("#sel_user_group_assignments_userlist").val();
            $.get("includes/ajax/json.useradmin.php?action=group_assignments_getgroup&users="+ users, function(data){
                $("#sel_user_group_assignments_grouplist").val(data).attr("selected", "selected");
                 // notify(data);
                });
        });
});
</script>
