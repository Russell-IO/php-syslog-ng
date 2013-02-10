#!/usr/bin/perl
use strict;
use warnings;

use Find::Lib qw(../../lib);

use Test::More;
use Test::Deep;
use LogZilla::Test::LogProcessor;

# This test checks if log_processor flush data properly if data stream doesn't end
# (how it is supposed to work usually, when connected to the syslog).
# This is almost the same like in 01-basic - only we use "send_data" instead of "process_data"
# and check only records counts

plan tests => 10;

my $tester = LogZilla::Test::LogProcessor->new();

$tester->start_script( '--save-period' => 1, '--save-msg-limit' => 10 ); 
# save after 10 messages collected, at least once per second

# Load first pack of data
$tester->add_data( data => [
        { host => 'host1', program => 'prog1', msg => 'Message no 1' },
        { host => 'host1', program => 'prog2', msg => 'Message no 2' },
        { host => 'host2', program => 'prog1', msg => 'Message no 3' },
        { host => 'host2', program => 'prog2', msg => 'Message no 4' },
    ] );

# Wait till data is processed - 1 second should be enough, but leave some margin
sleep(5);
$tester->flush_output(); # print debug info if available

# Check number of records in tables 
$tester->check_table_count( 'logs', 4 );
$tester->check_table_count( 'hosts', 2 );
$tester->check_table_count( 'programs', 2 );

# Add some data, with longer pauses between adds
for my $i ( 5 .. 9 ) {
    $tester->add_data( data => [
            { host => 'host5', program => 'prog1', msg => "Message no $i" },
        ] );
    sleep( $i % 2 + 1 ); # Sleep 1 or 2 seconds alternately
}

# Wait again
sleep(3);
$tester->flush_output(); # print debug info if available

# Check numbers again
$tester->check_table_count( 'logs', 9 );
$tester->check_table_count( 'hosts', 3 );
$tester->check_table_count( 'programs', 2 );

$tester->end_script();

# Make sure nothing changed after ending script
$tester->check_table_count( 'logs', 9 );
$tester->check_table_count( 'hosts', 3 );
$tester->check_table_count( 'programs', 2 );

