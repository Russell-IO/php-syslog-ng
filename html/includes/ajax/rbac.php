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

// Possible actions are create, remove, assign
$action = get_input('action');
// Possible components are users, groups, hosts
$component = get_input('component');
$users = explode(",", get_input('users'));
$group_bits = explode(",", get_input('group_bits'));
$groups = get_input('groups');
if (!is_array($groups)) {
    $groups = explode(",", $groups);
    $groupcount = count($groups);
}
$hosts = get_input('hosts');
if (!is_array($hosts)) {
    $hosts = explode(",", $hosts);
    $hostcount = count($hosts);
}
$groups2 = get_input('groups2');
if (!is_array($groups2)) {
    $groups2 = explode(",", $groups2);
    $groups2count = count($groups2);
}
$type = get_input('type');
$user = get_input('user');
$host = get_input('host');
$group = get_input('group');
$togrp = get_input('togrp');


// Remove empty values
$users = array_filter($users);
$groups = array_filter($groups);
$hosts = array_filter($hosts);


//---use below to debug from the command line
// $hostid = (!empty($hostid)) ? $hostid : "1";
// $action = (!empty($action)) ? $action : "assign";
// $users = (!empty($users)) ? $users : array("demo","bob");
// $hosts = (!empty($hosts)) ? $hosts : array("192.168.1.1","localhost");
// $group_bits = (!empty($group_bits)) ? $group_bits : 4;
// $groups = (!empty($names)) ? $names : array("Router");


// echo "<u><b>" . ucfirst($action) . " " . ucfirst($component) . "</u></b><br>";
switch ($action) {
    case "chkaccess":
        if($user) {
            $groups = rbac_getgroups('user', $user);
            if($groups) {
                echo "User $user has access to:<br>";
                foreach ($groups as $group) {
                    echo "$group, ";
                }
            } else {
                echo "$user does not have access to any groups...<br />";
            }
        }
        if($host) {
            $groups = rbac_getgroups('host', $host);
            if($groups) {
                echo "Host $host belongs to:<br>";
                foreach ($groups as $group) {
                    $str .= "$group,";
                }
                rtrim(',', $str);
                echo "$str<br>";
            } else {
                echo "$host does not belong to any groups...<br />";
            }
        }
        if ($hostcount > 50) {
            echo "Too many hosts selected, please select < 50 at one time<br>";
        } else {
            foreach ($hosts as $host) {
                $groups = rbac_getgroups('host', $host);
                if($groups) {
                    foreach ($groups as $group) {
                        $str .= "$host belongs to $group,";
                    }
                } else {
                    echo "$host does not belong to any groups...<br />";
                }
            }
                    rtrim(',', $str);
                    echo "$str<br>";
        }
        //        if($group) {
        //            $groups = rbac_getgroups('group', $group);
        //            if($groups) {
        //                echo "The \"$group\" group belongs to these other groups:<br />";
        //                foreach ($groups as $gname) {
        //                    if($group != $gname) {
        //                    echo "$gname<br />";
        //                    }
        //                }
        //            } else {
        //                echo "$group does not belong to any groups...<br />";
        //            }
        //        }
        break;
    case "remove":
        switch ($component) {
            case "users":
                foreach ($users as $user) {
                    if($user) {
                        foreach ($groups as $group) {
                            $sql = "SELECT rbac_bit FROM rbac WHERE rbac_text='$group'";
                            $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
                            while($row = fetch_array($result)) {
                                $bit = $row[0];
                            }
                            // echo "$group = $bit";
                            $sql = "SELECT rbac_key FROM users WHERE username='$user'";
                            $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
                            if ($result) {
                                while($row = fetch_array($result)) {
                                    $userkey = $row[0];
                                }
                            } else {
                                echo "Error" . mysql_error() . "<br />";
                            }
                            // echo "$user has $userkey key";
                            if($bit) {
                                $key = pow(2, $bit);
                                // echo "Key = $key<br />";
                                $newkey = $userkey - $key;
                                // echo "New key = $newkey<br />";
                                if ($key > 0) {
                                    $sql = "UPDATE users set rbac_key=$newkey WHERE username='$user'";
                                    $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
                                } else {
                                    echo "User doesn't have access to the $group group<br />";
                                    break;
                                }
                                if ($result) {
                                    echo "Removed $user from $group<br>";
                                }
                            }
                        }
                    } else {
                        echo "Did you forget to select a user?<br />";
                    }
                }
                break;
                /* removed groups of groups
                   case "groups":
                   foreach ($groups as $group) {
                   if($group) {
                   echo "removed $group<br>";
                   } else {
                   echo "Did you forget to select a group?";
                   }
                   }
                   break;
                 */
            case "hosts":
                if ($hostcount > 50) {
                    echo "Too many hosts selected, please select < 50 at one time<br>";
                } else {
                    foreach ($hosts as $host) {
                        if($host) {
                            foreach ($groups as $group) {
                                $sql = "SELECT rbac_bit FROM rbac WHERE rbac_text='$group'";
                                $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
                                while($row = fetch_array($result)) {
                                    $bit = $row[0];
                                }
                                // echo "$group bit is $bit\n<br>";
                                $sql = "SELECT rbac_key FROM hosts WHERE host='$host'";
                                $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
                                while($row = fetch_array($result)) {
                                    $hostkey = $row[0];
                                }
                                // echo "$host has key: $hostkey key\n<br>";
                                if($bit) {
                                    $key = pow(2, $bit);
                                    $newkey = $hostkey - $key;
                                    if ($newkey > 0) {
                                        $sql = "UPDATE hosts set rbac_key=$newkey WHERE host='$host'";
                                        $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
                                    } elseif ($newkey == 0) {
                                      $sql = "UPDATE hosts set rbac_key=1 WHERE host='$host'";
                                      $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
                                    } else {
                                        $str .= "$host doesn't have access to the $group group,";
                                        break;
                                    }
                                    if ($result) {
                                        $str .= "Removed $host from $group,";
                                    }
                                }
                            }
                        } else {
                            echo "Did you forget to select a host?<br />";
                        }
                    }
                    rtrim(',', $str);
                    echo "$str<br>";
                }
                break;
        }
        break;
    case "assign":
        switch ($component) {
            case "users":
                foreach ($users as $user) {
                    if($user) {
                        foreach ($group_bits as $bit) {
                            $keys[] = pow(2, $bit);
                        }
                        $keysum = array_sum($keys);
                        $sql = "UPDATE users set rbac_key='$keysum' WHERE username='$user'";
                        $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
                        if ($result) {
                            if ($groupcount < 16) {
                                foreach ($groups as $group) {
                                    echo "Added $user to $group<br>";
                                }
                            } else {
                                echo "Added $user to multiple groups<br>";
                            }
                        } else {
                            echo "Error" . mysql_error() . "<br />";
                        }
                    } else {
                        echo "Did you forget to select a user?<br />";
                    }
                } 
                break;
                //                   case "groups":
                //                   foreach ($groups2 as $group2) {
                //                   if($group2) {
                //                   foreach ($group_bits as $bit) {
                //                   $keys[] = pow(2, $bit);
                //                   }
                //                   $keysum = array_sum($keys);
                //                   $sql = "REPLACE INTO rbac (rbac_bit,rbac_text) VALUES ($keysum, '$group2')";
                //                   $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
                //                   if ($result) {
                //                   foreach ($groups as $group) {
                //                   echo "Added $group2 to $group<br>";
                //                   }
                //                   } else {
                //                   echo "Error" . mysql_error();
                //                   }
                //                   } else {
                //                   echo "Did you forget to select a group?";
                //                   }
                //                   } 
                //                   break;
            case "hosts":
                if (!empty($groups)) {
                    foreach ($hosts as $host) {
                        if($host) {
                            foreach ($group_bits as $bit) {
                                $keys[] = pow(2, $bit);
                            }
                            $keysum = array_sum($keys);
                            $sql = "UPDATE hosts set rbac_key='$keysum' WHERE host='$host'";
                            $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
                            if ($result) {
                                if ($hostcount < 16) {
                                    foreach ($groups as $group) {
                                        echo "Added $host to $group<br>";
                                    }
                                }
                            } else {
                                echo "Error" . mysql_error() . "<br />";
                            }
              $keys = null;
                        } else {
                            echo "Did you forget to select a host?<br />";
                        }
                    } 
                    if ($hostcount > 15) {
                        echo "Assigned multiple hosts<br>";
                    }
                } else {
                    echo "Did you forget to select a group?<br />";
                }
                break;
        }
        exit;
        /*
           case "create":
           foreach ($groups as $group) {
           if($group) {
           $group = mysql_real_escape_string($group);
        // Get the next usable rbac_bit in the table
        $sql = "SELECT rbac_bit + 1 FROM rbac mo WHERE NOT EXISTS (SELECT NULL FROM rbac mi WHERE mi.rbac_bit = mo.rbac_bit + 1 ) ORDER BY rbac_bit LIMIT 1;";
        $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
        if($result) {
        while($row = fetch_array($result)) {
        $bit = $row[0];
        }
        $sql = "REPLACE INTO rbac (rbac_bit,rbac_text) VALUES ($bit, '$group')";
        $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
        // add all groups to session
        if ($result) {
        rbac2session();
        // print_r($_SESSION['rbac_groups']);
        // return csv value below so that we can append to select box using split
        echo "$group,$bit";
        } else {
        echo "Error" . mysql_error() . "<br />";
        }
        } else {
        echo "Error" . mysql_error() . "<br />";
        }
        } else {
        echo "Did you forget to enter a group name?<br />";
        }
        }
        break;
         */
        /*
           case "delgrp":
           $delgrp = get_input('delgrp');
           if(!$delgrp) {
           echo "Missing group name<br>";
           exit;
           }
           $delgrp = mysql_real_escape_string($delgrp);
           $groups = explode(",", $delgrp); 
           foreach ($groups as $group) {
           if($group) {
           $sql = "DELETE FROM rbac WHERE rbac_text='$group'";
           $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
           if($result) {
           echo "Removed $group<br>";
           } else {
           echo "Error" . mysql_error() . "<br />";
           }
           } else {
           echo "Did you forget to select a group?<br />";
           }
           }
           break;
         */
    case "rename":
        if($group) {
            if ($togrp) {
                $sql = "UPDATE rbac SET rbac_text='$togrp' WHERE rbac_text='$group'";
                $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
                if ($result) {
                    $sql = "SELECT rbac_bit FROM rbac WHERE rbac_text='$togrp'";
                    $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
                    if ($result) {
                        $row = fetch_array($result);
                        $newkey = $row[0];
                        echo "$group renamed to $togrp,$newkey";
                    } else {
                        echo "Error" . mysql_error() . "<br />";
                    }
                } else {
                    echo "Error" . mysql_error() . "<br />";
                }
            } else {
                echo "Did you forget to enter a new group name?<br />";
            }
        } else {
            echo "Did you forget to select a group?<br />";
        }
        break;
}
?>
