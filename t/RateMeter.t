#!/usr/bin/perl
use strict;
use warnings;

use FindBin;
use Find::Lib qw(../lib);
use Cwd qw(abs_path);
use File::Temp qw(tempdir);
use Time::HiRes qw(gettimeofday tv_interval);

use Test::More;
use Test::mysqld;
use Test::Exception;
use Test::Deep;
use Test::MockTime qw(:all);

use LogZilla::Config;
use LogZilla::Install;
use LogZilla::RateMeter;

plan tests => 14;

note( "Creating test database" );
my $test_sql = Test::mysqld->new(
    my_cnf => {
        'skip-networking' => '',
    },
) or croak( "mysqld creation failed: $Test::mysqld::errstr" );

my $dsn = $test_sql->dsn();

note( "Populating test db with tables" );
LogZilla::Install::init_test_db( $dsn, \&note );

LogZilla::Config->set_db_for_tests( $dsn );

my $cfg = LogZilla::Config->new( path => '/dev/null' );

my $meter = LogZilla::RateMeter->new( 
    config => $cfg,
    cleanup_policy => { 
        s => 120,  # keep only last 120 seconds
        m => 600,  # ...and last 10 minutes
    },
);

my $t0 = set_fixed_time( "2012-01-01T10:00:00Z" );

my $res = $meter->get_rates( start => $t0 - 10, end => $t0 - 1 );
cmp_deeply( $res, [], "Initially no rates are available" );

lives_ok {
    $meter->update(5,  $t0);
    $meter->update(11, $t0+1);
    $meter->update(7,  $t0+2);
} 'Added 5, 11 then 7 events in three following seconds';

$res = $meter->get_rates( start => $t0, end => $t0 + 3 );
cmp_deeply( $res, [
        superhashof({ ts_from => $t0,   count => 5 }),
        superhashof({ ts_from => $t0+1, count => 11 }),
        superhashof({ ts_from => $t0+2, count => 7 }),
    ], "Proper seconds data" );

cmp_deeply( 
    $meter->get_rates( start => $t0, end => $t0 + 60, period => 'm' ),
    [ superhashof({ ts_from => $t0, count => 23 }) ],
    "Proper minute data" );

cmp_deeply( 
    $meter->get_rates( start => $t0, end => $t0 + 3600, period => 'h' ),
    [ superhashof({ ts_from => $t0, count => 23 }) ],
    "Proper hour data" );

# Update the same values second time and add some new from different minute
lives_ok {
    $meter->update(3, $t0);
    $meter->update(1, $t0+1);
    $meter->update(9, $t0+65);
} 'Added some values for the same ts, and some for different minute';

# Create new meter object and make sure it will load those data properly too
my $meter2 = LogZilla::RateMeter->new( config => $cfg );

$res = $meter->get_rates( start => $t0, end => $t0 + 80 );
cmp_deeply( $res, [
        superhashof({ ts_from => $t0,    count => 5+3 }),
        superhashof({ ts_from => $t0+1,  count => 11+1 }),
        superhashof({ ts_from => $t0+2,  count => 7 }),
        superhashof({ ts_from => $t0+65, count => 9 }),
    ], "Proper seconds values from another object" );

cmp_deeply( $meter2->get_rates( start => $t0, end => $t0 + 120, period => 'm' ), [ 
        superhashof({ ts_from => $t0,    count => 23+4 }),
        superhashof({ ts_from => $t0+60, count => 9 }),
    ],
    "Proper minute data" );

cmp_deeply( 
    $meter2->get_rates( start => $t0, end => $t0 + 3600, period => 'h' ),
    [ superhashof({ ts_from => $t0, count => 23+4+9 }) ],
    "Proper hour data" );

# Now make some cleanup
set_fixed_time( $t0 + 300 );
$meter->cleanup();

cmp_deeply( 
    $meter->get_rates( start => $t0, end => $t0 + 60, period => 's' ),
    [],
    "No second data after cleanup" );

cmp_deeply( 
    $meter2->get_rates( start => $t0, end => $t0 + 120, period => 'm' ), [
        superhashof({ ts_from => $t0,    count => 23+4 }),
        superhashof({ ts_from => $t0+60, count => 9 }),
    ],
    "Proper minute data" );

cmp_deeply( 
    $meter2->get_rates( start => $t0, end => $t0 + 3600, period => 'h' ),
    [ superhashof({ ts_from => $t0, count => 23+4+9 }) ],
    "Proper hour data" );

# Now move ahead more, so we'll get real aggregation, minutely
set_fixed_time( $t0 + 1200 );
$meter->cleanup();

cmp_deeply( 
    $meter->get_rates( start => $t0, end => $t0 + 60, period => 'm' ),
    [],
    "No minute data after second aggregation" );

cmp_deeply( 
    $meter2->get_rates( start => $t0, end => $t0 + 3600, period => 'h' ),
    [ superhashof({ ts_from => $t0, count => 23+4+9 }) ],
    "Proper hour data after second aggregation" );
