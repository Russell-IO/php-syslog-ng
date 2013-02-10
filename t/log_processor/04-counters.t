#!/usr/bin/perl
use strict;
use warnings;

use Find::Lib qw(../../lib);

use Test::More;
use Test::Deep;
use Test::MockTime qw(:all);
use LogZilla::Test::LogProcessor;
use LogZilla::Util::Snare qw(:all);

# Really basic tests - just put few logs lines and check if database has proper data
  
plan tests => 22;

my $tester = LogZilla::Test::LogProcessor->new(
    settings => { SNARE => 1 }, # Maybe it's not needed at all
);

# Load first pack of data
my %ts = ( ts => '2012-01-01 01:00:00' );
$tester->process_data( data => [
        { %ts, host => 'host1', msg => build_snare_msg({eid=>123, source=>'Security'}) },
        { %ts, host => 'host1', msg => build_snare_msg({eid=>124, source=>'System'}) },
        { %ts, host => 'host2', msg => build_snare_msg({eid=>125, source=>'System'}) },
        { %ts, host => 'host2', msg => build_snare_msg({eid=>126, source=>'Application'}) },
    ] );

# Add some data with different time
%ts = ( ts => '2012-01-01 02:00:00' );
$tester->process_data( data => [
        { %ts, host => 'host1', program => 'prog1', msg => 'Normal message nr 1' },
        { %ts, host => 'host5', program => 'prog1', msg => 'Normal message nr 2' },
    ] );

# Check number of records in tables 
$tester->check_table_count( 'logs', 6 );
$tester->check_table_count( 'hosts', 3 );
$tester->check_table_count( 'programs', 4 );  # for snare program is copied from "source"
$tester->check_table_count( 'snare_eid', 5 ); # including 0 for non-snare messages

# Check all counter tables (ignore order) for lastseen and seen count
$tester->check_last_seen( 'hosts', 3, bag(
        superhashof({ lastseen => '2012-01-01 02:00:00', seen => 3, host => 'host1' }),
        superhashof({ lastseen => '2012-01-01 01:00:00', seen => 2, host => 'host2' }),
        superhashof({ lastseen => '2012-01-01 02:00:00', seen => 1, host => 'host5' }),
    ) );

$tester->check_last_seen( 'programs', 4, bag(
        superhashof({ lastseen => '2012-01-01 01:00:00', seen => 1, name => 'Security' }),
        superhashof({ lastseen => '2012-01-01 01:00:00', seen => 2, name => 'System' }),
        superhashof({ lastseen => '2012-01-01 01:00:00', seen => 1, name => 'Application' }),
        superhashof({ lastseen => '2012-01-01 02:00:00', seen => 2, name => 'prog1' }),
    ) );

$tester->check_last_seen( 'snare_eid', 5, bag(
        superhashof({ lastseen => '2012-01-01 01:00:00', seen => 1, eid => 123 }),
        superhashof({ lastseen => '2012-01-01 01:00:00', seen => 1, eid => 124 }),
        superhashof({ lastseen => '2012-01-01 01:00:00', seen => 1, eid => 125 }),
        superhashof({ lastseen => '2012-01-01 01:00:00', seen => 1, eid => 126 }),
        superhashof({ lastseen => '2012-01-01 02:00:00', seen => 2, eid => 0 }),
    ) );

$tester->check_last_seen( 'mne', 5, bag(
        superhashof({ lastseen => '2012-01-01 02:00:00', seen => 6, name => 'None' }),
    ) );

# Add some more, also add some mnemonics
%ts = ( ts => '2012-01-01 03:00:00' );
$tester->process_data( data => [
        { %ts, host => 'host3', program => 'prog3', msg => 'Message with mne %ABC-1-XYZ:' },
        { %ts, host => 'host1', program => 'prog2', msg => 'Message with mne %ABC-2-XYZ:' },
        { %ts, host => 'host3', program => 'prog2', msg => 'Message with mne %ABC-2-XYZ:' },
        { %ts, host => 'host1', program => 'prog2', msg => 'Another normal message' },
        { %ts, host => 'host4', msg => build_snare_msg({eid=>123, source=>'Security'}) },
    ] );

# Check number of records in tables 
$tester->check_table_count( 'logs', 11 );
$tester->check_table_count( 'hosts', 5 );
$tester->check_table_count( 'programs', 6 );
$tester->check_table_count( 'snare_eid', 5 );

# Check all counter tables (ignore order) for lastseen and seen count
$tester->check_last_seen( 'hosts', 3, bag(
        superhashof({ lastseen => '2012-01-01 03:00:00', seen => 5, host => 'host1' }),
        superhashof({ lastseen => '2012-01-01 03:00:00', seen => 2, host => 'host3' }),
        superhashof({ lastseen => '2012-01-01 03:00:00', seen => 1, host => 'host4' }),
    ) );

$tester->check_last_seen( 'programs', 3, bag(
        superhashof({ lastseen => '2012-01-01 03:00:00', seen => 1, name => 'prog2' }),
        superhashof({ lastseen => '2012-01-01 03:00:00', seen => 3, name => 'Cisco Syslog' }),
        superhashof({ lastseen => '2012-01-01 03:00:00', seen => 2, name => 'Security' }),
    ) );

$tester->check_last_seen( 'snare_eid', 1, bag(
        superhashof({ lastseen => '2012-01-01 03:00:00', seen => 2, eid => 123 }),
    ) );

$tester->check_last_seen( 'mne', 5, bag(
        superhashof({ lastseen => '2012-01-01 03:00:00', seen => 1, name => 'ABC-1-XYZ' }),
        superhashof({ lastseen => '2012-01-01 03:00:00', seen => 2, name => 'ABC-2-XYZ' }),
        superhashof({ lastseen => '2012-01-01 03:00:00', seen => 8, name => 'None' }),
    ) );

# Final, evil test - add some record from the past - that is, with timestamp lower than value
# already set as lastseen in table for this value. It should change counter ('seen' field),
# but shouldn't change 'lastseen' - as it would decrease.

%ts = ( ts => '2012-01-01 00:00:00' );
$tester->process_data( data => [
        { %ts, host => 'host1', program => 'prog2', msg => 'Another normal message (from past)' },
    ] );

# It should stay last before this command, only counter for prog2 should change
$tester->check_last_seen( 'programs', 3, bag(
        superhashof({ lastseen => '2012-01-01 03:00:00', seen => 2, name => 'prog2' }),
        superhashof({ lastseen => '2012-01-01 03:00:00', seen => 3, name => 'Cisco Syslog' }),
        superhashof({ lastseen => '2012-01-01 03:00:00', seen => 2, name => 'Security' }),
    ) );

# Same for the hosts
$tester->check_last_seen( 'hosts', 3, bag(
        superhashof({ lastseen => '2012-01-01 03:00:00', seen => 6, host => 'host1' }),
        superhashof({ lastseen => '2012-01-01 03:00:00', seen => 2, host => 'host3' }),
        superhashof({ lastseen => '2012-01-01 03:00:00', seen => 1, host => 'host4' }),
    ) );
