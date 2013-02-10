#!/usr/bin/perl
use strict;
use warnings;

use Find::Lib qw(../../lib);

use Test::More;
use Test::Deep;
use Test::MockTime qw(:all);
use LogZilla::Test::LogProcessor;
use LogZilla::Util::Snare qw(:all);

# Really basic tests - just put few logs lines and check if database has proper data
  
plan tests => 3;

my $tester = LogZilla::Test::LogProcessor->new(
    settings => { SNARE => 1, DEDUP => 1 }, # Maybe it's not needed at all
    default_script_args => [ '--save-period' => 9999, '--save-msg-limit' => 4, '-d' => 5, '-v' ],
);

# Load first pack of data
my $ts0 = '2012-01-01 01:00:00';
my $ts1 = '2012-01-01 01:00:10';
my $ts2 = '2012-01-01 01:00:20';
my %common = ( host => 'host1', program => 'prog1' );
$tester->process_data( data => [
        { ts => $ts0, %common, msg => 'Message with mne %ABC-1-XYZ:' },
        { ts => $ts0, %common, msg => 'Message with mne %ABC-2-XYZ:' },
        { ts => $ts0, %common, msg => 'Message with mne %ABC-2-XYZ:' },
        { ts => $ts0, %common, msg => 'Another normal message' },
        { ts => $ts1, %common, msg => 'Message with mne %ABC-1-XYZ:' },
        { ts => $ts1, %common, msg => 'Message with mne %ABC-2-XYZ:' },
        { ts => $ts1, %common, msg => 'Message with mne %ABC-2-XYZ:' },
        { ts => $ts1, %common, msg => 'Another normal message' },
        { ts => $ts2, %common, msg => 'Message with mne %ABC-1-XYZ:' },
        { ts => $ts2, %common, msg => 'Message with mne %ABC-2-XYZ:' },
        { ts => $ts2, %common, msg => 'Message with mne %ABC-2-XYZ:' },
        { ts => $ts2, %common, msg => 'Another normal message' },
    ] );

$tester->check_last_logs( 10, [
        superhashof({ 
                msg => 'Message with mne %ABC-1-XYZ:', 
                counter => 3,
                fo => $ts0,
                lo => $ts2,
            }),
        superhashof({ 
                msg => 'Message with mne %ABC-2-XYZ:', 
                counter => 6,
                fo => $ts0,
                lo => $ts2,
            }),
        superhashof({ 
                msg => 'Another normal message',
                counter => 3,
                fo => $ts0,
                lo => $ts2,
            }),
    ] );

$tester->check_last_seen( 'mne', 10, bag(
        superhashof({ lastseen => $ts2, seen => 3, name => 'ABC-1-XYZ' }),
        superhashof({ lastseen => $ts2, seen => 6, name => 'ABC-2-XYZ' }),
        superhashof({ lastseen => $ts2, seen => 3, name => 'None' }),
    ) );

