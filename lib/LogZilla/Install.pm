package LogZilla::Install;

use Data::Dumper;
use Carp;
use Cwd qw(abs_path);

my $ROOT_DIR = __FILE__;
$ROOT_DIR =~ s{/lib/LogZilla/.*}{};

sub init_test_db {
    my( $dsn, $debug_cb ) = @_;

    my( $params ) = ( $dsn =~ /DBI:mysql:(.*)/ );
    my %params = ( map { ( split(/=/) ) } split /;/, $params );

    my @files = (
        "$ROOT_DIR/scripts/sql/test-db.sql",
        "$ROOT_DIR/scripts/sql/procedures.sql",
    );

    # This uncommon syntax ("command mysql...") is to ignore aliases in bash,
    # as Clayton had some issue due to the alias for mysql being set on one machine.
    my $cmd = "cat @files | " .
        "command mysql -u root --password='' -S $params{mysql_socket} $params{dbname} ";
    $debug_cb->( "Running $cmd" ) if $debug_cb;
    my $res = `$cmd`;
    if( $? ) {
        croak( "Import failed:\n$cmd\n$res\n" );
    }
    
    my $dbh = DBI->connect( $dsn, 'root', '', { RaiseError => 1 } );

    my $sth = $dbh->prepare( "UPDATE settings SET value = ? WHERE name = ?" );
    $sth->execute( $ROOT_DIR, 'PATH_BASE' ); 

    my $dbtable = 'logs';
    
    $dbh->do( "
        Create view logs_suppressed as 
        select *
        from $dbtable where (($dbtable.`suppress` > now()) or
        $dbtable.`host` in (select `suppress`.`name` from
        `suppress` where ((`suppress`.`col` = 'host') and
        (`suppress`.`expire` > now()))) or $dbtable.`facility`
        in (select `suppress`.`name` from `suppress` where
        ((`suppress`.`col` = 'facility') and
        (`suppress`.`expire` > now()))) or $dbtable.`severity`
        in (select `suppress`.`name` from `suppress` where
        ((`suppress`.`col` = 'severity') and
        (`suppress`.`expire` > now()))) or $dbtable.`program` in
        (select `suppress`.`name` from `suppress` where
        ((`suppress`.`col` = 'program') and
        (`suppress`.`expire` > now()))) or $dbtable.`mne` in
        (select `suppress`.`name` from `suppress` where
        ((`suppress`.`col` = 'mnemonic') and
        (`suppress`.`expire` > now()))) or $dbtable.`counter` in (select
        `suppress`.`name` from `suppress` where
        ((`suppress`.`col` = 'counter') and
        (`suppress`.`expire` > now()))))
        " );

    $dbh->do( "
        Create view logs_unsuppressed as
        select *
        from $dbtable where (($dbtable.`suppress` < now()) and
        (not($dbtable.`host` in (select `suppress`.`name` from
        `suppress` where ((`suppress`.`col` = 'host') and
        (`suppress`.`expire` > now()))))) and
        (not($dbtable.`facility` in (select `suppress`.`name`
        from `suppress` where ((`suppress`.`col` = 'facility')
            and (`suppress`.`expire` > now()))))) and
        (not($dbtable.`severity` in (select `suppress`.`name`
        from `suppress` where ((`suppress`.`col` = 'severity')
            and (`suppress`.`expire` > now()))))) and
        (not($dbtable.`program` in (select `suppress`.`name`
        from `suppress` where ((`suppress`.`col` = 'program')
            and (`suppress`.`expire` > now()))))) and
        (not($dbtable.`mne` in (select `suppress`.`name` from
        `suppress` where ((`suppress`.`col` = 'mnemonic') and
        (`suppress`.`expire` > now()))))) and
        (not($dbtable.`counter` in (select `suppress`.`name`
        from `suppress` where ((`suppress`.`col` = 'counter')
            and (`suppress`.`expire` > now()))))))
        " );
}

1;
