#!/usr/bin/perl -w

#
# logreplay.pl
#
# Developed by Clayton Dukes <cdukes@cdukes.com>
# Copyright (c) 2010 LogZilla, LLC
# All rights reserved.
#
# Changelog:
# 2009-06-26 - created
#

use strict;

$| = 1;

use POSIX qw/strftime/;
use vars qw/ %opt /;
use Log::Syslog::Fast ':all';
use Getopt::Std;
#
# Declare variables to use
#
my ($infile, $host, $msg, $randhost, $sleep_end, $sleep, $dest, $hostlimit, $inet, $port, $logger); 
use vars qw/ %opt /;
#
# Command line options processing
#
sub init()
{
    use Getopt::Std;
    my $opt_string = 'hrvt:f:e:d:l:i:p:';
    getopts( "$opt_string", \%opt ) or usage();
    usage() if $opt{h};
    $inet  = defined( $opt{'i'} ) ? $opt{'i'} : 'UDP';
    $port  = defined( $opt{'p'} ) ? $opt{'p'} : '514';
    $dest  = defined( $opt{'d'} ) ? $opt{'d'} : '127.0.0.1';
    $infile = $opt{'f'} or usage();
    $randhost = $opt{'r'};
    $hostlimit = defined($opt{'l'}) ? $opt{'l'} : '50';
    $sleep = defined($opt{'t'}) ? $opt{'t'} : '1';
    $sleep_end = $opt{'e'};
}
#
# Help message
#
sub usage()
{
    print STDERR << "EOF";
This program is used to replay a standard *Cisco* syslog dumpfile into the local syslog receiver (syslog-ng)
    usage: $0 [-hvfs] 
    -h        : this (help) message
    -t        : Sleep seconds between messages (default: 1)
    -e        : End sleep seconds (optional, will randomize between start (-t) and end (-e) seconds. 
    -d        : Destination host to send udp messages to (default: 127.0.0.1)
    -v        : verbose output
    -f        : Filename to replay (required)
    -i        : INET Protocol (TCP/UDP) default: UDP
    -p        : INET Port default: 514
    -r        : Generate random IP's based on incoming hosts (last octect will be randomized)
    example: $0 -f ./syslog.sample
EOF
    exit;
}
init();

my @hosts;
sub array_unique
{
    my @list = @_;
    my %finalList;
    foreach(@list)
    {
        $finalList{$_} = 1; # delete double values
    }
    return (keys(%finalList));
}
# My syslog looks like this, you may need to change the regex below to match yours
#Jun 19 05:10:58 netcontrol_3750.some.domain 117475: Jun 19 05:10:57: %DUAL-5-NBRCHANGE: IP-EIGRP(0) 1024: Neighbor 10.15.213.61 (Vlan40) is down: Interface Goodbye received
my $regex = qr/[A-Z][a-z][a-z]\s+\d+\s+\d\d:\d\d:\d\d\s+([^\s]+)\s+(.*)/;
open(FILE, $infile) || die("Can't open $infile : $!\nTry $0 -h\n");
my $count = 0;
print "Running...\n" if not $opt{v};
while(<FILE>) {
    if (&array_unique(@hosts) < $hostlimit) {
        if ($_ =~ m/$regex/) {
            $host = $1; 
            $msg = $2;
            print STDOUT "HOST: $host\n" if $opt{v};
            print STDOUT "MSG: $msg\n" if $opt{v};
            if ($host !~ /^([\d]+)\.([\d]+)\.([\d]+)\.([\d]+)$/) {
                print "$host not an IP address\n";
                next;
            }
            my $notIP = 0;
            foreach my $s (($1, $2, $3, $4)) {
                #print "s=$s;";
                if (0 > $s || $s > 255) {
                    $notIP = 1;
                    last;
                }
            }
            if ($notIP) {
                print "\n$host is not a valid IP address\n";
            } else {
                #print "\n$host is an IP address\n";
                if ($randhost) {
                    $host = "$1.$2.$3." . int(rand(254));
                    #print "\nNewIp = $host\n";
                }
            }
        if ( $inet =~ /UDP/ ) {
            $logger = Log::Syslog::Fast->new( LOG_UDP, "$dest", $port, LOG_LOCAL0, LOG_INFO, "$host", "Replay" );
        } else {
            $logger = Log::Syslog::Fast->new( LOG_TCP, "$dest", $port, LOG_LOCAL0, LOG_INFO, "$host", "Replay" );
        }
        $logger->send( "$msg", time );
            my $sleeptime;
            if ($sleep_end) {
                $sleeptime = ($sleep + rand($sleep_end));
            } else {
                $sleeptime = $sleep;
            }
            print "Sleeping for $sleeptime\n" if ($opt{v});
            select( undef, undef, undef, $sleeptime ); 
            push (@hosts, $host);
        } else {
            # If something goes wrong
            print "INVALID MESSAGE FORMAT:\n$_\n" if $opt{v};
        }
        $count++;
    } else {
        print "\n\nHost limit of $hostlimit reached, use $0 -l to set a higher limit\n"; 
        print "Sent $count messages out\n";
        exit;
    }
}
print "Sent $count messages out\n";
close (FILE);
