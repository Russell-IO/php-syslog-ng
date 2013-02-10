#!/usr/bin/perl
use strict;
use warnings;

use Find::Lib qw(../../lib);

use Test::More;
use Test::Deep;
use LogZilla::Test::LogProcessor;

# This checks if fields in hosts and other tables have field "hidden" set to false after
# they appear in a message. This is for ticket #418.

plan tests => 6;

my $tester = LogZilla::Test::LogProcessor->new( 
    settings => { DEDUP => 0 },
);

# Load first pack of data
$tester->process_data( data => [
        { host => 'host1', priority => 1*8+1, program => 'prog1', msg => 'Message no 1' },
        { host => 'host1', priority => 3*8+0, program => 'prog2', msg => 'Message no 2' },
        { host => 'host2', priority => 0*8+2, program => 'prog1', msg => 'Message no 3' },
        { host => 'host2', priority => 7*8+6, program => 'prog2', msg => 'Message no 4' },
    ] );

# Now "hide" all entries in table 'hosts'

$tester->sql_do("UPDATE hosts    SET hidden = 'true'");
$tester->sql_do("UPDATE programs SET hidden = 'true'");

# Check last hosts - should have "hidden" field set
$tester->check_last_records( 'hosts', 2, bag(
        superhashof({ host => 'host1', hidden => 'true' }),
        superhashof({ host => 'host2', hidden => 'true' }),
    ) );

# Same for programs
$tester->check_last_records( 'programs', 2, bag(
        superhashof({ name => 'prog1', hidden => 'true' }),
        superhashof({ name => 'prog2', hidden => 'true' }),
    ) );

# Now add two new records
$tester->process_data( data => [
        { host => 'host1', priority => 1*8+1, program => 'prog1', msg => 'Message no 5' },
    ] );

# Check last hosts again, now hidden for 'host1' should be cleared
$tester->check_last_records( 'hosts', 2, bag(
        superhashof({ host => 'host1', hidden => 'false' }),
        superhashof({ host => 'host2', hidden => 'true' }),
    ) );

# Same for program prog1
$tester->check_last_records( 'programs', 2, bag(
        superhashof({ name => 'prog1', hidden => 'false' }),
        superhashof({ name => 'prog2', hidden => 'true' }),
    ) );

