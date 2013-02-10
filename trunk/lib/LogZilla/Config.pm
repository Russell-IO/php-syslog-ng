package LogZilla::Config;

use Moose;
use Carp;
use JSON::XS;

# Path to the PHP config file, where we read all data from in BUILD
has path => (
    is => 'ro',
    isa => 'Str',
    required => 1,
);

has db_user => (
    is => 'ro',
    isa => 'Str',
    writer => '_set_db_user',
);

has db_pass => (
    is => 'ro',
    isa => 'Str',
    writer => '_set_db_pass',
);

has db_name => (
    is => 'ro',
    isa => 'Str',
    writer => '_set_db_name',
);

has db_host => (
    is => 'ro',
    isa => 'Str',
    writer => '_set_db_host',
);

has db_port => (
    is => 'ro',
    isa => 'Int',
    writer => '_set_db_port',
);

has db_sock => (
    is => 'ro',
    isa => 'Str',
    writer => '_set_db_sock',
);

has data_dir => (
    is => 'ro',
    isa => 'Str',
    writer => '_set_data_dir',
);

# Static method for passing data dir in $ENV, so child
# processes will use them regardless the configuration file. 
# Used by tests for log_processor script.
sub set_data_dir_for_tests {
    my( $class, $dir ) = @_;
    $ENV{LZ_TEST_DATA_DIR} = $dir;
}

# Static method for passing database parameters in $ENV, so child
# processes will use them regardless the configuration file. 
# Used by tests for db_insert script.
sub set_db_for_tests {
    my( $class, $dsn ) = @_;

    my( $params ) = ( $dsn =~ /DBI:mysql:(.*)/ );
    my %params = ( map { ( split(/=/) ) } split /;/, $params );

    my %dsn_to_env = (
        mysql_socket => 'DBSOCK',
        dbname       => 'DBNAME',
        database     => 'DBNAME',
        user         => 'DBADMIN',
        password     => 'DBADMINPW',
        host         => 'DBHOST',
        port         => 'DBPORT',
    );

    my %config = ();
    for my $k ( keys %params ) {
        if( exists( $dsn_to_env{$k} ) ) {
            $config{ $dsn_to_env{$k} } = $params{$k};
        }
    }

    $ENV{LZ_TEST_DB} = JSON::XS->new->encode( \%config );
}

# Read configuration from file, unless we have $ENV{LZ_TEST_DB} set, then
# settings from this variable (probably set via set_db_for_tests in parent
# process) will be used instead.
# After this method attribute dsn is ready to use
sub BUILD {
    my( $self ) = @_;

    my %config_to_read = (
        DBADMIN   => 'db_user',
        DBADMINPW => 'db_pass',
        DBNAME    => 'db_name',
        DBHOST    => 'db_host',
        DBPORT    => 'db_port',
        DBSOCK    => 'db_sock',
        DATA_DIR  => 'data_dir',
    );

    if( $ENV{LZ_TEST_DB} ) {
        my $settings = JSON::XS->new->decode( $ENV{LZ_TEST_DB} );
        for my $k ( keys( %{$settings} ) ) {
            if( exists($config_to_read{$k}) ) {
                my $attr = $config_to_read{$k};
                my $setter = "_set_$attr";
                $self->$setter( $settings->{$k} );
            }
        }
    }
    else {
        open( F, $self->path ) or croak( "Can't open " . $self->path . ": $!" );
        while(<F>) {
            chop;
            if( /DEFINE\s*\(\s*'(\w+)'\s*,\s*'([^']*)'\s*\)/ && exists($config_to_read{$1}) ) {
                my $attr = $config_to_read{$1};
                my $setter = "_set_$attr";
                $self->$setter( $2 );
            }
        }
        close(F);
    }
    
    if( $ENV{LZ_TEST_DATA_DIR} ) {
        $self->_set_data_dir( $ENV{LZ_TEST_DATA_DIR} );
    }

}

has db_dsn => (
    is => 'ro',
    isa => 'Str',
    lazy_build => 1,
);

sub _build_db_dsn {
    my( $self ) = @_;
    if( $self->db_sock ) {
        return sprintf( "DBI:mysql:database=%s;mysql_socket=%s", 
            $self->db_name, $self->db_sock );
    }
    else {
        return sprintf( "DBI:mysql:database=%s;host=%s;port=%s", 
            $self->db_name, $self->db_host, $self->db_port );
    }
}

__PACKAGE__->meta->make_immutable();
1;
