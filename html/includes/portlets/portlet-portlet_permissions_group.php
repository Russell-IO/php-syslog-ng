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
if ((has_portlet_access($_SESSION['username'], 'Portlet Group Permissions') == TRUE) || ($_SESSION['AUTHTYPE'] == "none")) {
$dbLink = db_connect_syslog(DBADMIN, DBADMINPW);
?>

<table border="0" width="100%" id="tbl_portlet_group">
<thead>
  <tr>
    <th width="1%"><input name="chk_portlet_groups_all" type="checkbox" onclick="togglePortlet_Groups(this.checked);" /></th>
    <th width="75%" style="text-align: left;">Portlet</th>
    <th width="25%" style="text-align: left;"> Group</th>
  </tr>
</thead>
  <tbody>
        <?php
	    $query = "SELECT header, group_access FROM ui_layout GROUP BY header ORDER BY header ASC";
	    $result = perform_query($query, $dbLink, $_SERVER['PHP_SELF']);
	    while($row = fetch_array($result)) {
            $header = $row['header'];
            $group_access = $row['group_access'];
            ?>
    <tr>
        <td>
            <input name="chk_portlet_groups" class="chk_portlet_groups" type='checkbox' value='<?php echo $header?>'>
        </td>
        <td>
            <?php 
            if ($group_access == 'admins') { 
                echo "<font color=\"red\">$header</font>";
            } else {
                echo $header;
            }
            ?>
        </td>
        <td>
            <select class="chzn-select" id="sel_portlet_groups">
        <?php
	    $sql = "SELECT DISTINCT(groupname) FROM groups";
	    $res = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
        while($line = fetch_array($res)) {
            $group = $line['groupname'];
                if (preg_match("/$group/", $group_access)) {
                    echo "<option selected value=\"$group\">$group</option>\n";
                } else {
                    echo "<option value=\"$group\">$group</option>\n";
                }
            }
            ?>
            </select>
        </td>
    </tr>
        <?php } ?>
    <tr>
    <td>
    </td>
        <td>
        <div style="position: relative; left: 25%;">
        <input class='ui-state-default ui-corner-all' id="btnPortletGrpPerm" type="submit" value="Assign Permissions">
        </div>
        </td>
    </tr>
  </tbody>
</table>
<?php
} else { ?>
<script type="text/javascript">
$('#portlet_Portlet_Group_Permissions').remove()
</script>
<?php } ?>
<script type="text/javascript">
$(function(){
        $("#btnPortletGrpPerm").click( function() { 
           var chk ="";
           var both ="";
           var all ="";
           if ($('#chk_portlet_groups_allusers').attr('checked')) {
           all = "on";
           } else {
           all = "off";
           };
           $('#tbl_portlet_group input:checked').each(function() {
               var val="";
               chk = $(this).val() + "=";
               $("select option:selected",$(this).closest("tr")).each(function(){
                   val += "," + $(this).val();
                   });
               val = val.substring(1);
               chk = chk.replace(/ /g, "_");
               both += "&"+ chk + val;
               });
           both = both.substring(1);
           both = both.replace(/on=&/g, "");
           //  alert( both );
           // alert( all );
           if (both !== "") {
           $.get("includes/ajax/json.useradmin.php?action=portlet_group_perm&all="+all+"&"+both, function(data){
                   notify(data);
                   });
                        $('input[name=chk_portlet_groups_allusers]').attr('checked', false);
                        $('input[name=chk_portlet_groups]').attr('checked', false);
                        $('input[name=chk_portlet_groups_all]').attr('checked', false);
           } else {
                   error("You haven't selected anything");
           };
        });
        $("#chk_portlet_groups_allusers").click( function() { 
                if ($('#chk_portlet_groups_allusers').attr('checked')) {
                warn('Selecting this box will reset group permissions for ALL users (except <?php echo $_SESSION['ADMIN_NAME']?>)');
                };
                });
});
$("#portlet-header_Portlet_Group_Permissions").append('<span style="float: right;">ALL<input type="checkbox" id="chk_portlet_groups_allusers" name="chk_portlet_groups_allusers"></span>');
</script>
