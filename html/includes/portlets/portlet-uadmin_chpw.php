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

if ((has_portlet_access($_SESSION['username'], 'Change Password') == TRUE) || ($_SESSION['AUTHTYPE'] == "none")) { 
?>
<table border="0" width="100%">
<thead>
  <tr>
    <th></th>
    <th></th>
    <th></th>
  <tr>
</thead>

<tbody>
    <tr>
        <td colspan="2">
            <?php
            if (getgroup($_SESSION['username']) == "admins") {
                echo "<select class=\"chzn-select\" style=\"width:102%\" id=\"sel_user\">\n";
                $query = "SELECT * FROM ".$_SESSION['TBL_AUTH'] ." WHERE username !='local_noauth'";
                $result = perform_query($query, $dbLink, $_SERVER['PHP_SELF']);
                while($row = fetch_array($result)) {
                    $chpw_user = $row['username'];
                    echo "<option name=\"chpw_user\ value=\"$chpw_user\">".htmlentities($chpw_user)."</option>\n";
                }
                echo "</select>\n";
            } else {
                $query = "SELECT * FROM ".$_SESSION['TBL_AUTH'] ." WHERE username ='$_SESSION[username]'";
                $result = perform_query($query, $dbLink, $_SERVER['PHP_SELF']);
                while($row = fetch_array($result)) {
                    $chpw_user = $row['username'];
                    echo "<input type=\"hidden\" id=\"inp_user\" value=\"$chpw_user\">\n";
                    echo "Change Password for ".htmlentities($chpw_user)."\n";
                }
            }
            ?>
        </td>
    </tr>
        <?php
        if (getgroup($_SESSION['username']) != "admins") {
        ?>
    <tr>
        <td width="33%">
		    Old password:
		</td>
        <td colspan="2">
		    <input type="password" style="width: 48%;" id="oldpw" autocomplete="off">
		</td>
    </tr>
        <?php } ?>
    <tr>
        <td width="33%">
		    Password:
		</td>
        <td colspan="2">
		    <input type="password" style="width: 48%;" id="newpw1" autocomplete="off">
		</td>
    </tr>
    <tr>
        <td width="33%">
		    Confirm:
		</td>
        <td colspan="2">
		    <input type="password" style="width: 48%;" id="newpw2" autocomplete="off">
		</td>
    </tr>
</tbody>
</table>

<table width="100%" border="0">
<?php if ($_SESSION['colwidth'] < 100) {
    echo "<td width=\"".$_SESSION['colwidth']."%\"\n";
} else { 
    echo "<td width=\"33%\">\n";
} 
?>
    </td>
    <td>
        <input class='ui-state-default ui-corner-all' id="btnChpw" type="submit" value="Change Password">
    </td>
</table>
<?php } else { ?>
<script type="text/javascript">
$('#portlet_Change_Password').remove()
</script>
<?php } ?>
<script type="text/javascript">
$(document).ready(function(){
$("#btnChpw").click( function() { 
        var chpw_user = $('#sel_user :selected').text();
        if (chpw_user == '') {
        chpw_user = $('#inp_user').val();
        }
        var oldpw = $('#oldpw').val();
        var newpw1 = $('#newpw1').val();
        var newpw2 = $('#newpw2').val();
        if ((chpw_user != '') && (newpw1 != '')) {
        $.get("includes/ajax/json.useradmin.php?action=chpw&chpw_user="+chpw_user+"&oldpw="+oldpw+"&newpw1="+newpw1+"&newpw2="+newpw2, function(data){
             notify(data);
             });
        oldpw = $('#oldpw').val("");
        newpw1 = $('#newpw1').val("");
        newpw2 = $('#newpw2').val("");
        } else {
           error("Missing Password");
        };
        }); 
}); // end doc ready
</script>
