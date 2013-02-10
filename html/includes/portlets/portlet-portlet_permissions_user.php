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
if ((has_portlet_access($_SESSION['username'], 'Portlet User Permissions') == TRUE) || ($_SESSION['AUTHTYPE'] == "none")) {
   	$dbLink = db_connect_syslog(DBADMIN, DBADMINPW);
?>

<table border="0" width="100%" id="tbl_portlet_user_perms">
<thead>
  <tr>
    <th width="40%">
            <select style="width: 100%;" class="sel_portlet_user_perms_user" id="sel_portlet_user_perms_user">
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
    </th>
    <th width="60%" style="text-align: left;"><input name="chk_portlet_user_perms_all" type="checkbox" onclick="toggleCheck(this.checked);">Portlet</th>
  </tr>
</thead>
  <tbody>
        <?php
	    $query = "SELECT header, group_access FROM ui_layout GROUP BY header ORDER BY header ASC";
	    $result = perform_query($query, $dbLink, $_SERVER['PHP_SELF']);
	    while($row = fetch_array($result)) {
            $header = $row['header'];
            $group_access = $row['group_access'];
            $option_selected = $row['group_access'];
            $sheader = str_replace(" ", "_", $header);
            ?>
    <tr>
        <td>
        </td>
        <td>
        <?php if (has_portlet_access($_SESSION['username'], $header) == TRUE) { ?>
            <input checked="yes" class="checkbox" type='checkbox' id='chk_portlet_user_perms' name="<?php echo $sheader?>" value='<?php echo $header?>'>
                <?php } else { ?>
            <input class="checkbox" type='checkbox' id='chk_portlet_user_perms' name="<?php echo $sheader?>" value='<?php echo $header?>'>
                <?php } ?>
            <?php 
            if ($group_access == 'admins') { 
                echo "<font color=\"red\">$header</font>";
            } else {
                echo $header;
            }
            ?>
        </td>
    </tr>
        <?php } // END while loop ?>
    <tr>
        <td colspan="2">
        <div style="position: relative; left: 25%;">
        <input class='ui-state-default ui-corner-all' id="btnPortletUserPerms" type="submit" value="Assign Permissions">
        </div>
        </td>
    </tr>
  </tbody>
</table>
<?php
} else { ?>
<script type="text/javascript">
$('#portlet_Portlet_User_Permissions').remove()
</script>
<?php } ?>
<script type="text/javascript">

$(function(){
        $("#btnPortletUserPerms").click( function() { 
           var header = "";
           var both = "";
           var header_name_and_value = "";
           var user = $("#sel_portlet_user_perms_user").val();
           $('#tbl_portlet_user_perms input').each(function() {
               header = $(this).val();
               var name = header.replace(/ /g, "_");
                if ($('input:checked[name='+name+']').val() == ''+header+'') {
                  //alert(name);
                   header_name_and_value += "," + name + '=true';
                 } else {
                   header_name_and_value += "," + name + '=false';
                 };
           });
               header_name_and_value = header_name_and_value.replace(/,on=false/g, "");
               header_name_and_value = header_name_and_value.replace(/^,/g, "");
// alert (header_name_and_value);
         $.get("includes/ajax/json.useradmin.php?action=portlet_user_perm&user="+ user +"&portlets="+header_name_and_value, function(data){
              notify(data);
              });
        });
});
function getperms(user){
        var headers = "";
           $('#tbl_portlet_user_perms input').each(function() {
               headers += "," + $(this).val();
               });
           headers = headers.substring(1);
           headers = headers.replace(/^on,/, "");
           // alert( headers);
                        $.ajax({
                        url: "includes/ajax/json.useradmin.php?action=portlet_user_perm_getperm&user="+ user +"&headers="+headers,
                        dataType: 'json',
                        success: function(data) {
                        <?php
                        $query = "SELECT header FROM ui_layout GROUP BY header ORDER BY header ASC";
                        $result = perform_query($query, $dbLink, $_SERVER['PHP_SELF']);
                        while($row = fetch_array($result)) {
                        $header = $row['header'];
                        $header = str_replace(" ", "_", $header);
                        $val = $header;
                        echo "var $val = data.$header\n";
                        //echo "alert (\"$header = \" + $val);\n";
                        echo "if ($val == \"true\") {\n";
                        echo "$('input[name=$header]').attr('checked', true);\n";
                        echo "} else {\n";
                        echo "$('input[name=$header]').attr('checked', false);\n";
                        echo "}\n";
                        }
                        ?>
                        }
                        });
                        // Clear the check all box if it was checked
                        $('input[name=chk_portlet_user_perms_all]').attr('checked', false);
                        //notify("Updated");
};
$(document).ready(function(){
        var user = $('#sel_portlet_user_perms_user :selected').text();
        getperms(user);
        $('select.sel_portlet_user_perms_user').change(function(){
            var user = $(this).val();
            getperms(user);
            });
        });
</script>
