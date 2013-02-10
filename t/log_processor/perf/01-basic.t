#!/usr/bin/perl
use strict;
use warnings;

use Find::Lib qw(../../../lib);

use Test::More;
use Test::Deep;
use File::Temp;
use LogZilla::Test::LogProcessor;

plan tests => 1;

my $ev_num = 100_000;

my $tester = LogZilla::Test::LogProcessor->new(
    # Uncomment line below to test old script
    #script_name => 'db_insert.pl',
    name => 'plain',
);
$tester->update_settings( DEDUP => 0 );

note( "Inserting $ev_num records..." );
my $time = $tester->time_genlog(
    'number-of-events' => $ev_num, 
    'hosts-num' => 500,
    'programs-num' => 40,
);

note( sprintf( "Total time taken: %0.3f s", $time ) );
note( sprintf( "Average performance: %0.2f rec/s", $ev_num / $time ) );

cmp_ok( $ev_num / $time, '>', 2000, "Can insert more than 2000 rec/s" );

