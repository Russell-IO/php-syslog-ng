#!/usr/bin/perl
use strict;
use warnings;

use Find::Lib qw(../../lib);

use Test::More;
use Test::Deep;
use LogZilla::Test::LogProcessor;

# Testing deduplication - with both disabled and enabled option

plan tests => 8;

my $tester = LogZilla::Test::LogProcessor->new(
    settings => { DEDUP => 0 },
    #default_script_args => [ '-d', 5, '-v' ],
);

my %common1 = ( host => 'host1', program => 'prog1' );
my %common2 = ( host => 'host2', program => 'prog2' );

# Load first pack of data with duplicates, but deduplication disabled
$tester->process_data( data => [
        { ts => '2011-05-01 10:00:00', %common1, msg => 'Message 1' },
        { ts => '2011-05-01 10:00:00', %common1, msg => 'Message 1' },
        { ts => '2011-05-01 10:00:01', %common1, msg => 'Message 1' },
        { ts => '2011-05-01 10:00:02', %common1, msg => 'Message 2' },
        { ts => '2011-05-01 10:00:03', %common1, msg => 'Message 2' },
        { ts => '2011-05-01 10:00:04', %common1, msg => 'Message 1' },
    ] );

$tester->check_message_rates( 's', [
        superhashof({ count => 2 }),
        superhashof({ count => 1 }),
        superhashof({ count => 1 }),
        superhashof({ count => 1 }),
        superhashof({ count => 1 }),
    ] );

$tester->check_message_rates( 'm', [
        superhashof({ count => 6 }),
    ] );

$tester->check_message_rates( 'h', [
        superhashof({ count => 6 }),
    ] );

# Enable deduplication, then load some data again
$tester->update_settings( DEDUP => 1, DEDUP_WINDOW => 30 );
$tester->process_data( data => [
        { ts => '2011-05-01 10:01:00', %common1, msg => 'Message 1' },
        { ts => '2011-05-01 10:01:00', %common1, msg => 'Message 1' },
        { ts => '2011-05-01 10:01:01', %common1, msg => 'Message 1' },
        { ts => '2011-05-01 10:01:02', %common1, msg => 'Message 2' },
        { ts => '2011-05-01 10:01:03', %common1, msg => 'Message 2' },
        { ts => '2011-05-01 10:01:04', %common1, msg => 'Message 1' },
    ] );

$tester->check_message_rates( 's', [
        superhashof({ count => 2 }),
        superhashof({ count => 1 }),
        superhashof({ count => 1 }),
        superhashof({ count => 1 }),
        superhashof({ count => 1 }),
        superhashof({ count => 2 }),
        superhashof({ count => 1 }),
        superhashof({ count => 1 }),
        superhashof({ count => 1 }),
        superhashof({ count => 1 }),
    ] );

$tester->check_message_rates( 'm', [
        superhashof({ count => 6 }),
        superhashof({ count => 6 }),
    ] );

$tester->check_message_rates( 'h', [
        superhashof({ count => 12 }),
    ] );
