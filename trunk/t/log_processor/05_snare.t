#!/usr/bin/perl
use strict;
use warnings;

use Find::Lib qw(../../lib);

use Test::More;
use Test::Deep;
use LogZilla::Test::LogProcessor;
use LogZilla::Util::Snare qw(:all);

# Really basic tests - just put few logs lines and check if database has proper data
plan skip_all => 'Test not ready yet';

plan tests => 12;

my $tester = LogZilla::Test::LogProcessor->new(
    settings => { SNARE => 1 }, # Maybe it's not needed at all
);

# Load first pack of data
$tester->process_data( data => [
        { host => 'host1', msg => build_snare_msg({eid=>123, source=>'Security'}) },
        { host => 'host1', msg => build_snare_msg({eid=>124, source=>'System'}) },
        { host => 'host2', msg => build_snare_msg({eid=>125, source=>'System'}) },
        { host => 'host2', msg => build_snare_msg({eid=>126, source=>'Application'}) },

        { host => 'host1', prog => 'prog1', msg => 'Normal message nr 1' },
    ] );

# Check number of records in tables 
$tester->check_table_count( 'logs', 5 );
$tester->check_table_count( 'hosts', 2 );
$tester->check_table_count( 'programs', 4 );  # for snare program it is copied from "source"
$tester->check_table_count( 'snare_eid', 5 ); # including 0 for non-snare messages

# And check last records in table logs TODO
$tester->check_last_logs( 4, [
        superhashof({ host => 'host2', program => 'prog2', msg => 'Message no 4', }),
        superhashof({ host => 'host2', program => 'prog1', msg => 'Message no 3', }),
        superhashof({ host => 'host1', program => 'prog2', msg => 'Message no 2', }),
        superhashof({ host => 'host1', program => 'prog1', msg => 'Message no 1', }),
    ] );
