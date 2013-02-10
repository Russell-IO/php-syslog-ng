#!/usr/bin/perl
use strict;
use warnings;

use Find::Lib qw(../../lib);
use Data::Dumper;
use Test::More tests => 17;
use Test::Deep;

use LogZilla::Test::SQLProcedures;

my $tester = LogZilla::Test::SQLProcedures->new();

$tester->set_date( "2011-01-01" );

sub d_cmp_deeply {
    my( $got, $exp, $descr ) = @_;
    cmp_deeply( $got, $exp, $descr ) or 1 or
    diag( "Got: " . Dumper($got) . "Expected: " . Dumper($exp) );
}

# Initially we have one NULL partition
d_cmp_deeply( 
    [ $tester->get_partitions("logs") ],
    [ superhashof({ name => undef }) ],
    "one unnamed partition before first call",
);

# Call it for the first time - we should get 10 new partitions
$tester->call( "manage_logs_partitions" );

d_cmp_deeply( 
    [ $tester->get_partitions("logs") ],
    [ map { superhashof({ name => sprintf('p201101%02d', $_) }) } 1 .. 10 ],
    "10 partitions were created after first call",
);

# Call it again - nothing should change (and of course no error!)

$tester->call( "manage_logs_partitions" );

d_cmp_deeply( 
    [ $tester->get_partitions("logs") ],
    [ map { superhashof({ name => sprintf('p201101%02d', $_) }) } 1 .. 10 ],
    "nothing changed after second call",
);

# Move one day ahead
$tester->set_date( "2011-01-02" );
$tester->call( "manage_logs_partitions" );

d_cmp_deeply( 
    [ $tester->get_partitions("logs") ],
    [ map { superhashof({ name => sprintf('p201101%02d', $_) }) } 1 .. 11 ],
    "we have 11 partitions after moving one day ahead and another call",
);

# And now go back into the time
$tester->set_date( "2011-01-01" );
$tester->call( "manage_logs_partitions" );

d_cmp_deeply( 
    [ $tester->get_partitions("logs") ],
    [ map { superhashof({ name => sprintf('p201101%02d', $_) }) } 1 .. 11 ],
    "nothing changes after going back into the time",
);

# Skip more days
$tester->set_date( "2011-01-06" );
$tester->call( "manage_logs_partitions" );

d_cmp_deeply( 
    [ $tester->get_partitions("logs") ],
    [ map { superhashof({ name => sprintf('p201101%02d', $_) }) } 1 .. 15 ],
    "we have 15 partitions after moving another 3 days and another call",
);

# Skip more than 10 days
$tester->set_date( "2011-03-01" );
$tester->call( "manage_logs_partitions" );

d_cmp_deeply( 
    [ $tester->get_partitions("logs") ],
    [ ( map { superhashof({ name => sprintf('p201101%02d', $_) }) } 1 .. 15 ),
      ( map { superhashof({ name => sprintf('p201103%02d', $_) }) } 1 .. 10 ),
    ],
    "we have 15 old partitions and 10 new after bigger time leap and call",
);

# And now go back into the time (again) - nothing should change
$tester->set_date( "2011-02-01" );
$tester->call( "manage_logs_partitions" );

d_cmp_deeply( 
    [ $tester->get_partitions("logs") ],
    [ ( map { superhashof({ name => sprintf('p201101%02d', $_) }) } 1 .. 15 ),
      ( map { superhashof({ name => sprintf('p201103%02d', $_) }) } 1 .. 10 ),
    ],
    "nothing changes after going back into the time (again)",
);

# And go back into previous date
$tester->set_date( "2011-03-01" );
$tester->call( "manage_logs_partitions" );

d_cmp_deeply( 
    [ $tester->get_partitions("logs") ],
    [ ( map { superhashof({ name => sprintf('p201101%02d', $_) }) } 1 .. 15 ),
      ( map { superhashof({ name => sprintf('p201103%02d', $_) }) } 1 .. 10 ),
    ],
    "nothing changes too after leaping back to already processed date",
);

