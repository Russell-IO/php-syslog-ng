package LogZilla::Test::LogProcessor;
use Moose;

use Cwd qw(abs_path);
use File::Temp qw(tempdir tmpnam);
use Time::HiRes qw(gettimeofday tv_interval);
use Data::Dumper;
use IPC::Open3;
use POSIX;
use MooseX::Params::Validate;
use Carp;

use Test::More;
use Test::Deep qw(cmp_deeply);
use Test::mysqld;

use LogZilla::Install;
use LogZilla::Config;
use LogZilla::RateMeter;

has name => (
    is => 'ro',
    isa => 'Str',
    default => 'tester',
);

has debug => (
    is => 'rw',
    isa => 'Bool',
    # A little ugly, but for module used for tests only seems acceptable
    default => sub { !! grep { /^(-d|--debug)$/ } @ARGV },
);

has profiling => (
    is => 'rw',
    isa => 'Bool',
    default => sub { !! grep { /^(-p|--profile)$/ } @ARGV },
);

has _root_dir => (
    is => 'ro',
    isa => 'Str',
    lazy_build => 1,
);

sub _build__root_dir {
    my( $self ) = @_;
    my $pkg_path = __FILE__;
    $pkg_path =~ s{/[^/]+$}{};
    return( abs_path( $pkg_path . "/../../.." ) );
}

has script_name => (
    is => 'ro',
    isa => 'Str',
    default => 'log_processor',
);

has _script_path => (
    is => 'ro',
    isa => 'Str',
    lazy_build => 1,
);

sub _build__script_path {
    my( $self ) = @_;
    return abs_path( $self->_root_dir . "/scripts/" . $self->script_name );
}

has default_script_args => (
    is => 'rw',
    isa => 'ArrayRef',
    lazy => 1,
    default => sub { $_[0]->debug ? [ '-d' => 5, '-v' ] : [ '-v' ]; },
);

has settings => (
    is => 'ro',
    isa => 'HashRef',
);

has _genlog_path => (
    is => 'ro',
    isa => 'Str',
    lazy_build => 1,
);

sub _build__genlog_path {
    my( $self ) = @_;
    return abs_path( $self->_root_dir . "/scripts/test/genlog" );
}

has _script_pid => (
    is => 'rw',
    isa => 'Maybe[Int]',
);

has [qw(_script_in _script_out)] => (
    is => 'rw',
    isa => 'Maybe[FileHandle]',
);

has _dbh => (
    is => 'rw',
    isa => 'Object',
);

has _test_mysqld => (
    is => 'rw',
    isa => 'Test::mysqld',
);

has _dedup_cache_size => (
    is => 'rw',
    isa => 'Int',
    default => -1,
);

sub BUILD {
    my( $self ) = @_;

    note( "Creating test database" );
    my $test_sql = Test::mysqld->new(
        my_cnf => {
            'skip-networking' => '',
        },
    ) or croak( "mysqld creation failed: $Test::mysqld::errstr" );
    note( "Created mysqld, pid=" . $test_sql->pid . ", " . "dsn=" . $test_sql->dsn );
    $self->_test_mysqld( $test_sql );

    my $dsn = $test_sql->dsn();

    note( "Populating test db with tables" );
    LogZilla::Install::init_test_db( $dsn, \&note );

    LogZilla::Config->set_db_for_tests( $dsn );
    LogZilla::Config->set_data_dir_for_tests( tempdir( CLEANUP => 1 ) );

    my $dbh = DBI->connect( $dsn );
    $self->_dbh( $dbh );

    if( $self->settings ) {
        $self->update_settings( %{$self->settings} );
    }
            
}

sub update_settings {
    my( $self, %settings ) = @_;
    my $sth = $self->_dbh->prepare( "UPDATE settings SET value = ? WHERE name = ?" );
    for my $k ( keys %settings ) {
        $sth->execute( $settings{$k}, $k );
    }
}

sub start_script {
    my( $self, @args ) = @_;

    @args = ( @{$self->default_script_args}, @args );

    my( $in_fh, $out_fh );
    my $pid = open3( $in_fh, $out_fh, undef, $self->_script_path, @args )
        or croak( "open3 " . $self->_script_path . ": $!" );
   
    $out_fh->autoflush(1);
    $in_fh->autoflush(1);
    # Make it non-blocking so flush_script_output won't block 
    $out_fh->blocking(0);
    
    $self->_script_pid( $pid );
    $self->_script_in( $in_fh );
    $self->_script_out( $out_fh );
}

sub end_script {
    my( $self ) = @_;

    close( $self->_script_in );
    my $t = time();
    while( 1 ) {
        my $r = waitpid( $self->_script_pid, POSIX::WNOHANG );
        if( $r == 0 ) {
            if( time() - $t > 10 ) {
                diag( "Script didn't finish in 10 seconds, sending KILL" );
                kill( 9, $self->_script_pid );
                $t = time();
            }
        }
        elsif( $r < 0 ) { # shouldn't happen
            diag( "Script lost? $!" );
            fail( "Script done properly" );
            return;
        }
        else {
            $self->flush_output();
            $self->_script_pid(undef);
            if( $? != 0 ) {
                my $exit = ($? >> 8);
                my $sig = ($? & 0x7f);
                diag( "Script exited with status $exit" . ( $sig ? " (signal $sig)" : "" ) );
            }
            ok( $? == 0, "Script done properly" );
            return;
        }
    }
}

sub flush_output {
    my( $self ) = @_;

    return unless $self->_script_pid;

    while( defined( my $l = $self->_script_out->getline ) ) {
        if($l =~ /dedup cache size: (\d+)/) {
            $self->_dedup_cache_size($1);
        }
        if( $self->debug ) {
            chop($l);
            note( "LP>> '$l'" );
        }
    }
}

# Measure time of processing generating data by genlog. Input is saved to
# temporary file, then log_processor is run on file, to measure only processing time.
sub time_genlog {
    my( $self, %params ) = @_;

    my @params = map { 
        $params{$_} eq 'TRUE' ? "--$_" : 
        $params{$_} eq 'FALSE' ? "--no$_" :
        "--$_=" . $params{$_} 
    } keys %params;

    my $tmpfile = File::Temp->new();

    my $genlog_cmd = join( " ", $self->_genlog_path, @params, "2>&1 >$tmpfile" );
    my $res = `$genlog_cmd`;
    if( $? ) {
        croak( "Genlog fail: $genlog_cmd\n$res" );
    }

    my $lp_cmd = join( " ", $self->_script_path, " <$tmpfile" );
    if( $self->profiling ) {
        my $name = $self->name;
        $ENV{NYTPROF} = "start=no:file=nytprof-$name.out";
        $lp_cmd = "perl -d:NYTProf $lp_cmd";
    }
    my $start = [gettimeofday];
    $res = `$lp_cmd`;
    my $elapsed = tv_interval($start);

    if( $? ) {
        croak( "LP fail: $lp_cmd\n$res" );
    }

    if( $self->profiling ) {
        my $name = $self->name;
        note( "Profiling data saved to nytprof-$name.out" );
    }

    return $elapsed;
}

sub _process_line {
    my( $self, $line, $now ) = @_;

    if( ref($line) eq 'ARRAY' ) {
        if( $line->[0] eq 'NOW' ) {
            $line->[0] = $now;
        }
        $line = join( "\t", @$line );
    }
    elsif( ref($line) eq 'HASH' ) {
        if( ! $line->{ts} || $line->{ts} eq 'NOW' ) {
            $line->{ts} = $now;
        }
        $line = join( "\t",
            $line->{ts},
            $line->{host}     || 'default_test_host',
            $line->{priority} || 0,
            $line->{program}  || 'default_test_program',
            $line->{msg}      || 'default message',
        );
    }
    $self->flush_output();
    if( $self->debug ) {
        note( "LP<< '$line'" );
    }
    $self->_script_in->print( "$line\n" );
}

sub _log_ts {
    my( $self, $t ) = @_;
    $t = time() if ! $t;
    return POSIX::strftime( "%Y-%m-%d %H:%M:%S", gmtime($t) );
}

sub process_data {
    my $self = shift;
    my( $options, $data ) = validated_list( \@_,
        options => { isa => 'ArrayRef', default => [] },
        data    => { isa => 'ArrayRef' },
    );

    my $now = $self->_log_ts();

    $self->start_script( @$options );
    for my $d ( @$data ) {
        $self->_process_line( $d, $now );
        $self->flush_output();
    }
    $self->end_script();
}

sub add_data {
    my $self = shift;
    my( $data ) = validated_list( \@_,
        data    => { isa => 'ArrayRef' },
    );
    for my $d ( @$data ) {
        $self->_process_line( $d, $self->_log_ts() );
        $self->flush_output();
    }
}

sub sql_do {
    my($self, $query) = @_;
    $self->_dbh->do($query);
}

sub check_table_count {
    my( $self, $table, $expected_count ) = @_;
    my $sth = $self->_dbh->prepare( "SELECT count(*) from $table" );
    $sth->execute();
    my $r = $sth->fetchrow_arrayref();
    return is( $r->[0], $expected_count, "There is $expected_count records in table $table" );
}

sub check_last_seen {
    my( $self, $table, $number, $expect ) = @_;
    my $sth = $self->_dbh->prepare( "SELECT * from $table ORDER BY lastseen DESC LIMIT ?" );
    $sth->execute( $number );
    my $res = $sth->fetchall_arrayref({});
    return cmp_deeply( $res, $expect, "Has proper $number last seen records in table $table" )
        || diag( "Got: " . Dumper($res) );
}

sub check_last_logs {
    my( $self, $number, $expect ) = @_;
    my $sth = $self->_dbh->prepare( 
        "SELECT logs.*, programs.name as program, mne.name as mne FROM logs " .
        "  LEFT JOIN programs ON ( logs.program = programs.crc ) " .
        "  LEFT JOIN mne ON ( logs.mne = mne.crc ) " .
        "ORDER BY fo DESC LIMIT ?" );
    $sth->execute( $number );
    my $res = $sth->fetchall_arrayref({});
    return cmp_deeply( $res, $expect, "Has proper $number last records in table logs" )
        || diag( "Got: " . Dumper($res) );
}

sub check_last_records {
    my( $self, $table, $number, $expect ) = @_;
    my $sth = $self->_dbh->prepare( "SELECT * from $table ORDER BY id DESC LIMIT ?" );
    $sth->execute( $number );
    my $res = $sth->fetchall_arrayref({});
    return cmp_deeply( $res, $expect, "Has proper $number last records in table $table" )
        || diag( "Got: " . Dumper($res) );
}

sub check_cache {
    my( $self, $name, $expect ) = @_;
    my $sth = $self->_dbh->prepare( "SELECT * FROM cache WHERE name = ?" );
    $sth->execute($name);
    my $r = $sth->fetchrow_hashref();
    return cmp_deeply( $r, $expect, "There is expected value in table cache for name '$name'" )
        || diag( "Got: " . Dumper($r) . "Expected: " . Dumper($expect) );
}

sub check_dedup_cache_size {
    my( $self, $expect ) = @_;
    return is( $self->_dedup_cache_size, $expect, "Dedup cache size as expected ($expect)" );
}

sub check_message_rates {
    my( $self, $period, $expect ) = @_;

    my $rm = LogZilla::RateMeter->new( dbh => $self->_dbh );
    return cmp_deeply( 
        $rm->get_rates( period => $period ),
        $expect,
        "message rates as expected for period $period" );
}

1;
