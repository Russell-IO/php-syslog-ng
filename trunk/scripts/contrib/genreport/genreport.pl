#!/usr/bin/perl

#
# LogZilla Excel Export
#
# Developed by Clayton Dukes <cdukes@logzilla.pro>
# Copyright (c) 2010 LogZilla, LLC
# All rights reserved.
#
# Changelog:
# 2010-04-30 - created
#
# Note that this requires the MIME::Lite package which can easily be installed by typing:
# cpan MIME::Lite from the linux command line.

use strict;
use warnings;
use POSIX qw/strftime/;
use DBI;
use MIME::Lite;
# NOTE: this requires curl from the command line.
# There's a way to do it with perl LWP, but I don't know how.


my $now = strftime("%Y-%m-%d %H:%M:%S", localtime);
my $today = strftime("%Y-%m-%d", localtime);

####### MODIFY below to suit your needs ##############
my $limit = 100;
my $from = 'root@localhost.com';
my $to = 'REPLACEME@###.com';
my $subject = 'LogZilla Excel Report';
my $body = "Report generated on $now";
my $basepath = "/path_to_logzilla";
my $baseurl = "http://localhost";
my $smtphost = "localhost";
####### MODIFY above to suit your needs ##############

my ($db, $dbhost, $dbport, $dbuser, $dbpass, $dbtable, @ids, $dbids);
my $rpt_type = "excel"; # valid types are "pdf", "excel", "xml", and "csv" (xml is Excel 2007 format)
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

####### MODIFY below to suit your needs ##############
# You can Modify the query below to whatever you want.
# Other examples would be (to get all message for today limited to $limit specified above:
# my $query = $dbh->prepare("SELECT id FROM $dbtable WHERE lo BETWEEN '$today 00:00:00' and '$today 23:59:59' ORDER BY id LIMIT ?");
my $query = $dbh->prepare("SELECT id FROM $dbtable ORDER BY id LIMIT ?");
####### MODIFY above to suit your needs ##############

$query->execute("$limit");
while (my $ref = $query->fetchrow_hashref()) {
    $dbids = $ref->{'id'};
    push (@ids, $dbids);
}
$dbids = "dbid[]=";
for (my $i=0; $i <= $#ids; $i++) {
    $dbids .= $ids[$i]."&dbid[]=";
}
# rtrim last dbid string
$dbids =~ s/&dbid\[\]=$//g;
#print "$dbids\n";

my $url = $baseurl . "/includes/excel.php";
my $res = `curl -s -d "$dbids&rpt_type=$rpt_type" $url`;

my $filename = "file.xls";
open(XLS,">/tmp/$filename");
if (! -f "/tmp/$filename") {
    print STDOUT "Unable to open file \"$filename\" for writing...$!\n";
    exit;
}
print XLS $res;
close (XLS);

### Start with a simple text message:
my $msg = MIME::Lite->new(
    From    =>"$from",
    To      =>"$to",
    Subject =>"$subject",
    Type    =>'TEXT',
    Data    =>"$body"
);

$msg->attach(
    Type     => 'Application/vnd.ms-excel', # change this to the proper mime type if you change to pdf, xml, etc.
    Path => "/tmp/$filename",
);

#$msg->send('smtp','localhost', Debug=>1 );
$msg->send('smtp',"$smtphost");
print "Mail Sent\n";

# Delete the temp file
unlink ("/tmp/$filename");





