#!/usr/bin/perl
use strict;
use warnings;

use Find::Lib qw(../../lib);

use Test::More;
use Test::Deep;
use LogZilla::Test::LogProcessor;

# This is very similar to the 02-long-run-flushing-by-time.t, but here
# we force flushing data by setting maximum number of messages in queue.

plan tests => 7;

my $tester = LogZilla::Test::LogProcessor->new();

$tester->start_script( '--save-period' => 9999, '--save-msg-limit' => 3 ); 
# save after 10 messages collected, at least once per second

# Load first pack of data
$tester->add_data( data => [
        { host => 'host1', program => 'prog1', msg => 'Message no 1' },
        { host => 'host1', program => 'prog2', msg => 'Message no 2' },
        { host => 'host2', program => 'prog1', msg => 'Message no 3' },
        { host => 'host2', program => 'prog2', msg => 'Message no 4' },
    ] );

# Wait till data is processed - 1 second should be enough, but leave some margin
sleep(3);
$tester->flush_output(); # print debug info if available

# Check number of records in tables 
$tester->check_table_count( 'logs', 3 );
$tester->check_table_count( 'hosts', 2 );
$tester->check_table_count( 'programs', 2 );

# Add 2 more
$tester->add_data( data => [
        { host => 'host1', program => 'prog1', msg => 'Message no 5' },
        { host => 'host1', program => 'prog2', msg => 'Message no 6' },
    ] );

sleep(3);

# As we didn't reach the limit, we should have still only 4 records
$tester->check_table_count( 'logs', 6 );

# Now add another 2 - they should save automatically, together with previous 2
$tester->add_data( data => [
        { host => 'host1', program => 'prog1', msg => 'Message no 7' },
        { host => 'host2', program => 'prog1', msg => 'Message no 8' },
    ] );

sleep(3);

$tester->check_table_count( 'logs', 6 );

$tester->end_script();

$tester->check_table_count( 'logs', 8 );
