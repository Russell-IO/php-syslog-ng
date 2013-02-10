#!/usr/bin/perl

#
# licadd.pl
#
# Developed by Clayton Dukes <cdukes@logzilla.pro>
# Copyright (c) 2011 logzilla.pro
# All rights reserved.
#
# Changelog:
# 2011-02-18 - created
#
# this script needs to be run as root, to do this from the web interface we add an entry to 
# /etc/sudoers so that just apache can run it suid root
# NB: you must edit this file using visudo, ie
# visudo -f /etc/sudoers
# add these lines:
# # Allows Apache user to HUP the syslog-ng process
# www-data ALL=NOPASSWD:/path_to_logzilla/scripts/licadd.pl

use strict;
$| = 1;

my $licfile="/path_to_logzilla/license.txt";
print "Paste your license, be sure to include the <licdata> tags (or type END on a blank line to end):\n";
my $answer;
while (<STDIN>) {
    if (/<licdata>|BEGIN|Registered/../<\/licdata>|END/) {
        next if /^<licdata>|BEGIN/;
        $_ =~ s/[\r\n]/\n/g;
        $_ = decode($_);
        $_ =~ s/PLUS/+/g;
        $answer .= $_;
        last if /^<\/licdata>|END/;
    }
    open FILE, ">$licfile" or die "Unable to open $licfile: $!";
    print FILE $answer;
    close FILE;
}
sub decode {
    my $str = shift;
    $str =~ s/%([A-Fa-f0-9]{2})/pack('C', hex($1))/seg;
    return $str;
}
