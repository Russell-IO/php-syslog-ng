<?php
include ("../common_funcs.php");
$id = $_POST['id'];
$host = $_POST['host'];
$facility = $_POST['facility'];
$priority = $_POST['priority'];
$program = $_POST['program'];
$msg = $_POST['msg'];
$counter = $_POST['counter'];
$fo = $_POST['fo'];
$lo = $_POST['lo'];
$notes = $_POST['notes'];
$operation = $_POST['oper'];

$dbLink = db_connect_syslog(DBADMIN, DBADMINPW);

if ($operation == "edit") {
  $sql = "UPDATE ".$_SESSION['TBL_MAIN']." SET notes = '$notes' WHERE id = '$id'";
  $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']);
} 
if ($operation == "del") {
  $sql = "DELETE from ".$_SESSION['TBL_MAIN']." where id='$id'";
  $result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']); 
}
/*if ($operation == "add") 
{
$sql = "INSERT INTO ".$_SESSION['TBL_MAIN']." (id, program, msg, counter, fo, lo, priority, host, facility, notes) VALUES ('$newid', '$program', '$msg', '$counter', '$fo', '$lo', '$priority', '$host', '$facility', '$notes')";
$result = perform_query($sql, $dbLink, $_SERVER['PHP_SELF']); 
}
*/
?>
