#!/usr/bin/perl

#
# hup.pl
#
# Developed by Clayton Dukes <cdukes@logzilla.pro>
# Copyright (c) 2011 logzilla.pro
# All rights reserved.
#
# Changelog:
# 2011-02-18 - created
#
# Used by the Email Alerts interface to send a HUP to the syslog-ng process.
# This allows you to enter new patterns in the web interface and make them active.
# It is necessary to HUP the process because db_insert is only loaded when syslog-ng starts up.

# Code from http://www.perlmonks.org/?abspart=1;displaytype=displaycode;node_id=255576;part=1
# this script needs to be run as root, to do this we add an entry to 
# /etc/sudoers so that just apache can run it suid root
# NB: you must edit this file using visudo, ie
# visudo -f /etc/sudoers
# add these lines:
# # Allows Apache user to HUP the syslog-ng process
# www-data ALL=NOPASSWD:/path_to_logzilla/scripts/hup.pl

use strict;
$| = 1;


my $PROGRAM = 'syslog-ng';
my @ps = `ps ax`;
@ps = map { m/(\d+)/; $1 } grep { /\Q$PROGRAM\E/ } @ps;

# for debugging lets see who we think we are....
# printf("uid=%d euid=%d<br>\n", $<, $>);

for ( @ps ) {
    (kill 1, $_) or die("Restart failed");
}
my $time = gmtime();
print "Sent SIGHUP to $PROGRAM @ps\n";
