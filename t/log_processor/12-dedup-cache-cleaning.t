#!/usr/bin/perl
use strict;
use warnings;

use Find::Lib qw(../../lib);

use Test::More;
use Test::Deep;
use LogZilla::Test::LogProcessor;

# Testing deduplication - with both disabled and enabled option

plan tests => 4;

my %common = ( host => 'host1', program => 'prog1' );

my $tester = LogZilla::Test::LogProcessor->new(
    settings => { DEDUP => 1, DEDUP_WINDOW => 90, DEDUP_CLEANUP_PERIOD => -1 },
);

$tester->start_script( '--save-period' => 1 );

# Load first pack of data with duplicates
$tester->add_data( data => [
        { ts => '2011-05-01 10:00:00', %common, msg => 'Message 1' },
        { ts => '2011-05-01 10:00:01', %common, msg => 'Message 1' },
        { ts => '2011-05-01 10:00:02', %common, msg => 'Message 2' },
        { ts => '2011-05-01 10:00:03', %common, msg => 'Message 2' },
        { ts => '2011-05-01 10:00:04', %common, msg => 'Message 1' },
    ] );

sleep(3);
$tester->flush_output();
# Check number of records in dedup cache
$tester->check_dedup_cache_size(2);


# Load another pack of data, more duplicates but with later timestamp
$tester->add_data( data => [
        { ts => '2011-05-01 10:01:00', %common, msg => 'Message 1' },
        { ts => '2011-05-01 10:01:01', %common, msg => 'Message 1' },
        { ts => '2011-05-01 10:01:02', %common, msg => 'Message 2' },
        { ts => '2011-05-01 10:01:03', %common, msg => 'Message 2' },
        { ts => '2011-05-01 10:01:04', %common, msg => 'Message 3' },
        { ts => '2011-05-01 10:01:05', %common, msg => 'Message 1' },
    ] );

sleep(3);
$tester->flush_output();

# One new entry in cache should arrive
$tester->check_dedup_cache_size(3);

# Add some more data, including duplicates but with longer time - Message 1 and 3 should
# be removed from cache
$tester->add_data( data => [
        { ts => '2011-05-01 10:06:00', %common, msg => 'Message 2' },
        { ts => '2011-05-01 10:06:01', %common, msg => 'Message 4' },
        { ts => '2011-05-01 10:06:02', %common, msg => 'Message 2' },
    ] );
sleep(3);
$tester->flush_output();

# Another one new entry in cache should arrive
$tester->check_dedup_cache_size(2);

# Finally, one message which should clean all other entries in cache
$tester->add_data( data => [
        { ts => '2011-05-01 10:10:00', %common, msg => 'Message 1' },
    ] );
sleep(3);
$tester->flush_output();

# No more "Message 1" in cache, others should stay
$tester->check_dedup_cache_size(1);
