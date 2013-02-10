#!/usr/bin/perl
use strict;
use warnings;

use Find::Lib qw(../../lib);

use Test::More;
use Test::Deep;
use LogZilla::Test::LogProcessor;

# Really basic tests - just put few logs lines and check if database has proper data

plan tests => 18;

my $tester = LogZilla::Test::LogProcessor->new( 
    #default_script_args => [ '-dd', 1, '-ds', '-d', 5, '-v' ],
);
$tester->update_settings( DEDUP => 0 );

# Load first pack of data
$tester->process_data( data => [
        { host => 'host1', priority => 1*8+1, program => 'prog1', msg => 'Message no 1' },
        { host => 'host1', priority => 3*8+0, program => 'prog2', msg => 'Message no 2' },
        { host => 'host2', priority => 0*8+2, program => 'prog1', msg => 'Message no 3' },
        { host => 'host2', priority => 7*8+6, program => 'prog2', msg => 'Message no 4' },
    ] );

# Check number of records in tables 
$tester->check_table_count( 'logs', 4 );
$tester->check_table_count( 'hosts', 2 );
$tester->check_table_count( 'programs', 2 );

# And check last records in table logs
$tester->check_last_logs( 4, [
        superhashof({ host => 'host2', program => 'prog2', msg => 'Message no 4',
            severity => 6, facility => 7 }),
        superhashof({ host => 'host2', program => 'prog1', msg => 'Message no 3',
            severity => 2, facility => 0 }),
        superhashof({ host => 'host1', program => 'prog2', msg => 'Message no 2',
            severity => 0, facility => 3 }),
        superhashof({ host => 'host1', program => 'prog1', msg => 'Message no 1', 
            severity => 1, facility => 1 }),
    ] );

# Check last hosts (order doesn't matter as they are put from hash)
$tester->check_last_records( 'hosts', 2, bag(
        superhashof({ host => 'host1' }),
        superhashof({ host => 'host2' }),
    ) );
$tester->check_cache( 'msg_sum', superhashof({ value => 4 }) );
$tester->check_cache( 'max_id',  superhashof({ value => 4 }) );

# Check last programs (order doesn't matter - as above)
$tester->check_last_records( 'programs', 2, bag(
        superhashof({ name => 'prog1' }),
        superhashof({ name => 'prog2' }),
    ) );

# Now process another chunk of data (separate script run)
$tester->process_data( data => [
        { host => 'host1', priority => 5*8+5, program => 'prog5', msg => 'Message no 6' },
        { host => 'host3', priority => 3*8+0, program => 'prog2', msg => 'Message no 7' },
    ] );

# Check number of records again
$tester->check_table_count( 'logs', 6 );
$tester->check_table_count( 'hosts', 3 );
$tester->check_table_count( 'programs', 3 );

# Check helper values in cache
$tester->check_cache( 'msg_sum', superhashof({ value => 6 }) );
$tester->check_cache( 'max_id',  superhashof({ value => 6 }) );

# Check last 2, just inserted records
$tester->check_last_logs( 2, [
        superhashof({ host => 'host3', program => 'prog2', msg => 'Message no 7',
            severity => 0, facility => 3 }),
        superhashof({ host => 'host1', program => 'prog5', msg => 'Message no 6',
            severity => 5, facility => 5 }),
] );

# Check last host - should be the new one
$tester->check_last_records( 'hosts', 1, [
        superhashof({ host => 'host3' }),
    ] );

# Check last program - should be the new one
$tester->check_last_records( 'programs', 1, [
        superhashof({ name => 'prog5' }),
    ] );

# That's all, folks (for this basic test)
