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

plan tests => 8;

my $tester = LogZilla::Test::LogProcessor->new(
    settings => { DEDUP => 0 },
);

$tester->start_script( '--save-period' => 1, '--save-msg-limit' => 10 ); 
# Just wait till 'save-period' pass
sleep(3);
$tester->end_script(); # this will check exit code and print diagnostic if needed

# Check number of records in tables 
$tester->check_table_count( 'logs', 0 );
$tester->check_table_count( 'hosts', 0 );
$tester->check_table_count( 'programs', 0 );

# Now try the same with deduplication enabled
$tester = LogZilla::Test::LogProcessor->new(
    settings => { DEDUP => 1 },
);
$tester->start_script( '--save-period' => 1, '--save-msg-limit' => 10 ); 
# Just wait till 'save-period' pass
sleep(3);
$tester->end_script(); # this will check exit code and print diagnostic if needed

# Check number of records in tables 
$tester->check_table_count( 'logs', 0 );
$tester->check_table_count( 'hosts', 0 );
$tester->check_table_count( 'programs', 0 );
