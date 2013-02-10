package LogZilla::DBCounters;

use Moose;
use Carp;
use JSON::XS;
use BerkeleyDB;
use File::Slurp;
use Digest::CRC qw(crc32);

has name => (
    is => 'ro',
    isa => 'Str',
    required => 1,
);

has use_dictionary => (
    is => 'ro',
    isa => 'Bool',
    default => 0,
);

has _dictionary => (
    is => 'ro',
    isa => 'Maybe[HashRef]',
    lazy_build => 1,
);

sub _build__dictionary {
    my( $self ) = @_;
    my $fn = sprintf( "%s/%s-dictionary.db", $self->config->bdb_dir, $self->name );
    tie( my %h, 'BerkeleyDB::Hash', -Filename => $fn, -Flags => DB_CREATE )
        or croak( "Couldn't create DB $fn: $! $BerkeleyDB::Error" );
    return \%h;
}

has config => (
    is => 'ro',
    isa => 'LogZilla::Config',
    required => 1,
);

has _json => (
    is => 'ro',
    isa => 'JSON::XS',
    default => sub { JSON::XS->new->utf8; },
);

my @PERIODS = qw(hourly daily weekly total);

has _counters => (
    is => 'ro',
    isa => 'HashRef[HashRef]',
    lazy_build => 1,
);

sub _build__counters {
    my( $self ) = @_;
    my $counters = {};
    for my $period ( @PERIODS ) {
        my $fn = sprintf( "%s/%s-counters-%s.db", $self->config->bdb_dir, $self->name, $period );
        tie( my %h, 'BerkeleyDB::Hash', -Filename => $fn, -Flags => DB_CREATE )
            or croak( "Couldn't create DB $fn: $! $BerkeleyDB::Error" );
        $counters->{$period} = \%h;
    }
    return $counters;
}

has top10_filepath => (
    is => 'ro',
    isa => 'HashRef[Str]',
    lazy_build => 1,
);

sub _build_top10_filepath {
    my( $self ) = @_;
    my $paths = {};
    for my $period ( @PERIODS ) {
        $paths->{$period} = sprintf( "%s/%s-top10-%s.json", $self->config->bdb_dir, $self->name,
           $period );
    }
    return $paths;
}

has top10 => (
    is => 'ro',
    isa => 'HashRef[ArrayRef]',
    lazy_build => 1,
);

sub _build_top10 {
    my( $self ) = @_;
    my $top10 = {};
    for my $period ( @PERIODS ) {
        if( -f $self->top10_filepath->{$period} ) {
            my $data = scalar( read_file( $self->top10_filepath->{$period} ) );
            $top10->{$period} = $self->_json->decode( $data )->{list};
        }
        else {
            $top10->{$period} = [];
        }
    }
    return $top10;
}

sub _save_top10 {
    my( $self ) = @_;

    for my $period ( @PERIODS ) {
        write_file( $self->top10_filepath->{$period},
            $self->_json->encode( { list => $self->top10->{$period} } ) );
    }
}

has last_seen_filepath => (
    is => 'ro',
    isa => 'Str',
    lazy_build => 1,
);

sub _build_last_seen_filepath {
    my( $self ) = @_;
    return sprintf( "%s/%s-lastseen.json", $self->config->bdb_dir, $self->name );
}

has last_seen => (
    is => 'ro',
    isa => 'ArrayRef',
    lazy_build => 1,
);

sub _build_last_seen {
    my( $self ) = @_;
    if( -f $self->last_seen_filepath ) {
        my $data = scalar( read_file( $self->last_seen_filepath ) );
        return $self->_json->decode( $data )->{list};
    }
    else {
        return [];
    }
}

sub _save_last_seen {
    my( $self ) = @_;
    write_file( $self->last_seen_filepath, 
        $self->_json->encode( { list => $self->last_seen } ) );
}

has _last_update_ts => (
    is => 'rw',
    isa => 'Int',
    default => 0,
);

sub update {
    my( $self, $name, $count ) = @_;

    my $now = time();

    # First, check if previous update was on previous hour - then we have to 
    # dump data to the DB and reset counters.

    my $key;
    if( $self->use_dictionary ) {
        $key = crc32( $name );
        $self->_dictionary->{$key} = $name;
    }
    else {
        $key = $name;
    }

    # Then update proper counters, and recalculate lists
    for my $period ( @PERIODS ) {
        $self->_counters->{$period}->{$key} += $count;
        my $new_count = $self->_counters->{$period}->{$key};
        # Only update top10 if we are bigger than last element on the list,
        # or list has less than 10 elements
        my $top10 = $self->top10->{$period};
        if( scalar( @{$top10} ) < 10 || $new_count > $top10->[-1]->{seen} ) {
            @{$top10} = grep { $_->{key} ne $key } @{$top10};
            my $i = 0;
            while( $i <= $#{$top10} ) {
                last if $top10->[$i] < $new_count;
                $i++;
            }
            splice( @{$top10}, $i, 0, { name => $name, key => $key, seen => $new_count } );
            if( scalar(@{$top10}) > 10 ) {
                pop( @{$top10} );
            }
            $self->top10->{$period} = $top10;
        }
    }

    @{$self->last_seen} = grep { $_->{key} ne $key } @{$self->last_seen};
    unshift( @{$self->last_seen}, {
            key => $key,
            name => $name,
            seen => $self->_counters->{total}->{$key},
            time => $now,
        } );
    if( scalar(@{$self->last_seen}) > 10 ) {
        pop( @{$self->last_seen} );
    }
    
    # Finally, save all lists if they were not saved in this second yet
    if( $now > $self->_last_update_ts + 10 ) {
        $self->_save_last_seen();
        $self->_save_top10();
        $self->_last_update_ts($now);
    }
}

sub save {
    my( $self ) = @_;
    $self->_save_last_seen();
    $self->_save_top10();
    $self->_last_update_ts(time());
}

__PACKAGE__->meta->make_immutable();
1;
