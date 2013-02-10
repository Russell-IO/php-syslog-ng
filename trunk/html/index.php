<?php
// Copyright (C) 2005 Clayton Dukes, cdukes@logzilla.pro

// Check to see if config.php is set, if not we need to run the installer.
$chk_config = @file_get_contents("config/config.php");
if (strlen($chk_config) < 300) {
	echo "<center><h2>\n";
	echo "Unable to get DB config - have you run scripts/install.pl yet?<br>\n";
	echo "</center></h2>\n";
	exit;
} else {
   	require_once ("config/config.php");
   	require_once 'includes/common_funcs.php';
	include_once ("includes/modules/functions.security.php");
}
// Check to see if  a license exists.
$chk_lic = @file_get_contents("license.txt");
if (!preg_match ('/^Registered/', $chk_lic)) {
    $chk_lic = @file_get_contents("license.txt");
    if (!preg_match ('/^Registered/', $chk_lic)) {
        $addTitle = "License";
        require 'includes/license.php';
        exit;
    }
}

session_start();
$_SERVER = cleanArray($_SERVER);
$_POST = cleanArray($_POST);
$_GET = cleanArray($_GET);
$_COOKIE = cleanArray($_COOKIE);

secure();

$time_start = get_microtime();

//------------------------------------------------------------------------
// Determine what page is being requested
//------------------------------------------------------------------------
$pageId = get_input('pageId');
if (!$pageId) { $pageId = "login"; }
if(!validate_input($pageId, 'pageId')) {
	echo "Error on pageId validation! <br>Check your regExpArray in config.php!\n";
   	$pageId = "login";
}

//------------------------------------------------------------------------
// Connect to database. If connection fails then set the pageId for the
// help page.
//------------------------------------------------------------------------
$dbProblem = FALSE;
if(!$dbLink = db_connect_syslog(DBADMIN, DBADMINPW)) {
   	$pageId = "help";
   	$dbProblem = TRUE;
}


//------------------------------------------------------------------------
// Load page
//------------------------------------------------------------------------
if(strcasecmp($pageId, "Main") == 0) {
	$addTitle = "Welcome to ".$_SESSION['PROGNAME'];
	require 'includes/index.php';
}
elseif(strcasecmp($pageId, "login") == 0) {
	$addTitle = "Login";
	require 'login.php';
}
elseif(strcasecmp($pageId, "logout") == 0) {
	$addTitle = "Logout";
	require 'logout.php';
}
elseif(strcasecmp($pageId, "License") == 0) {
	$addTitle = "License";
	require 'includes/license.php';
}
else {
	$addTitle = "Welcome to ".$_SESSION['PROGNAME'];
	require 'includes/index.php';
}
require_once 'includes/html_footer.php';
?>
