<?php
// Copyright (C) 2011 LogZilla, LLC - Clayton Dukes, cdukes@logzilla.pro

$basePath = dirname( __FILE__ );
require_once ($basePath . "/common_funcs.php");

// Load DB settings into SESSION variables
getsettings();

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
<meta http-equiv="X-UA-Compatible" content="chrome=1">
<link rel="shortcut icon" type="image/x-icon" href="<?php echo $_SESSION['SITE_URL']?>favicon.ico" />
<?php echo "<title>".$addTitle.": ".$_SESSION['PROGNAME']." ".$_SESSION['VERSION']."</title>\n"; ?>
<meta name="Description" "LogZilla (http://www.logzilla.pro)">
<meta name="Keywords" 'LogZilla', 'Syslog', 'Syslog Tool', 'Syslog Analysis', 'Syslog Analyzer', 'Syslog Management'>
<meta name="Copyright" 'LogZilla, LLC'>
<meta name="Author" 'Clayton Dukes - cdukes@logzilla.pro'>
<meta http-equiv="Content-Language" content="EN">
<meta http-equiv="content-type" content="application/xhtml+xml; charset=UTF-8" />
<meta http-equiv="cache-control" content="no-cache,no-store,must-revalidate">
<meta http-equiv="pragma" content="no-cache">
<meta http-equiv="expires" content="0">

<!-- BEGIN Import CSS -->
<?php include ("css.php");?>
<!-- END Import CSS -->

<!-- BEGIN Import JS Head -->
<?php include ("js_header.php");?>
<!-- END JS Head -->
</head>

<!-- BEGIN Body 
Note: Closing body tag is located in the html_footer.php file
-->
<body class="body gradient">
