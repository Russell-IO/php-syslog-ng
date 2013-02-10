<?php
/*
 *
 * Developed by Clayton Dukes <cdukes@logzilla.pro>
 * Copyright (c) 2010 LogZilla, LLC
 * All rights reserved.
 * Last updated on 2010-06-15
 *
 * Changelog:
 * 2010-03-01 - created
 *
 */

session_start();
$basePath = dirname( __FILE__ );
require_once ($basePath . "/../common_funcs.php");
$dbLink = db_connect_syslog(DBADMIN, DBADMINPW);
$action = get_input('action');

switch ($action) {
    case "deluser":
        $del_user = get_input('del_user');
    $sql = "SELECT COUNT(*) FROM ".$_SESSION['TBL_AUTH'];
    $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
    $rowCount = fetch_array($result);
    if($rowCount[0] < 2) {
        echo "The system must have at least one user!";
        break;
    }
    $sql = "SELECT COUNT(*) FROM ".$_SESSION['TBL_AUTH']." WHERE username='".$del_user."'";
    $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
    $rowCount = fetch_array($result);
    if($rowCount[0] == 0) {
        echo "$del_user does not exist!";
        break;
    }
    $sql = "DELETE FROM groups WHERE userid=(SELECT id FROM users WHERE username='$del_user')";
    perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
    $sql = "DELETE FROM ".$_SESSION['TBL_AUTH']." WHERE username='".$del_user."'";
    perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
    echo "Removed $del_user";
    break;

    case "chpw":
        $chpw_user = get_input('chpw_user');
    $oldpw = get_input('oldpw');
    $newpw1 = get_input('newpw1');
    $newpw2 = get_input('newpw2');
    if(strcmp($newpw1, $newpw2) != 0) {
        $error .= "Passwords do not match, try again.<br>";
    } 
    if (!$error) {
        if (getgroup($_SESSION['username']) != "admins") {
            // Make sure the old oldpw is correct
            $oldpwHash = md5($oldpw);
            $sql = "SELECT * FROM ".$_SESSION['TBL_AUTH']." WHERE username='".$chpw_user."'
                AND pwhash='".$oldpwHash."'";
            $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
            if(mysql_affected_rows() != 1) {
                $error .= "Old password is incorrect.<br>";
            }
        }
        // Change password
        $newpwHash = md5($newpw1);
        $sql = "UPDATE ".$_SESSION['TBL_AUTH']." SET pwhash='".$newpwHash."' WHERE
            username='".$chpw_user."'";

        perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
        if(mysql_affected_rows() == 1) {
            $success .= "Updated password for $chpw_user.";
        }
    }
    if ($error) {
        echo "$error";
    } else {
        echo $success;
    }
    break;

    case "adduser":
        $nu = get_input('nu');
    $nupw = get_input('nupw');
    $nupwcnf = get_input('nupwcnf');
    $group = get_input('group');
    if (strlen($nu) > 15) {
        $error .= "Usernames must be 15 characters or less.<br>";
    }
    if(strcmp($nupw, $nupwcnf) != 0) {
        $error .= "Passwords do not match.<br>";
    } 
    if (!$error) {
        // Make sure user doesn't already exist
        $sql = "SELECT * FROM ".$_SESSION['TBL_AUTH']." WHERE username='".$nu."'";
        $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
        if(mysql_affected_rows() == 1) {
            $error .= "$nu already exists.<br>";
        }
        // Add user
        $nuHash = md5($nupw);
        $sql = "INSERT INTO ".$_SESSION['TBL_AUTH']." (username,pwhash,rbac_key) VALUES ('$nu','$nuHash',(SELECT value FROM settings WHERE name='RBAC_ALLOW_DEFAULT'))";
        $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
        if(mysql_affected_rows() == 1) {
            $success .= "Added $nu to users";
        }
        $sql = "SELECT userid FROM groups WHERE userid=(SELECT id FROM users WHERE username='$nu')";
        $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
        if(num_rows($result) == 1) {
            $error .= "$nu is already assigned to that group.<br>";
        } else {
            $sql = "INSERT INTO groups (userid, groupname) SELECT (SELECT id FROM users WHERE username='$nu'),'$group'";
            perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
            $sql = "REPLACE INTO ui_layout (userid, pagename, col, rowindex, header, content, group_access) SELECT (SELECT id FROM users WHERE username='$nu'),pagename,col,rowindex,header,content, group_access FROM ui_layout WHERE userid=0";
            perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
            $success .= " and assigned them to the $group group";
        }
        echo $success;
    } else {
        echo "$error";
    }
    break;

    case "addgrp":
        $grp_add = get_input('grp_add');
    if (strlen($grp_add) > 15) {
        $error .= "Group names must be 15 characters or less.<br>";
    }
    $sql = "SELECT id FROM groups WHERE groupname='$grp_add'";
    $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
    if(mysql_affected_rows() == 1) {
        $error .= "$grp_add already exists.<br>";
    }
    if (!$error) {
        $sql = "INSERT INTO groups (groupname) VALUES ('$grp_add')";
        perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
        if(mysql_affected_rows() == 1) {
            $success .= "Added the $grp_add group";
        } else {
            $error .= mysql_error();
        }
    }
    if ($error) {
        echo "$error";
    } else {
        echo $success;
    }
    break;

    case "delgrp":
        $grp_del = get_input('grp_del');
    if ($grp_del == "admins") {
        echo "Can't delete the \"admins\" group!";
        break;
    }
    $sql = "DELETE FROM groups where groupname='$grp_del'";
    perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
    echo "Deleted the $grp_del group";
    break;

    case "group_assignments":
        $users = get_input('users');
    // $groups should only be one group, but listed here as multiple for future changes (multi-group assignments per user)
    $groups = get_input('groups'); 
    $pieces = explode(",", $users);
    foreach($pieces as $user) {
        // echo "u = $user<br>";
        $pieces = explode(",", $groups);
        foreach($pieces as $group) {
            // echo "g = $group<br>";
        }
        if (getgroup($user) !== "$group") {
        $sql = "UPDATE groups SET groupname='$group' WHERE userid=(SELECT id FROM users WHERE username='$user')";
        $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
        // echo "aff = " .mysql_affected_rows();
        if(mysql_affected_rows() != 1) {
            $sql = "REPLACE INTO groups (userid, groupname) SELECT (SELECT id FROM users WHERE username='$user'), '$group'";
            perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
        }
        echo "Assigned $user to $group<br>";
        } else {
            echo "$user is already assigned to $group<br>";
        }
    }
    break;

    case "reset_layout":
        reset_layout($_SESSION['username']);
    echo "Your UI Layout has been reset.";
    break;

    case "portlet_group_perm":
        $all = get_input('all');
    if ($all == "off") {
        $AND = "AND userid=0";
    }
    // echo "ALL = $all<br>";
    foreach($_GET as $key => $arrValue) {
        $value[$key] = addslashes($arrValue);
        $header = str_replace("_", " ", $key);
        $group = $value[$key];
        // echo "$header = $group<br>";
        if((stristr($header, 'action') === FALSE) && (stristr($header, 'all') === FALSE)) {
            if ($AND) {
                $sql = "UPDATE ui_layout SET group_access='$group' WHERE header='$header' $AND";
                $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
            } else {
                $sql = "SELECT username from users where username !='local_noauth'";
                $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
                while($row = fetch_array($result)) {
                    $user = $row['username'];
                    if ($user !== $_SESSION['ADMIN_NAME']) { // Don't replace the main site admin's permission :-)
                        $query = "REPLACE INTO ui_layout (userid, pagename, col, header, group_access, content) SELECT (SELECT id FROM users WHERE username='$user'),(SELECT DISTINCT pagename from ui_layout where header='$header'), (SELECT DISTINCT col from ui_layout where header='$header'), '$header', '$group', (SELECT DISTINCT content from ui_layout where header='$header')";
                        perform_query($query, $dbLink, $_SERVER['PHP_SELF']);
                    }
                }
            }
        }
    }
    if ($AND == '') {
        echo "Updated Portlet Group Permissions for ALL users";
    } else {
        echo "Updated Portlet Group Permissions for the template user";
    }
    break;

    case "portlet_user_perm":
        $user = get_input('user');
    $portlets = get_input('portlets');
    $pieces = explode(",", $portlets);
    foreach($pieces as $portlet) {
        $header = str_replace("_", " ", $portlet);
        $value = preg_replace('/.*=(.*)/', '$1', $portlet);
        $header = preg_replace('/(.*)=.*/', '$1', $header);
        $group = getgroup($user);
        if ($header !== "Assign Permissions") { // Button name comes through because it's an "input" type
            if ($value == 'true') {
                logmsg("---\nHeader = $header\nValue = $value");
                $sql = "UPDATE ui_layout SET group_access='$group' WHERE header='$header' and userid=(SELECT id FROM users WHERE username='$user')";
                $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
                // logmsg("MySQL Affected Rows = " .mysql_affected_rows() . "\n");
                if(mysql_affected_rows() != 1) {
                    // User doesn't have an entry (they are probably a new user who hasn't logged in yet)
                    // so we need to insert permissions for them.
                    $sql = "REPLACE INTO ui_layout (userid, pagename, col, header, group_access, content) SELECT (SELECT id FROM users WHERE username='$user'),(SELECT DISTINCT pagename from ui_layout where header='$header' AND userid=0), (SELECT DISTINCT col from ui_layout where header='$header' AND userid=0), '$header', '$group', (SELECT DISTINCT content from ui_layout where header='$header' and userid=0)";
                    perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
                }
            } else {
                $sql = "DELETE FROM ui_layout WHERE group_access='$group' AND header='$header' and userid=(SELECT id FROM users WHERE username='$user')";
                // echo "Removed $header access for $user<br>";
                $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
            }
        }
    }
    echo "Updated Portlet Access for $user<br>";
    break;

    case "portlet_user_perm_getperm":
        $user = get_input('user');
    $headers = get_input('headers');
    $pieces = explode(",", $headers);
    foreach($pieces as $header) {
        $header = str_replace("_", " ", $header);
        if (has_portlet_access($user, $header) == TRUE) {
            $header = str_replace(" ", "_", $header);
            $data->$header = "true";
        } else {
            // Note: didn't really need to return false below, just did it so 
            // the ajax wouldn't throw a null when the user had no access.
            $header = str_replace(" ", "_", $header);
            $data->$header = "false";
        }
    }
    echo json_encode($data);
    break;

    case "group_assignments_getgroup":
        $users = get_input('users');
    $pieces = explode(",", $users);
    foreach($pieces as $user) {
        $user = $user; // just set to last user select for now since the select box for groups is not a multi-select
    }
    echo getgroup($user);
    break;
}
?>
