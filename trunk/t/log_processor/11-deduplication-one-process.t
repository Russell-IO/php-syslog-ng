#!/usr/bin/perl
use strict;
use warnings;

use Find::Lib qw(../../lib);

use Test::More;
use Test::Deep;
use LogZilla::Test::LogProcessor;

# Testing deduplication - with both disabled and enabled option

plan tests => 14;

my $tester = LogZilla::Test::LogProcessor->new(
    settings => { DEDUP => 0 },
);

my %common1 = ( host => 'host1', program => 'prog1' );
my %common2 = ( host => 'host2', program => 'prog2' );

# Load first pack of data with duplicates, but deduplication disabled
$tester->process_data( data => [
        { ts => '2011-05-01 10:00:00', %common1, msg => 'Message 1' },
        { ts => '2011-05-01 10:00:01', %common1, msg => 'Message 1' },
        { ts => '2011-05-01 10:00:02', %common1, msg => 'Message 2' },
        { ts => '2011-05-01 10:00:03', %common1, msg => 'Message 2' },
        { ts => '2011-05-01 10:00:04', %common1, msg => 'Message 1' },
    ] );

# Check number of records in tables 
$tester->check_table_count( 'logs', 5 );
$tester->check_table_count( 'hosts', 1 );
$tester->check_table_count( 'programs', 1 );

$tester->check_cache( 'msg_sum', superhashof({ value => 5 }) );

# And check last records in table logs
$tester->check_last_logs( 4, [
        superhashof({ %common1, msg => 'Message 1', counter => 1,
           fo => '2011-05-01 10:00:04', lo => '2011-05-01 10:00:04' }),
        superhashof({ %common1, msg => 'Message 2', counter => 1,
           fo => '2011-05-01 10:00:03', lo => '2011-05-01 10:00:03' }),
        superhashof({ %common1, msg => 'Message 2', counter => 1,
           fo => '2011-05-01 10:00:02', lo => '2011-05-01 10:00:02' }),
        superhashof({ %common1, msg => 'Message 1', counter => 1,
           fo => '2011-05-01 10:00:01', lo => '2011-05-01 10:00:01' }),
    ] );

$tester->update_settings( DEDUP => 1, DEDUP_WINDOW => 90 );
$tester->start_script( '--save-period' => 1 );

# Load another pack of data with duplication enabled now
$tester->add_data( data => [
        { ts => '2011-05-01 10:01:00', %common2, msg => 'Message 1' },
        { ts => '2011-05-01 10:01:01', %common2, msg => 'Message 1' },
        { ts => '2011-05-01 10:01:02', %common2, msg => 'Message 2' },
        { ts => '2011-05-01 10:01:03', %common2, msg => 'Message 2' },
        { ts => '2011-05-01 10:01:04', %common2, msg => 'Message 3' },
        { ts => '2011-05-01 10:01:05', %common2, msg => 'Message 1' },
    ] );

sleep(3);
$tester->flush_output();

$tester->check_cache( 'msg_sum', superhashof({ value => 11 }) );

# Check number of records in tables (including values from previous data processing)
$tester->check_table_count( 'logs', 5 + 3 );
$tester->check_table_count( 'hosts', 1 + 1 );
$tester->check_table_count( 'programs', 1 + 1 );

# And check last records in table logs
$tester->check_last_logs( 3, [
        superhashof({ %common2, msg => 'Message 3', counter => 1,
           fo => '2011-05-01 10:01:04', lo => '2011-05-01 10:01:04' }),
        superhashof({ %common2, msg => 'Message 2', counter => 2,
           fo => '2011-05-01 10:01:02', lo => '2011-05-01 10:01:03' }),
        superhashof({ %common2, msg => 'Message 1', counter => 3,
           fo => '2011-05-01 10:01:00', lo => '2011-05-01 10:01:05' }),
    ] );

# Add some more data, including duplicates
$tester->add_data( data => [
        { ts => '2011-05-01 10:02:00', %common2, msg => 'Message 2' },
        { ts => '2011-05-01 10:02:01', %common2, msg => 'Message 4' },
        { ts => '2011-05-01 10:02:02', %common2, msg => 'Message 2' },
    ] );
sleep(3);
$tester->flush_output();

$tester->check_last_logs( 4, [
        superhashof({ %common2, msg => 'Message 4', counter => 1,
           fo => '2011-05-01 10:02:01', lo => '2011-05-01 10:02:01' }),
        superhashof({ %common2, msg => 'Message 3', counter => 1,
           fo => '2011-05-01 10:01:04', lo => '2011-05-01 10:01:04' }),
        superhashof({ %common2, msg => 'Message 2', counter => 4,
           fo => '2011-05-01 10:01:02', lo => '2011-05-01 10:02:02' }),
        superhashof({ %common2, msg => 'Message 1', counter => 3,
           fo => '2011-05-01 10:01:00', lo => '2011-05-01 10:01:05' }),
    ] );

# Now add again some duplicates - but some after more than DEDUP_WINDOW seconds
# from first occurence
$tester->add_data( data => [
        { ts => '2011-05-01 10:03:00', %common2, msg => 'Message 2' },
        { ts => '2011-05-01 10:03:01', %common2, msg => 'Message 4' },
        { ts => '2011-05-01 10:03:02', %common2, msg => 'Message 2' },
    ] );
sleep(3);
$tester->flush_output();

$tester->check_cache( 'msg_sum', superhashof({ value => 17 }) );

# So, "Message 2" should be put as a new, as it's first occurence was more then DEDUP_WINDOW
# before current occurence. But "Message 4" should be treated as a duplicate, as it arrived
# first time only 60 seconds ago. "Message 3" stays as it was, untouched.
$tester->check_last_logs( 3, [
        superhashof({ %common2, msg => 'Message 2', counter => 2,
           fo => '2011-05-01 10:03:00', lo => '2011-05-01 10:03:02' }),
        superhashof({ %common2, msg => 'Message 4', counter => 2,
           fo => '2011-05-01 10:02:01', lo => '2011-05-01 10:03:01' }),
        superhashof({ %common2, msg => 'Message 3', counter => 1,
           fo => '2011-05-01 10:01:04', lo => '2011-05-01 10:01:04' }),
   ] );

