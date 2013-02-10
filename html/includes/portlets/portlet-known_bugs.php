<?php
$basePath = dirname( __FILE__ );
?>
<table class="header">
<tr><td>
	<h2 class="logo"><?php echo $_SESSION['PROGNAME'] ." v".$_SESSION['VERSION']." ".$_SESSION['VERSION_SUB']." by Clayton Dukes (cdukes@logzilla.pro)";?></h2>
</td><td class="headerright">
</td></tr></table>
<table class="headerbottom"><tr><td>
</table>
<table class="pagecontent">
<tr><td><span class="longtext">
<h3 class="title">History</h3>
<?php
// Get a file into an array.  In this example we'll go through HTTP to get
// the HTML source of a URL.
$lines = file($basePath . "/../../CHANGELOG");
echo "<pre>";
foreach ($lines as $line) {
	    echo htmlspecialchars($line);
}
echo "</pre>";

?>
<hr>
<b>Ancient History<br></b></b>
<hr>
2002-07-31 - Michael Earls releases version 0.1 of Php-Syslog-ng. 
More information can be found at http://www.vermeer.org/projects/Php-Syslog-ng and http://sourceforge.net/projects/Php-Syslog-ng/ <i>But please be aware that these versions are old and no longer maintained.</i>

Michael Earls steadily improved on Php-Syslog-ng until his last release (2.5.1) in the summer of 2004.

<br />
<br />
After that, Jason Taylor made some contributions (<a href="http://deathstar.com/PhpSyslogNG">http://deathstar.com/PhpSyslogNG</a>). The development since Michael Earls' last release can be followed on the mailing list on sourceforge (<a href="http://sourceforge.net/mailarchive/forum.php?forum=Php-Syslog-ng-support">http://sourceforge.net/mailarchive/forum.php?forum=Php-Syslog-ng-support</a>). (Note: The Sourceforge list is deprecated in favor of <a href="http://code.google.com/p/php-syslog-ng">Google's Code Repository</a>

<br />
<br />
Php-Syslog-ng April 2005 (2.5.3)<br />
I had been looking at this project and others like it for a while and I needed to implement something like this at work. That gave me the excuse to finally take a good look at the code for Php-Syslog-ng to see if it matched my requirements. I downloaded Jason Taylor's 20050422 release and started to dig in. It quickly became apparent that the code could use some clean-up work before I would start to add any new features. The clean-up was a little more work than I had first hoped but after a couple of days I had some code that I thought was workable. I then started adding/fixing some of the things I immediately wanted done. The most significant change is that MySQL 4.0 is now required if you want full functionality (earlier versions are still supported with limited functionality). I don't know how well my approach to calculating the total number of results works in a very busy environment but I believe that it will be OK. This is something I will keep an eye on as I implement this system where I work.<br>

<br />
<br />
Php-Syslog-ng May 2005 (2.5.4)<br />
User authentication, support for multiple tables with log data and improved log rotation capabilities ... those are the main changes in this version. I also fixed a couple of bugs, added input validation for must user supplied values and further cleaned up and commented the code.<br />
Enjoy!

<br />
<br />
Php-Syslog-ng June 2005 (2.6)<br />
New features/changes:<br />
1) A cache function has been implemented to remove/minimize the delay when loading the search page.<br />
2) Basic access controls have been implemented. They currently only affect the configure page but I plan on expanding it to also allow for restrictions on what searches a user can make (what hosts, timeframe etc).<br />
In addition to the new stuff I also fixed a few minor bugs.

<br />
<br />
Php-Syslog-ng June 2005 (2.7)<br />
This release only has bug fixes. See the changelog for the list of changes.<br />
If you are using the merge table then you need to use MySQL 4.0.18 or later.

<br />
<br />
Php-Syslog-ng July 2005 (2.8)<br />
A quick bug fix to remedy a problem in logrotate.php.

<br />
<br />
This latest version of Php-Syslog-ng is available at <a href="http://code.google.com/p/php-syslog-ng">http://code.google.com/p/php-syslog-ng</a>.
</span></td></tr></table>

</td></tr></table>
