<?php
/*
 *
 * Developed by Clayton Dukes <cdukes@logzilla.pro>
 * Copyright (c) 2010 LogZilla, LLC
 * All rights reserved.
 * Last updated on 2010-06-15
 *
 * Changelog:
 * 2010-03-05 - created
 *
 */

session_start();
$basePath = dirname( __FILE__ );
require_once ($basePath . "/../common_funcs.php");
$dbLink = db_connect_syslog(DBADMIN, DBADMINPW);

if ((has_portlet_access($_SESSION['username'], 'Delete User') == TRUE) || ($_SESSION['AUTHTYPE'] == "none")) {
?>

<table border="0" width="100%">
    <tr>
        <td width="70%">
        <select class="chzn-select" style="width:99%" id="sel_deluser">
        <?php
	    $query = "SELECT * FROM ".$_SESSION['TBL_AUTH'] ." WHERE username !='local_noauth' AND username !='$_SESSION[username]'";
	    $result = perform_query($query, $dbLink, $_SERVER['PHP_SELF']);
	    while($row = fetch_array($result)) {
            $del_user = $row['username'];
		    echo "<option name=\"del_user\ value=\"$del_user\">".htmlentities($del_user)."</option>\n";
	    }
        ?>
        </select>
        </td>
    </tr>
    <tr>
        <td width="100%">
        <div style="position: relative; left: 25%;">
        <input class='ui-state-default ui-corner-all' id="btnDelUser" type="submit" value="Delete User">
        </div>
        </td>
    </tr>
</table>
<?php } else { ?>
<script type="text/javascript">
$('#portlet_Delete_User').remove()
</script>
<?php } ?>
<script type="text/javascript">
$(document).ready(function(){
$("#btnDelUser").click( function() { 
        var del_user = $('#sel_deluser :selected').text()
        if (del_user != '') {
        $("#sel_deluser :selected").remove();
        $.get("includes/ajax/json.useradmin.php?action=deluser&del_user="+del_user, function(data){
            notify(data);
            });
        $("#sel_user").val(del_user).attr("selected", "selected");
        $("#sel_user :selected").remove();
        $("#sel_portlet_user_perms_user").each( function() {
            $('#sel_portlet_user_perms_user').find('option:contains('+del_user+')').remove()
            });
        $("#sel_user_group_assignments_userlist").each( function() {
            $('#sel_user_group_assignments_userlist').find('option:contains('+del_user+')').remove()
            });
        } else {
           error("Missing Username");
           };
        }); 
}); // end doc ready
</script>
