#!/usr/bin/perl

#
# LogZilla Daily Report
#
# Developed by Clayton Dukes <cdukes@logzilla.pro>
# Copyright (c) 2010 LogZilla, LLC
# All rights reserved.
#
# Changelog:
# 2010-05-24 - created
#
# Note that this requires the MIME::Lite and Text::Tabulate packages which can easily be installed by typing:
# cpan MIME::Lite Text::Tabulate
# from the linux command line.

use strict;
use warnings;
use POSIX qw/strftime/;
use DBI;
use MIME::Lite;
use Text::Tabulate;
my $tab = new Text::Tabulate();
$tab->configure(-tab => "\t", gutter => ' = ');


my $now = strftime("%Y-%m-%d %H:%M:%S", localtime);
my $today = strftime("%Y-%m-%d", localtime);

####### MODIFY below to suit your needs ##############
my $smtphost = "localhost";
my $from = 'root@localhost.com';
my $to = 'REPLACEME@###.com';
my $subject = "LogZilla Daily Report - $now";
my $body;
my $basepath = "/path_to_logzilla"; ## CHANGE THIS!
####### MODIFY above to suit your needs ##############

my ($db, $dbhost, $dbport, $dbuser, $dbpass, $dbtable, @ids, $dbids);
my $config = "$basepath/html/config/config.php";

open( CONFIG, $config );
my @config = <CONFIG>; 
close( CONFIG );

foreach my $var (@config) {
	next unless $var =~ /DEFINE/; # read only def's
	$dbuser = $1 if ($var =~ /'DBADMIN', '(\w+)'/);
	$dbpass = $1 if ($var =~ /'DBADMINPW', '(\w+)'/);
	$db = $1 if ($var =~ /'DBNAME', '(\w+)'/);
	$dbhost = $1 if ($var =~ /'DBHOST', '(\w+.*|\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})'/);
	$dbport = $1 if ($var =~ /'DBPORT', '(\w+)'/);
}
my $dbh = DBI->connect( "DBI:mysql:$db:$dbhost", $dbuser, $dbpass );
if (!$dbh) {
	print "Can't connect to $db database: ", $DBI::errstr, "\n";
	exit;
}
my $sth = $dbh->prepare("SELECT name,value FROM settings");
$sth->execute();
if ($sth->errstr()) {
	print "FATAL: Unable to execute SQL statement: ", $sth->errstr(), "\n";
	exit;
}
while (my @settings = $sth->fetchrow_array()) {
	$dbtable = $settings[1] if ($settings[0] =~ /^TBL_MAIN$/);
}

my $q_msgs_total= $dbh->prepare("SELECT value FROM cache WHERE name='msg_sum'");
my $q_msgs_last24= $dbh->prepare("SELECT SUM(value) AS last24 FROM cache WHERE name LIKE 'chart_mph_%' AND updatetime BETWEEN NOW() - INTERVAL 23 HOUR and NOW() - INTERVAL 0 HOUR");
my $q_msgs_today= $dbh->prepare("SELECT SUM(value) FROM cache WHERE name LIKE 'chart_mph_%' AND updatetime > CONCAT(CURDATE(), ' 00:00:00')");
my $q_msgs_lasthour= $dbh->prepare("SELECT SUM(counter) AS lasthour FROM $dbtable WHERE lo BETWEEN NOW() - INTERVAL 2 HOUR and NOW() - INTERVAL 1 HOUR");
my $q_msgs_thishour= $dbh->prepare("SELECT SUM(counter) AS thishour FROM $dbtable WHERE lo BETWEEN NOW() - INTERVAL 1 HOUR and NOW() - INTERVAL 0 HOUR");
my $q_msgs_avg_perhour= $dbh->prepare("SELECT ROUND(SUM(value)/24) AS avg_last24 FROM cache WHERE name LIKE 'chart_mph_%' AND updatetime BETWEEN NOW() - INTERVAL 23 HOUR and NOW() - INTERVAL 0 HOUR;");
my $q_top20_hosts_today = $dbh->prepare("SELECT host,SUM(counter) as count FROM $dbtable WHERE lo BETWEEN CONCAT(CURDATE(), ' 00:00:00') AND CONCAT(CURDATE(), ' 23:59:59') GROUP BY host ORDER BY count DESC LIMIT 20");

$q_msgs_total->execute();
$q_msgs_last24->execute();
$q_msgs_today->execute();
$q_msgs_lasthour->execute();
$q_msgs_thishour->execute();
$q_msgs_avg_perhour->execute();
$q_top20_hosts_today->execute();
my $total = $q_msgs_total->fetchrow_array(); 
my $total24 = $q_msgs_last24->fetchrow_array(); 
my $total_today = $q_msgs_today->fetchrow_array(); 
my $lasthour = $q_msgs_lasthour->fetchrow_array(); 
my $thishour = $q_msgs_thishour->fetchrow_array(); 
my $mph_avg = $q_msgs_avg_perhour->fetchrow_array(); 
my @top20;
while (my $ref = $q_top20_hosts_today->fetchrow_hashref()) {
	push(@top20, $ref->{'host'} ."\t". $ref->{'count'});
}
$total = "No Data" if (!$total); 
$total24 = "No Data" if (!$total24); 
$total_today = "No Data" if (!$total_today); 
$lasthour = "No Data" if (!$lasthour); 
$thishour = "No Data" if (!$thishour); 
$mph_avg = "No Data" if (!$mph_avg); 
$body .= "Messages received - Total: $total\n";
$body .= "Messages received - Last 24 hours: $total24\n";
$body .= "Messages received - Since Midnight: $total_today\n";
$body .= "Messages received - Last hour: $lasthour\n";
$body .= "Messages received - This hour: $thishour\n";
$body .= "Messages per hour - Average: $mph_avg\n";
$body .= "\n";
$body .= "Top 20 Hosts Today\n";
$body .= "-------------------------------------\n";
my @out = $tab->format (@top20);
foreach my $l (@out) {
	$body .= "$l\n";
}
$body .= "-------------------------------------\n";

### Start with a simple text message:
my $msg = MIME::Lite->new(
	From    =>"$from",
	To      =>"$to",
	Subject =>"$subject",
	Type    =>'TEXT',
	Data    =>"$body"
);

#$msg->send('smtp','localhost', Debug=>1 );
$msg->send('smtp',"$smtphost");
print "Mail Sent\n";

# END
