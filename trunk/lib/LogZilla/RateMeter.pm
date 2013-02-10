package LogZilla::RateMeter;

use Moose;
use MooseX::Params::Validate;
use Carp;

has name => (
    is => 'ro',
    isa => 'Str',
    default => 'msg',
);

has config => (
    is => 'ro',
    isa => 'LogZilla::Config',
);

has cleanup_policy => (
    is => 'ro',
    isa => 'HashRef',
    default => sub { 
        return {
            s => 10*60,    # keep last 10 minutes data by second
            m => 10*60*60, # ...and last 10 hours data by minute
        };
    },
);

has dbh => (
    is => 'ro',
    isa => 'Object',
    lazy_build => 1,
);

# This will trigger only if not set directly in constructor - as it is done in log_processor
sub _build_dbh {
    my( $self ) = @_;

    return DBI->connect( 
        $self->config->db_dsn, 
        $self->config->db_user, 
        $self->config->db_pass, 
        { RaiseError => 1 },
    );
}

has _update_sth => (
    is => 'ro',
    isa => 'Object',
    lazy_build => 1,
);

sub _build__update_sth {
    my( $self ) = @_;
    return $self->dbh->prepare( 
        "INSERT INTO events_per_second (name, ts_from, count) " .
        "VALUES (?, ?, ?) " .
        "ON DUPLICATE KEY UPDATE count = count + values(count)"
    );
}

sub update {
    my( $self, $num, $ts ) = @_;

    $self->_update_sth->execute( $self->name, $ts, $num );
}

# This is used mostly for tests, as values are read usually from PHP
sub get_rates {
    my $self = shift;
    my( $start, $end, $period ) = validated_list( \@_,
        start  => { isa => 'Int', optional => 1 },
        end    => { isa => 'Int', optional => 1 },
        period => { isa => 'Str', default => 's' },
    );

    my $table = {
        s => 'events_per_second',
        m => 'events_per_minute',
        h => 'events_per_hour',
    }->{$period} or croak( "Unknown period, should be one of 's', 'm' or 'h'" );

    my $q = "SELECT * FROM $table WHERE name = ? ";
    my @params = ( $self->name );
    if( $start ) {
        $q .= "AND ts_from >= ? ";
        push( @params, $start );
    }
    if( $end ) {
        $q .= "AND ts_from < ?";
        push( @params, $end );
    }
    $q .= "ORDER BY ts_from";

    my $sth = $self->dbh->prepare( $q );
    $sth->execute( @params );

    return $sth->fetchall_arrayref({});
}

sub cleanup {
    my( $self ) = @_;
    my $now = time();
    if( $self->cleanup_policy->{s} ) {
        my $sth = $self->dbh->prepare( "DELETE FROM events_per_second " .
            "WHERE ts_from < ?" );
        $sth->execute( $now - $self->cleanup_policy->{s} );
    }
    if( $self->cleanup_policy->{m} ) {
        my $sth = $self->dbh->prepare( "DELETE FROM events_per_minute " .
            "WHERE ts_from < ?" );
        $sth->execute( $now - $self->cleanup_policy->{m} );
    }
    if( $self->cleanup_policy->{h} ) {
        my $sth = $self->dbh->prepare( "DELETE FROM events_per_hour " .
            "WHERE ts_from < ?" );
        $sth->execute( $now - $self->cleanup_policy->{h} );
    }
}

__PACKAGE__->meta->make_immutable();
1;
