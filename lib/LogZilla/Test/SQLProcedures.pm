package LogZilla::Test::SQLProcedures;
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
use Test::Exception;
use Test::mysqld;

use LogZilla::Install;
use LogZilla::Config;
use LogZilla::RateMeter;

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

has _dbh => (
    is => 'rw',
    isa => 'Object',
);

has _test_mysqld => (
    is => 'rw',
    isa => 'Test::mysqld',
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
}

sub get_partitions {
    my( $self, $table ) = @_;
    my $query = 
        "SELECT partition_name, partition_ordinal_position, partition_description " .
        "FROM information_schema.partitions " .
        "WHERE table_name = 'logs' AND table_schema = database() " .
        "ORDER BY partition_ordinal_position";
    my $sth = $self->_dbh->prepare($query);
    $sth->execute();
    my @partitions;
    while( my $r = $sth->fetchrow_arrayref() ) {
        push(@partitions, { 
                name => $r->[0], 
                position => $r->[1], 
                description => $r->[2],
            });
    }
    return @partitions;
}

sub set_date {
    my( $self, $date ) = @_;
    $self->_dbh->do( 'SET @test_current_date = ?', {}, $date );
    return;
}

sub call {
    my( $self, $name, @args ) = @_;
    my $placeholders = join(", ", ("?")x@args);
    $self->_dbh->do( "SET \@debug_msg = ''" );
    lives_ok {
        $self->_dbh->do( "CALL $name($placeholders)", {}, @args );
    } "Call to method $name" . ( @args ? " with args @args" : '' );
    my($dbg) = $self->_dbh->selectrow_array( 'SELECT @debug_msg' );
    if( $dbg ) {
        note( "SQL DEBUG: $dbg" );
    }
    return;
}

1;
