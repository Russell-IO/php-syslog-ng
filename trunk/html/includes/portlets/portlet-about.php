<?php
$basePath = dirname( __FILE__ );
require_once ($basePath . "/../common_funcs.php");
?>
<img style='float: left; border: 0 none; padding-right: 1.5em; padding-left: 1.5em; width: 200px;' src='images/lztri.png' alt='Go Go LogZilla!'/>
<table class="header">
<tr><td>
	<h2 class="logo"><?php echo $_SESSION['PROGNAME'] ." v".$_SESSION['VERSION']."".$_SESSION['VERSION_SUB']." by Clayton Dukes (cdukes@logzilla.pro)";?></h2>
</td><td class="headerright">
</td></tr></table>
<table class="headerbottom"><tr><td>
</table>
<table class="pagecontent">
<tr><td><span class="longtext">
<h3 class="title">Overview</h3>
LogZilla specializes in providing a proactive approach to network management by monitoring network status in real time.  Our network management software is the choice of thousands of IT professionals interested in improving network uptime, increasing network efficiency, reducing internal costs and eliminating unwanted network traffic

<h3 class="title">Local License Information</h3>
<ul>
<li>The license for this copy of LogZilla will expire on <?php echo $_SESSION['LZ_LIC_EXPIRES']?></li>
<li>Maximum number of messages per day: <?php echo commify($_SESSION['LZ_LIC_MSGLIMIT'])?></li>
<li>Maximum number of hosts: <?php echo commify($_SESSION['LZ_LIC_HOSTS'])?></li>
<li>Authentication modules: <?php echo $_SESSION['LZ_LIC_AUTH']?></li>
<li>Adhoc Charts: <?php echo $_SESSION['LZ_LIC_ADHOC']?></li>
<li>Email Alerts: <?php echo $_SESSION['LZ_LIC_TRIGGERS']?></li>
<li>RBAC Security Controls: <?php echo $_SESSION['LZ_LIC_RBAC']?></li>
</ul>
</table>
