#!/usr/bin/perl

#
# install.pl
#
# Developed by Clayton Dukes <cdukes@logzilla.pro>
# Copyright (c) 2010 LogZilla, LLC
# All rights reserved.
#

use strict;

$| = 1;

################################################
# Help user if Perl mods are missing
################################################
my @mods = (qw(DBI Date::Calc Term::ReadLine File::Copy Digest::MD5 LWP::Simple File::Spec String::CRC32 MIME::Lite IO::Socket::INET Getopt::Long CHI Net::SNMP Log::Fast Test::mysqld PerlIO::Util Find::Lib MooseX::Params::Validate Test::Deep Test::MockTime Date::Simple ));

foreach my $mod (@mods) {
    ( my $fn = "$mod.pm" ) =~ s|::|/|g;    # Foo::Bar::Baz => Foo/Bar/Baz.pm
    if ( eval { require $fn; 1; } ) {
        ##print "Module $mod loaded ok\n";
    } else {
        print "You are missing a required Perl Module: $mod\nI will attempt to install it for you.\n";
        system("(echo o conf prerequisites_policy follow;echo o conf commit)|cpan");
        #my $ok = &getYN( "Shall I attempt to install it for you?", "y" );
        #if ( $ok =~ /[Yy]/ ) {
            require CPAN;
            CPAN::install($mod);
            #} else {
            #print "LogZilla requires $mod\n";
            #exit;
            #}
	print "Module installation complete. Please re-run install\n";
	exit;
    }
}


use Cwd;
use File::Basename;
use POSIX;
require DBI;
require CHI;
require Date::Calc;
require Term::ReadLine;
require File::Copy;
require Digest::MD5->import("md5_hex");
require LWP::Simple->import('getstore');
require LWP::Simple->import('is_success');
require File::Spec;
require String::CRC32;
require MIME::Lite;
require IO::Socket::INET;
require Getopt::Long;
require Net::SNMP;
require Date::Simple;



sub prompt {
    my ( $prompt, $default ) = @_;
    my $defaultValue = $default ? "[$default]" : "";
    print "$prompt $defaultValue: ";
    chomp( my $input = <STDIN> );
    return $input ? $input : $default;
}

my $version    = "4.26";
my $subversion = ".615";

# Grab the base path
my $lzbase = getcwd;
$lzbase =~ s/\/scripts//g;
my $now = localtime;

my ( $sec, $min, $hour, $curmday, $curmon, $curyear, $wday, $yday, $isdst ) = localtime time;
$curyear = $curyear + 1900;
$curmon  = $curmon + 1;
my ( $year, $mon, $mday ) = Date::Calc::Add_Delta_Days( $curyear, $curmon, $curmday, 1 );
my $pAdd = "p" . $year . sprintf( "%02d", $mon ) . sprintf( "%02d", $mday );
my $dateTomorrow = $year . "-" . sprintf( "%02d", $mon ) . "-" . sprintf( "%02d", $mday );
my ( $dbroot, $dbrootpass, $dbname, $dbtable, $dbhost, $dbport, $dbadmin, $dbadminpw, $siteadmin, $siteadminpw, $email, $sitename, $url, $logpath, $retention, $snare, $j4, $arch, $skipcron, $skipdb, $skipsysng, $skiplogrot, $skipsudo, $skipfb, $skiplic, $sphinx_compile, $sphinx_index, $skip_ioncube,$skipapparmor, $syslogng_conf, $webuser, $syslogng_source, $upgrade, $test, $autoyes, $spx_cores );

sub getYN {
    unless ( $autoyes =~ /[Yy]/ ) {
        my ( $prompt, $default ) = @_;
        my $defaultValue = $default ? "[$default]" : "";
        print "$prompt $defaultValue: ";
        chomp( my $input = <STDIN> );
        return $input ? $input : $default;
    } else {
        return "Y";
    }
}

# The command line args below are really just for me so I don't have to keep going through extra steps to test 1 thing.
# But you can use them if you want :-)
foreach my $arg (@ARGV) {
    if($arg eq "update_paths") {
        update_paths();
        exit;
    }
    elsif($arg eq "genconfig") {
        genconfig();
        exit;
    }
    elsif($arg eq "add_logrotate") {
        add_logrotate();
        exit;
    }
    elsif($arg eq "add_syslog_conf") {
        add_syslog_conf();
        exit;
    }
    elsif($arg eq "setup_cron") {
        setup_cron();
        exit;
    }
    elsif($arg eq "setup_sudo") {
        setup_sudo();
        exit;
    }
    elsif($arg eq "setup_apparmor") {
        setup_apparmor();
        exit;
    }
    elsif($arg eq "install_sphinx") {
        install_sphinx();
        exit;
    }
    elsif($arg eq "install_license") {
        install_license();
        exit;
    }
    elsif($arg eq "install_ioncube") {
        add_ioncube();
        exit;
    }
    elsif($arg eq "test") {
        run_tests();
        exit;
    }
    elsif($arg eq "insert_test") {
        insert_test();
        exit;
    }
}

my $rcfile = ".lzrc";
if ( -e $rcfile ) {
    open CONFIG, "$rcfile";
    my $config = join "", <CONFIG>;
    close CONFIG;
    eval $config;
    die "Couldn't interpret the configuration file ($rcfile) that was given.\nError details follow: $@\n" if $@;
}
print("\n\033[1m\n\n========================================\033[0m\n");
print("\n\033[1m\tLogZilla End User License\n\033[0m");
print("\n\033[1m========================================\n\n\033[0m\n\n");

# Display the end-user license agreement
if ( $skiplic =~ /[Yy]/ ) {
    print "You've agreed to the license using the .lzrc method, skipping...\n";
} else {
    #my $pager = $ENV{PAGER} || 'less' || 'more';
    #system( $pager, "$lzbase/scripts/EULA.txt" ) == 0 or die "$pager call failed: $?";
    &EULA;
}

print("\n\033[1m\n\n========================================\033[0m\n");
print("\n\033[1m\tInstallation\n\033[0m");
print("\n\033[1m========================================\n\n\033[0m\n\n");

unless ( -e $rcfile ) {
    $dbroot     = &prompt( "Enter the MySQL root username",      "root" );
    $dbrootpass = &prompt( "Enter the password for $dbroot",     "mysql" );
    $dbname     = &prompt( "Database to install to",             "syslog" );
    $dbhost     = &prompt( "Enter the name of the MySQL server", "localhost" );
    $dbport     = &prompt( "Enter the port of the MySQL server", "3306" );
    $dbadmin = &prompt( "Enter the name to create as the owner of the $dbname database", "syslogadmin" );
    $dbadminpw = &prompt( "Enter the password for the $dbadmin user", "$dbadmin" );
    $siteadmin = &prompt( "Enter the name to create as the WEBSITE owner", "admin" );
    $siteadminpw = &prompt( "Enter the password for $siteadmin", "$siteadmin" );
    $email = &prompt( "Enter your email address", 'root@localhost' );
    $sitename = &prompt( "Enter a name for your website", 'The home of LogZilla' );
    $url = &prompt( "Enter the base url for your site (e.g: / or /logs/)", '/' );
    $logpath = &prompt( "Where should log files be stored?", '/var/log/logzilla' );
    $retention = &prompt( "How long before I archive old logs? (in days)", '7' );
    $snare = &getYN( "Do you plan to log Windows events from SNARE to this server?", 'n' );
    #$spx_cores = &prompt( "How many cores do you want to use for indexing", '8' );
}
$dbtable     = "logs";
$dbroot      = qq{$dbroot};
$dbrootpass  = qq{$dbrootpass};
$dbadmin     = qq{$dbadmin};
$dbadminpw   = qq{$dbadminpw};
$siteadmin   = qq{$siteadmin};
$siteadminpw = qq{$siteadminpw};
$url         = $url . "/" if ( $url !~ /\/$/ );
$url         = "/" . $url if ( $url !~ /^\// );

use IO::Socket::INET;

my $sock = IO::Socket::INET->new(
    PeerAddr => "$dbhost",
    PeerPort => $dbport,
    Proto    => "tcp" );
my $localip = $sock->sockhost;

if ( $dbhost !~ /localhost|127.0.0.1/ ) {
    my $file = "$lzbase/scripts/log_processor";
    system("perl -i -pe 's/LOAD DATA INFILE/LOAD DATA LOCAL INFILE/g' $file");
}

if ( !-d "$logpath" ) {
    mkdir "$logpath";
}
if ( !-d "$lzbase/data" ) {
    mkdir "$lzbase/data";
}

# Create mysql .cnf file
open( CNF, ">$lzbase/scripts/sql/lzmy.cnf" ) || die("Cannot Open $lzbase/scripts/sql/lzmy.cnf: $!");
print CNF "[logzilla]\n";
print CNF "user = $dbadmin\n";
print CNF "password = $dbadminpw\n";
print CNF "host = $dbhost\n";
print CNF "port = $dbport\n";
print CNF "database = $dbname\n";

close(CNF);
chmod 0400, "$lzbase/scripts/sql/lzmy.cnf";

update_paths();
make_logfiles();
genconfig();


if ( $skipdb !~ /[Yy]/ ) {
    print "All data will be installed into the $dbname database\n";
    my $ok = &getYN( "Ok to continue?", "y" );
    if ( $ok =~ /[Yy]/ ) {
        my $dbh = DBI->connect( "DBI:mysql:mysql:$dbhost:$dbport", $dbroot, $dbrootpass );
        my $sth = $dbh->prepare("SELECT version()") or die "Could not get MySQL version: $DBI::errstr";
        $sth->execute;
        while ( my @data = $sth->fetchrow_array() ) {
            my $ver = $data[0];
            if ( $ver !~ /5\.[1-9]/ ) {
                print("\n\033[1m\tERROR!\n\033[0m");
                print "LogZilla requires MySQL v5.1 or better.\n";
                print "Your version is $ver\n";
                print "Please upgrade MySQL to v5.1 or better and re-run this installation.\n";
                exit;
            }
        }
        if ( db_exists() eq 0 ) {
            $dbh->do("create database $dbname");
            do_install();
        } else {
            $upgrade='yes';
            print("\n\033[1m\tPrevious installation detected!\n\033[0m");
            print "Install can attempt an upgrade, but be aware of the following:\n";
            print "1. The upgrade process could potentially take a VERY long time on very large databases.\n";
            print "2. There is a potential for data loss, so please make sure you have backed up your database before proceeding.\n";
            my $ok = &getYN( "Ok to continue?", "y" );
            if ( $ok =~ /[Yy]/ ) {
                &rm_config_block("/etc/apparmor.d/usr.sbin.mysqld");
                &rm_config_block("/etc/syslog-ng/syslog-ng.conf");
                &rm_config_block("/etc/sudoers");
                &rm_config_block("/etc/php5/apache2/php.ini");
                my ( $major, $minor, $sub ) = getVer();
                print "Your Version: $major.$minor.$sub\n";
                print "New Version: $version" . "$subversion\n";
                my $t = $subversion;
                $t =~ s/\.(\d+)/$1/;

                if ( $sub =~ $t ) {
                    print "DB is already at the lastest revision, no need to upgrade.\n";
                } else {
                    # print "VERSION = $major $minor $sub\n";
                    #    exit;
                    if ( "$minor" eq 0 ) {
                        do_upgrade("0");
                    } elsif ( "$minor$sub" eq 1122 ) {
                        do_upgrade("1122");
                    } elsif ( "$major$minor" eq 299 ) {
                        do_upgrade("php-syslog-ng");
                    } elsif ( "$major$minor" eq 32 ) {
                        do_upgrade("32");
                    } else {
                        do_upgrade("all");
                    }
                }
                verify_columns();
            }
        }
    }
    do_procs();
    update_version();
}
add_logrotate()   unless $skiplogrot =~ /[Yy]/;
add_syslog_conf() unless $skipsysng  =~ /[Yy]/;
setup_cron()      unless $skipcron   =~ /[Yy]/;
setup_sudo()      unless $skipsudo   =~ /[Yy]/;
setup_apparmor()  unless $skipapparmor   =~ /[Yy]/;
install_sphinx()  unless $sphinx_compile   =~ /[Nn]/;
insert_test();
if ($sphinx_index   =~ /[Yy]/) {
    print "Starting Sphinx search daemon and re-indexing data...\n";
    system("(rm -f $lzbase/sphinx/data/* && cd $lzbase/sphinx && ./indexer.sh full)");
}
fbutton()         unless $skipfb       =~ /[Yy]/;
add_ioncube()     unless $skip_ioncube =~ /[Yy]/;
install_license() unless $skiplic      =~ /[Yy]/;
# run_tests()       unless $test    =~ /[Nn]/;

setup_rclocal();
hup_syslog();

sub make_archive_tables {
    my $i = 0; 
    my $j = 0;
    my $dbh = db_connect( $dbname, $lzbase, $dbroot, $dbrootpass );
    if ( !$dbh ) {
        print "Can't connect to $dbname database: ", $DBI::errstr, "\n";
        exit;
    }

# Insert archives table
#[[ticket:315]]
# Can't overwrite current archives on upgrade. We'll copy the existing table to old, then replace into new table.
    if ( tblExists("archives") eq 1 ) {
        copy_old_archives();
    } else {
        my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/archives.sql`;
    }

    # TH: seed the hourly views with the no record
    # Hourly
    for ( $i = 0 ; $i <= 23 ; $i++ ) {
        $dbh->do( "CREATE OR REPLACE VIEW log_arch_hr_$i AS SELECT * FROM $dbtable where id>2 and id<1;
            " ) or die "Could not create log_arch_hr_$i: $DBI::errstr";
    }
    
    # TH: seed the quad-hourly views with the no record
    # quad-Hourly
    for ( $i = 0 ; $i <= 3 ; $i++ ) {
    	$j = $i*15;
        $dbh->do( "CREATE OR REPLACE VIEW log_arch_qrhr_$j AS SELECT * FROM $dbtable where id>2 and id<1;
            " ) or die "Could not create log_arch_hr_$i: $DBI::errstr";
    }

}

sub do_install {
    my $dbh = db_connect( $dbname, $lzbase, $dbroot, $dbrootpass );
    if ( !$dbh ) {
        print "Can't connect to $dbname database: ", $DBI::errstr, "\n";
        exit;
    }

    # Create main table
    $dbh->do( "
        CREATE TABLE $dbtable (
        id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        host varchar(128) NOT NULL,
        facility enum('0','1','2','3','4','5','6','7','8','9','10','11','12','13','14','15','16','17','18','19','20','21','22','23') NOT NULL,
        severity enum('0','1','2','3','4','5','6','7') NOT NULL,
        program int(10) unsigned NOT NULL,
        msg varchar(2048) NOT NULL,
        mne int(10) unsigned NOT NULL,
        eid int(10) unsigned NOT NULL DEFAULT '0',
        suppress datetime NOT NULL DEFAULT '2010-03-01 00:00:00',
        counter int(11) NOT NULL DEFAULT '1',
        fo datetime NOT NULL,
        lo datetime NOT NULL,
        notes varchar(255) NOT NULL DEFAULT '',
        PRIMARY KEY (id,fo),
        KEY lo (lo),
        KEY `fo` (`fo`) USING BTREE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 
        " ) or die "Could not create $dbtable table: $DBI::errstr";

    # Create sphinx table
    if ( $upgrade !~ /[Yy][Ee][Ss]/ ) {
        my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/sph_counter.sql`;
        print $res;
    }

    # Create cache table
    my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/cache.sql`;
    print $res;

    # Create hosts table
    my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/hosts.sql`;
    print $res;

    # Create mnemonics table
    my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/mne.sql`;
    print $res;

    # Create snare_eid table
    create_snare_table();

    # Create programs table
    do_programs();

    # Create suppress table
    my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/suppress.sql`;
    print $res;

    # Create facilities table
    my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/facilities.sql`;
    print $res;

    # Create severities table
    my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/severities.sql`;
    print $res;

    # Create ban table
    my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/banned_ips.sql`;
    print $res;

    # Create epx tables
    `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/epx.sql` if ( colExists( "events_per_second", "name" ) eq 0 );

    # Create email alerts table
    do_email_alerts();

    # Groups
    my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/groups.sql`;
    print $res;

    # Insert totd data
    my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/totd.sql`;
    print $res;

    # Insert LZECS data
    my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/lzecs.sql`;
    print $res;

    # Insert Suppress data
    my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/suppress.sql`;
    print $res;

    # Insert ui_layout data
    if ( tblExists("ui_layout") eq 1 ) {
        upgrade_ui_layout();
    } else {
        my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/ui_layout.sql`;
    }

    # Insert help data
    my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/help.sql`;
    print $res;

    # Insert history table
    my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/history.sql`;
    print $res;

    # Insert users table
    my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/users.sql`;
    print $res;

    # Insert system_log table
    my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/system_log.sql`;
    print $res;

    # Insert rbac table
    if ( tblExists("rbac") eq 1 ) {
        copy_old_rbac();
    } else {
        my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/rbac.sql`;
    }

  # Insert view_limits table
    if ( tblExists("view_limits") eq 1 ) {
        copy_old_view_limits();
    } else {
        my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/view_limits.sql`;
    }

    make_partitions();
    create_views();
    make_dbuser();
    add_table_triggers();
    make_archive_tables();
    update_settings();
}

sub update_paths {
    my $search = "/path_to" . "_logzilla";
    print "Updating file paths\n";
    my @flist = `find ../ -name '*.sh' -o -name '*.pl' -o -name '*.conf' -o -name '*.rc' -o -name 'logzilla.*' -type f | egrep -v '/install.pl|sphinx\/src|\\.svn|\\.lzrc' | xargs grep -l "$search"`;

    #print "@flist\n";
    foreach my $file (@flist) {
        chomp $file;
        print "Modifying $file\n";
        system "perl -i -pe 's|$search|$lzbase|g' $file" and warn "Could not modify $file $!\n";
    }
    my $search = "/path_to" . "_logs";
    print "Updating log paths\n";
    my @flist = `find ../ -name '*.sh' -o -name '*.pl' -o -name '*.conf' -o -name '*.rc' -o -name 'logzilla.*' -type f | egrep -v '/install.pl|sphinx\/src|\\.svn|\\.lzrc' | xargs grep -l "$search"`;

    #print "@flist\n";
    foreach my $file (@flist) {
        chomp $file;
        print "Modifying $file\n";
        system "perl -i -pe 's|$search|$logpath|g' $file" and warn "Could not modify $file $!\n";
    }
}

sub make_logfiles {

    #Create log files for later use by the server
    my $logfile = "$logpath/logzilla.log";
    open( LOG, ">>$logfile" );
    if ( !-f $logfile ) {
        print STDOUT "Unable to open log file \"$logfile\" for writing...$!\n";
        exit;
    }
    chmod 0666, "$logpath/logzilla.log";
    close(LOG);
    my $logfile = "$logpath/mysql_query.log";
    open( LOG, ">>$logfile" );
    if ( !-f $logfile ) {
        print STDOUT "Unable to open log file \"$logfile\" for writing...$!\n";
        exit;
    }
    close(LOG);
    chmod 0666, "$logpath/mysql_query.log";
    my $logfile = "$logpath/audit.log";
    open( LOG, ">>$logfile" );
    if ( !-f $logfile ) {
        print STDOUT "Unable to open log file \"$logfile\" for writing...$!\n";
        exit;
    }
    close(LOG);
    chmod 0666, "$logpath/audit.log";
}

sub genconfig {
    print "Generating $lzbase/html/config/config.php\n";
    my $config = qq{<?php
    DEFINE('DBADMIN', '$dbadmin');
    DEFINE('DBADMINPW', '$dbadminpw');
    DEFINE('DBNAME', '$dbname');
    DEFINE('DBHOST', '$dbhost');
    DEFINE('DBPORT', '$dbport');
    DEFINE('LOG_PATH', '$logpath');
    DEFINE('DATA_DIR', '$lzbase/data');
    DEFINE('MYSQL_QUERY_LOG', '$logpath/mysql_query.log');
    DEFINE('PATHTOLOGZILLA', '$lzbase');
    DEFINE('SPHINXHOST', '127.0.0.1'); // NOT 'localhost'! Else it will connect to local socket instead
    DEFINE('SPHINXPORT', '9306');
    DEFINE('SPHINXAPIPORT', '3312');
# Enabling query logging will degrade performance.
DEFINE('LOG_QUERIES', 'FALSE');
};
    my $file = "$lzbase/html/config/config.php";
    open( CNF, ">$file" ) || die("Cannot Open $file: $!");
    print CNF "$config";
    my $rfile = "$lzbase/scripts/sql/regexp.txt";
    open( FILE, $rfile ) || die("Cannot Open file: $!");
    my @data = <FILE>;

    foreach my $line (@data) {
        print CNF "$line";
    }
    print CNF "?>\n";
    close(CNF);
    close(FILE);
}

sub make_partitions {


    # Get some date values in order to create the MySQL Partition
    my $dbh = db_connect( $dbname, $lzbase, $dbroot, $dbrootpass );

    # Import procedures
    system "perl -i -pe 's| logs | $dbtable |g' sql/procedures.sql" and warn "Could not modify sql/procedures.sql $!\n";
    my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/procedures.sql`;
    print $res;
    # Create initial Partition of the $dbtable table
    $dbh->do( "CALL manage_logs_partitions();" )
        or die "Could not create partition for the $dbtable table: $DBI::errstr";

}

sub do_events {
    my $dbh = db_connect( $dbname, $lzbase, $dbroot, $dbrootpass );
    print "Creating MySQL Events...\n";

    # Drop events and recreate them whether this is a new install or an upgrade.
    $dbh->do( "
        DROP EVENT IF EXISTS `cacheEid`;
        " ) or die "$DBI::errstr";
    $dbh->do( "
        DROP EVENT IF EXISTS `cacheHosts`;
        " ) or die "$DBI::errstr";
    $dbh->do( "
        DROP EVENT IF EXISTS `cacheMne`;
        " ) or die "$DBI::errstr";
    $dbh->do( "
        DROP EVENT IF EXISTS `log_arch_daily_event`;
        " ) or die "$DBI::errstr";
    $dbh->do( "
        DROP EVENT IF EXISTS `log_arch_hr_event`;
        " ) or die "$DBI::errstr";
    $dbh->do( "
        DROP EVENT IF EXISTS `logs_add_partition`;
        " ) or die "$DBI::errstr";
    $dbh->do( "
        DROP EVENT IF EXISTS `logs_del_partition`;
        " ) or die "$DBI::errstr";
    $dbh->do( "
        DROP EVENT IF EXISTS `log_arch_qrhr_event`;
        " ) or die "$DBI::errstr";
    # ticket #412 : As of v4.25, all cleanup and updateCache procedures moved from DB to Perl to speed up the processes.
    $dbh->do( "
        DROP EVENT IF EXISTS `updateCache`;
        " ) or die "$DBI::errstr";
    $dbh->do( "
        DROP EVENT IF EXISTS `cleanup`;
        " ) or die "$DBI::errstr";

    # Create Partition events
    my $event = qq{
    CREATE EVENT logs_add_partition ON SCHEDULE EVERY 1 DAY STARTS '$dateTomorrow 00:00:00' ON
    COMPLETION NOT PRESERVE ENABLE DO CALL manage_logs_partitions();
    };
    my $sth = $dbh->prepare( "
        $event
        " ) or die "Could not create partition events: $DBI::errstr";
    $sth->execute;

    my $event = qq{
    CREATE EVENT logs_del_partition ON SCHEDULE EVERY 1 DAY STARTS '$dateTomorrow 00:15:00' ON COMPLETION NOT PRESERVE ENABLE DO CALL logs_delete_part_proc();
    };
    my $sth = $dbh->prepare( "
        $event
        " ) or die "Could not create partition events: $DBI::errstr";
    $sth->execute;

    $dbh->do( "
        CREATE EVENT `log_arch_daily_event` ON SCHEDULE EVERY 1 DAY STARTS date_add(date_add(date(now()), interval 1 day),interval 270 second) ON COMPLETION NOT PRESERVE ENABLE DO call log_arch_daily_proc();
        " ) or die "$DBI::errstr";
    $dbh->do( "
        CREATE EVENT `log_arch_hr_event` ON SCHEDULE EVERY 1 HOUR STARTS date_add(date(now()),interval maketime(date_format(now(),'%H')+1,4,40) hour_second) ON COMPLETION PRESERVE ENABLE DO call log_arch_hr_proc();
        " ) or die "$DBI::errstr";
    $dbh->do( "
        CREATE EVENT `log_arch_qrhr_event` ON SCHEDULE EVERY 15 MINUTE STARTS date_add(date(now()),interval maketime(date_format(now(),'%H'),4,15) hour_second) ON COMPLETION PRESERVE ENABLE DO call log_arch_qrthr_proc();
        " ) or die "$DBI::errstr";
}

sub do_procs {
    print "Verifying MySQL Procedures\n";
    my $dbh = db_connect( $dbname, $lzbase, $dbroot, $dbrootpass );

    # Drop procs and recreate them whether this is a new install or an upgrade.
    $dbh->do( "
    DROP PROCEDURE IF EXISTS updateHosts;
        " ) or die "$DBI::errstr";
    $dbh->do( "
    DROP PROCEDURE IF EXISTS updateMne;
        " ) or die "$DBI::errstr";
    $dbh->do( "
    DROP PROCEDURE IF EXISTS updateEid;
        " ) or die "$DBI::errstr";
    $dbh->do( "
    DROP PROCEDURE IF EXISTS export;
        " ) or die "$DBI::errstr";
    $dbh->do( "
    DROP PROCEDURE IF EXISTS import;
        " ) or die "$DBI::errstr";
    $dbh->do( "
    DROP PROCEDURE IF EXISTS cleanup;
        " ) or die "$DBI::errstr";
    $dbh->do( "
    DROP PROCEDURE IF EXISTS log_arch_daily_proc;
        " ) or die "$DBI::errstr";
    $dbh->do( "
    DROP PROCEDURE IF EXISTS log_arch_hr_proc;
        " ) or die "$DBI::errstr";
    $dbh->do( "
    DROP PROCEDURE IF EXISTS log_arch_qrthr_proc;
        " ) or die "$DBI::errstr";
    $dbh->do( "
    DROP PROCEDURE IF EXISTS logs_add_archive_proc;
        " ) or die "$DBI::errstr";
    $dbh->do( "
    DROP PROCEDURE IF EXISTS logs_add_part_proc;
        " ) or die "$DBI::errstr";
    $dbh->do( "
    DROP PROCEDURE IF EXISTS logs_delete_part_proc;
        " ) or die "$DBI::errstr";

    my $event = qq{
    CREATE PROCEDURE logs_add_part_proc()
    SQL SECURITY DEFINER
    COMMENT 'Creates partitions for tomorrow' 
    BEGIN    
    DECLARE new_partition CHAR(32) DEFAULT
    CONCAT ('p', DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 1 DAY), '%Y%m%d'));
    DECLARE max_day INTEGER DEFAULT TO_DAYS(NOW()) +1;
    SET \@s =
    CONCAT('ALTER TABLE `$dbtable` ADD PARTITION (PARTITION ', new_partition,
    ' VALUES LESS THAN (', max_day, '))');
    PREPARE stmt FROM \@s;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
    END 
    };
    my $sth = $dbh->prepare( "
        $event
        " ) or die "Could not create partition events: $DBI::errstr";
    $sth->execute;

    my $event = qq{
    CREATE PROCEDURE logs_delete_part_proc()
    SQL SECURITY DEFINER
    COMMENT 'Deletes old partitions - based on value of settings>retention' 
    BEGIN    
    select REPLACE(concat('drop view log_arch_day_',DATE_SUB(CURDATE(), INTERVAL (SELECT value from settings WHERE name='RETENTION') DAY)), '-','') into \@v;
    SELECT CONCAT( 'ALTER TABLE `logs` DROP PARTITION ',
    GROUP_CONCAT(`partition_name`))
    INTO \@s

    FROM INFORMATION_SCHEMA.partitions
    WHERE table_schema = '$dbname'
    AND table_name = '$dbtable'
    AND partition_description <
    TO_DAYS(DATE_SUB(CURDATE(), INTERVAL (SELECT value from settings WHERE name='RETENTION') DAY))
    GROUP BY TABLE_NAME;

    IF \@s IS NOT NULL then
    PREPARE stmt FROM \@s;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
    PREPARE stmt FROM \@v;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
    END IF;
    select min(id) from logs into \@h; 
    update sph_counter set max_id=\@h-1 where counter_id=2;
    END 
    };
    my $sth = $dbh->prepare( "
        $event
        " ) or die "Could not create partition events: $DBI::errstr";
    $sth->execute;

    my $event = qq{
    CREATE PROCEDURE export()
    SQL SECURITY DEFINER
    COMMENT 'Acrhive all old data to a file'
    BEGIN
    DECLARE export CHAR(32) DEFAULT CONCAT ('dumpfile_',  DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 day), '%Y%m%d'),'.txt');
    DECLARE export_path CHAR(127);
    SELECT value INTO export_path FROM settings WHERE name="ARCHIVE_PATH";
    SET \@s =
    CONCAT('select log.id, log.host, log.facility, log.severity, prg.name as program, log.msg, mne.name as mne, log.eid, log.suppress, log.counter, log.fo, log.lo, log.notes  into outfile "',export_path, '/' , export,'"  FIELDS TERMINATED BY "," OPTIONALLY ENCLOSED BY """" LINES TERMINATED BY "\n" from  `$dbtable` as log, `programs` as prg, `mne` as mne where  prg.crc=log.program and mne.crc=log.mne and TO_DAYS( log.lo )=',TO_DAYS(NOW())-1);
    PREPARE stmt FROM \@s;
    EXECUTE stmt;
    DEALLOCATE PREPARE stmt;
    INSERT IGNORE INTO archives (archive, records) VALUES (export,(SELECT COUNT(*) FROM `$dbtable` WHERE lo BETWEEN DATE_SUB(CONCAT(CURDATE(), ' 00:00:00'), INTERVAL 1 DAY) AND DATE_SUB(CONCAT(CURDATE(), ' 23:59:59'), INTERVAL  1 DAY))); END 
    };

    my $sth = $dbh->prepare( "
        $event
        " ) or die "Could not create export Procedure: $DBI::errstr";
    $sth->execute;

    # TH: adding import procedure
    my $event = qq{
    CREATE PROCEDURE `import`( `i_id` bigint(20) unsigned ,
    `i_host` varchar(128),
    `i_facility` int(2) unsigned,
    `i_severity` int(2) unsigned,
    `i_program` varchar(255),
    `i_msg` varchar(2048),
    `i_mne` varchar(255),
    `i_eid` int(10) unsigned,
    `i_suppress` datetime ,
    `i_counter` int(11),
    `i_fo` datetime,
    `i_lo` datetime,
    `i_notes` varchar(255))
    SQL SECURITY DEFINER
    COMMENT 'Import Data from archive'
    BEGIN
    INSERT INTO mne(name,crc,lastseen) VALUES (i_mne,crc32(i_mne),i_lo) ON DUPLICATE KEY UPDATE lastseen=GREATEST(i_lo,lastseen), hidden='false';
    INSERT INTO programs(name,crc,lastseen) VALUES (i_program,crc32(i_program),i_lo) ON DUPLICATE KEY UPDATE lastseen=GREATEST(i_lo,lastseen), hidden='false';
    INSERT IGNORE INTO `$dbtable`(id, host, facility, severity, program, msg, mne, eid, suppress, counter, fo, lo, notes)
    values (i_id, i_host, i_facility, i_severity, crc32(i_program), i_msg, crc32(i_mne), i_eid, i_suppress, i_counter,
    i_fo, i_lo, i_notes); 
    END
    };
    my $sth = $dbh->prepare( "
        $event
        " ) or die "Could not create import Procedure: $DBI::errstr";
    $sth->execute;

    # Turn the event scheduler on

    my $sth = $dbh->prepare( "
        SET GLOBAL event_scheduler = 1;
        " ) or die "Could not enable the Global event scheduler: $DBI::errstr";
    $sth->execute;

    #    $dbh->do("
    #        DROP PROCEDURE IF EXISTS `log_arch_mnthly_proc`;
    #        ") or die "$DBI::errstr";
    #    $dbh->do("
    #        DROP PROCEDURE IF EXISTS `log_arch_weekly_proc`;
    #        ") or die "$DBI::errstr";

    # Now create the events that trigger these procs
    do_events();
    # cdukes: added below after v4.25 because we moved some procedures to file but they were getting deleted above
    # this will all get cleaned up when Piotr writes the new install :-)
    system "perl -i -pe 's| logs | $dbtable |g' sql/procedures.sql" and warn "Could not modify sql/procedures.sql $!\n";
    my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/procedures.sql`;
    print $res;
}

sub make_dbuser {

    # DB User
    # Remove old user in case this is an upgrade
    # Have to do this for the new LOAD DATA INFILE
    print "Temporarily removing $dbadmin from $localip\n";
    my $dbh   = db_connect( $dbname, $lzbase, $dbroot, $dbrootpass );
    my $grant = qq{GRANT USAGE ON *.* TO '$dbadmin'\@'$localip';};
    my $sth   = $dbh->prepare( "
        $grant
        " ) or die "Could not temporarily drop the $dbadmin user on $dbname: $DBI::errstr";
    $sth->execute;
    my $grant = qq{DROP USER '$dbadmin'\@'$localip';};
    my $sth   = $dbh->prepare( "
        $grant
        " ) or die "Could not temporarily drop the $dbadmin user on $dbname: $DBI::errstr";
    $sth->execute;

    print "Adding $dbadmin to $localip\n";

    # Grant access to $dbadmin
    my $grant = qq{GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, GRANT OPTION, REFERENCES, INDEX, ALTER, CREATE TEMPORARY TABLES, LOCK TABLES, CREATE VIEW, SHOW VIEW, CREATE ROUTINE, ALTER ROUTINE, EXECUTE, EVENT, TRIGGER ON `$dbname`.* TO '$dbadmin'\@'$localip'  IDENTIFIED BY '$dbadminpw'};

#my $grant = qq{GRANT ALL PRIVILEGES ON `$dbname.*` TO '$dbadmin'\@'$localip' IDENTIFIED BY '$dbadminpw';};
    my $sth = $dbh->prepare( "
        $grant
        " ) or die "Could not create $dbadmin user on $dbname: $DBI::errstr";
    $sth->execute;

    # CDUKES: [[ticket:16]]
    my $grant = qq{GRANT FILE ON *.* TO '$dbadmin'\@'$localip' IDENTIFIED BY '$dbadminpw';};
    my $sth = $dbh->prepare( "
        $grant
        " ) or die "Could not create $dbadmin user on $dbname: $DBI::errstr";
    $sth->execute;

    # Repeat for localhost
    # Remove old user in case this is an upgrade
    # Have to do this for the new LOAD DATA INFILE
    print "Temporarily removing $dbadmin from localhost\n";
    my $grant = qq{GRANT USAGE ON *.* TO '$dbadmin'\@'localhost';};
    my $sth   = $dbh->prepare( "
        $grant
        " ) or die "Could not temporarily drop the $dbadmin user on $dbname: $DBI::errstr";
    $sth->execute;
    my $grant = qq{DROP USER '$dbadmin'\@'localhost';};
    my $sth   = $dbh->prepare( "
        $grant
        " ) or die "Could not temporarily drop the $dbadmin user on $dbname: $DBI::errstr";
    $sth->execute;

    # Grant access to $dbadmin
    print "Adding $dbadmin to localhost\n";
    my $grant = qq{GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, GRANT OPTION, REFERENCES, INDEX, ALTER, CREATE TEMPORARY TABLES, LOCK TABLES, CREATE VIEW, SHOW VIEW, CREATE ROUTINE, ALTER ROUTINE, EXECUTE, EVENT, TRIGGER ON `$dbname`.* TO '$dbadmin'\@'localhost'  IDENTIFIED BY '$dbadminpw'};

#my $grant = qq{GRANT ALL PRIVILEGES ON `$dbname.*` TO '$dbadmin'\@'localhost' IDENTIFIED BY '$dbadminpw';};
    my $sth = $dbh->prepare( "
        $grant
        " ) or die "Could not create $dbadmin user on $dbname: $DBI::errstr";
    $sth->execute;

    # CDUKES: [[ticket:16]]
    my $grant = qq{GRANT FILE ON *.* TO '$dbadmin'\@'localhost' IDENTIFIED BY '$dbadminpw';};
    my $sth = $dbh->prepare( "
        $grant
        " ) or die "Could not create $dbadmin user on $dbname: $DBI::errstr";
    $sth->execute;

    # THOMAS HONZIK: [[ticket:16]]
    my $flush = qq{FLUSH PRIVILEGES;};
    my $sth   = $dbh->prepare( "
        $flush
        " ) or die "Could not FLUSH PRIVILEGES: $DBI::errstr";
    $sth->execute;

}

sub create_views {
    my $dbh = db_connect( $dbname, $lzbase, $dbroot, $dbrootpass );
    my $sth = $dbh->prepare( "
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
        " ) or die "Could not create $dbtable table: $DBI::errstr";
    $sth->execute;

    my $sth = $dbh->prepare( "
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
        " ) or die "Could not create $dbtable table: $DBI::errstr";
    $sth->execute;
}

sub update_settings {

    my $dbh = db_connect( $dbname, $lzbase, $dbroot, $dbrootpass );

    # Insert settings data
    # use copy_old settings so upgraders don't get overwritten
    if ( tblExists("settings") eq 1 ) {
        copy_old_settings();
    } else {
        my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/settings.sql`;
    }
    my $sth = $dbh->prepare( "
        update settings set value='$url' where name='SITE_URL';
        " ) or die "Could not update settings table: $DBI::errstr";
    $sth->execute;
    my $sth = $dbh->prepare( "
        update settings set value='$email' where name='ADMIN_EMAIL';
        " ) or die "Could not update settings table: $DBI::errstr";
    $sth->execute;
    my $sth = $dbh->prepare( "
        update settings set value='$siteadmin' where name='ADMIN_NAME';
        " ) or die "Could not update settings table: $DBI::errstr";
    $sth->execute;
    my $sth = $dbh->prepare( "
        update settings set value='$lzbase' where name='PATH_BASE';
        " ) or die "Could not update settings table: $DBI::errstr";
    $sth->execute;
    my $sth = $dbh->prepare( "
        update settings set value='$sitename' where name='SITE_NAME';
        " ) or die "Could not update settings table: $DBI::errstr";
    $sth->execute;
    my $sth = $dbh->prepare( "
        update settings set value='$dbtable' where name='TBL_MAIN';
        " ) or die "Could not update settings table: $DBI::errstr";
    $sth->execute;
    my $sth = $dbh->prepare( "
        update settings set value='$logpath' where name='PATH_LOGS';
        " ) or die "Could not update settings table: $DBI::errstr";
    $sth->execute;
    my $sth = $dbh->prepare( "
        update settings set value='$retention' where name='RETENTION';
        " ) or die "Could not update settings table: $DBI::errstr";
    $sth->execute;
    if (not $spx_cores) {
        $spx_cores = `cat /proc/cpuinfo | grep processor | wc -l`;
    }
    #$spx_cores = 8 if ($spx_cores > 8);
    my $sth = $dbh->prepare( "
        update settings set value='$spx_cores' where name='SPX_CPU_CORES';
        " ) or die "Could not update settings table: $DBI::errstr";
    $sth->execute;
        if ( $snare =~ /[Yy]/ ) {
        my $sth = $dbh->prepare( "
            update settings set value=1 where name='SNARE';
            " ) or die "Could not update settings table: $DBI::errstr";
        $sth->execute;
    } else {
        my $sth = $dbh->prepare( "
            delete from ui_layout where header='Snare EventId' and userid>0;
            " ) or die "Could not update ui layout for snare: $DBI::errstr";
        $sth->execute;
        my $sth = $dbh->prepare( "
            update settings set value=0 where name='SNARE';
            " ) or die "Could not update settings table: $DBI::errstr";
        $sth->execute;
    }
    my $sth = $dbh->prepare( "
        update triggers set mailto='$email', mailfrom='$email';
        " ) or die "Could not update triggers table: $DBI::errstr";
    $sth->execute;
    my $sth = $dbh->prepare( "
        update users set username='$siteadmin' where username='admin';
        " ) or die "Could not insert user data: $DBI::errstr";
    $sth->execute;
    my $sth = $dbh->prepare( "
        update users set pwhash=MD5('$siteadminpw') where username='$siteadmin';
        " ) or die "Could not insert user data: $DBI::errstr";
    $sth->execute;
    my $sth = $dbh->prepare( "
        delete from users where username='guest';
        " ) or die "Could not insert user data: $DBI::errstr";
    $sth->execute;
    
}

sub add_logrotate {
    if ( -d "/etc/logrotate.d" ) {
        print "\nAdding LogZilla logrotate.d file to /etc/logrotate.d\n";
        my $ok = &getYN( "Ok to continue?", "y" );
        if ( $ok =~ /[Yy]/ ) {
            system("cp contrib/system_configs/logzilla.logrotate /etc/logrotate.d/logzilla");
        } else {
            print "Skipped logrotate.d file, you will need to manually copy:\n";
            print "cp contrib/system_configs/logzilla.logrotate /etc/logrotate.d/logzilla\n";
        }
    } else {
        print("\n\033[1m\tWARNING!\n\033[0m");
        print "Unable to locate your /etc/logrotate.d directory\n";
        print "You will need to manually copy:\n";
        print "cp $lzbase/scripts/contrib/system_configs/logzilla.logrotate /etc/logrotate.d/logzilla\n";
    }
}

# [[ticket:10]] Modifies the exports dir to he correct user
system "chown mysql.mysql ../exports" and warn "Could not modify archive directory";

# [[ticket:300]] chown scripts also
system "chown mysql.mysql $lzbase/scripts/export.sh" and warn "Could not set permission on $lzbase/scripts/export.sh";
system "chown mysql.mysql $lzbase/scripts/import.sh" and warn "Could not set permission on $lzbase/scripts/import.sh";
system "chown mysql.mysql $lzbase/scripts/doimport.sh" and warn "Could not set permission on $lzbase/scripts/doimport.sh";

sub add_syslog_conf {
    print "\n\nAdding LogZilla to syslog-ng\n";
    $syslogng_conf = "/etc/syslog-ng/syslog-ng.conf";
    my $ok = &getYN( "Ok to continue?", "y" );
    if ( $ok =~ /[Yy]/ ) {
        unless ( -e $syslogng_conf ) {
            my $syslogng_conf = &prompt( "Where is your syslog-ng.conf file located?", "/etc/syslog-ng/syslog-ng.conf" );
        }
        if ( -e $syslogng_conf ) {

            # Check to see if entry already exists
            open FILE, "<$syslogng_conf";
            my @lines = <FILE>;
            close FILE;
            if ( grep( /<lzconfig>/, @lines ) ) {
                print "\nLogZilla config already exists in $syslogng_conf, skipping add...\n";
            } else {
                print "Adding syslog-ng configuration to $syslogng_conf\n";

                # Find syslog-ng.conf source definition
                my ( @sources, $syslogng_source );
                open( NGCONFIG, $syslogng_conf );
                my @config = <NGCONFIG>;
                close(NGCONFIG);
                foreach my $var (@config) {
                    next unless $var =~ /^source/;    # Skip non-source def's
                    $syslogng_source = $1 if ( $var =~ /^source (\w+)/ );
                    push( @sources, $syslogng_source );
                }
                my $count = $#sources + 1;
                if ( $count > 1 ) {
                    print "You have more than 1 source defined\n";
                    print "Your source definitions are:\n";
                    foreach my $t (@sources)
                    {
                        print $t . "\n";
                    }
                }
                if ( not $syslogng_source ) {
                    $syslogng_source = &prompt( "Which source definition would you like to use?", "$syslogng_source" );
                }
                system "perl -i -pe 's|MYSOURCE|$syslogng_source|g' contrib/system_configs/syslog-ng.conf" and warn "Could not modify contrib/system_configs/syslog-ng.conf $!\n";
                open( CNF, ">>$syslogng_conf" ) || die("Cannot Open $syslogng_conf: $!");
                open( FILE, "contrib/system_configs/syslog-ng.conf" ) || die("Cannot Open file: $!");
                my @data = <FILE>;
                foreach my $line (@data) {
                    print CNF "$line";
                }
                close(CNF);
                close(FILE);
            }
        } else {
            print "Unable to locate your syslog-ng.conf file\n";
            print "You will need to manually merge contrib/system_configs/syslog-ng.conf with yours.\n";
        }
    } else {
        print "Skipped syslog-ng merge\n";
        print "You will need to manually merge contrib/system_configs/syslog-ng.conf with yours.\n";
    }
}

sub setup_cron {

    # Cronjob  Setup
    print("\n\033[1m\n\n========================================\033[0m\n");
    print("\n\033[1m\tCron Setup\n\033[0m");
    print("\n\033[1m========================================\n\n\033[0m\n");
    print "\n";
    print "Cron is used to run backend indexing and data exports.\n";
    print "Install will attempt to do this automatically for you by adding it to /etc/cron.d\n";
    print "In the event that something fails or you skip this step, \n";
    print "You MUST create it manually or create the entries in your root's crontab file.\n";
    my $crondir;
    my $ok = &getYN( "Ok to continue?", "y" );

    if ( $ok =~ /[Yy]/ ) {
        my $minute;

# due hourly views cron can always run every minute
#        my $sml = &getYN( "\n\nWill this copy of LogZilla be used to process more than 1 Million messages per day?\nNote: Your answer here only determines how often to run indexing.", "n" );
#        if ( $sml =~ /[Yy]/ ) {
#            $minute = 5;
#        } else {
        $minute = 1;

        #        }
        my $cron = qq{
#####################################################
# BEGIN LogZilla Cron Entries
#####################################################
# http://www.logzilla.pro
# Sphinx indexer cron times
# Note: Your setup may require some tweaking depending on expected message rates!
# Install date: $now
#####################################################

#####################################################
# Run Sphinx "delta" scans every 5 minutes throughout 
# the day.  
#####################################################
*/1 * * * * root ( cd $lzbase/sphinx; ./indexer.sh delta ) >> $logpath/sphinx_indexer.log 2>&1

#####################################################
# Daily export archives
#####################################################
# 0 1 * * * root sh $lzbase/scripts/export.sh

#####################################################
# Daily DB/SP Sync
# Run at 12:12 AM
# 1. Runs after DB routines (scheduled @ midnight in the DB scheduler)
# 2. Should complete run prior to next 5 minute index.
#####################################################
12 0 * * * root perl $lzbase/scripts/syncDB >> $logpath/syncDB.log 2>&1

#####################################################
# END LogZilla Cron Entries
#####################################################
};
        $crondir = "/etc/cron.d";
        unless ( -d "$crondir" ) {
            $crondir = &prompt( "What is the correct path to your cron.d?", "/etc/cron.d" );
        }
        if ( -d "$crondir" ) {
            my $file = "$crondir/logzilla";
            open FILE, ">$file" or die "cannot open $file: $!";
            print FILE $cron;
            close FILE;
            print "Cronfile added to $crondir\n";
            hup_crond();
        } else {
            print "$crondir does not exist\n";
            print "You will need to manually copy $lzbase/scripts/contrib/system_configs/logzilla.crontab to /etc/cron.d\n";
            print "or use 'crontab -e' as root and paste the contents of $lzbase/scripts/contrib/system_configs/logzilla.crontab into it.\n";
            print "If you add it manually as root's personal crontab, then be sure to remove the \"root\" username from the last entry.\n";
        }
    } else {
        print "Skipping Crontab setup.\n";
        print "You will need to manually copy $lzbase/scripts/contrib/system_configs/logzilla.crontab to /etc/cron.d\n";
        print "or use 'crontab -e' as root and paste the contents of $lzbase/scripts/contrib/system_configs/logzilla.crontab into it.\n";
        print "If you add it manually as root's personal crontab, then be sure to remove the \"root\" username from the last entry.\n";
    }
}

sub setup_sudo {

    # Sudo Access Setup
    print("\n\033[1m\n\n========================================\033[0m\n");
    print("\n\033[1m\tSUDO Setup\n\033[0m");
    print("\n\033[1m========================================\n\n\033[0m\n\n");
    print "In order for the Apache user to be able to apply changes to syslog-ng, sudo access needs to be provided in /etc/sudoers\n";
    print "Note that you do not HAVE to do this, but it will make things much easier on your for both licensing and Email Alert editing.\n";
    print "If you choose not to install the sudo commands, then you must manually SIGHUP syslog-ng each time an Email Alert is added, changed or removed.\n";
    my $ok = &getYN( "Ok to continue?", "y" );
    if ( $ok =~ /[Yy]/ ) {
        my $file = "/etc/sudoers";
        unless ( -e $file ) {
            $file = &prompt( "Please provide the location of your sudoers file", "/etc/sudoers" );
        }
        if ( -e "$file" ) {

            # Try to get current web user
            my $PROGRAM = qr/apache|httpd/;
            my @ps      = `ps axu`;
            @ps = map { m/^(\S+)/; $1 } grep { /$PROGRAM/ } @ps;
            my $webuser = $ps[$#ps];
            if ( not $webuser ) {
                my $webuser = &prompt( "Please provide the username that Apache runs as", "$webuser" );
            }

# since we have $webuser here, let's go ahead and chown the files needed for licensing
            system "chown $webuser.$webuser $lzbase/html/includes/ajax/license.log" and warn "Could not chown license.log";
            system "chown $webuser.$webuser $lzbase/html/" and warn "Could not chown html/";

            # Check to see if entry already exists
            open SFILE, "<$file";
            my @lines = <SFILE>;
            close SFILE;
            if ( grep( /<lzconfig>/, @lines ) ) {
                print "Config entry already exists in $file, skipping add...\n";
            } else {
                my $os = `uname -a`;
                $os =~ s/.*(ubuntu).*/$1/i;
                my $now = localtime;
                open( SFILE, ">>$file" ) || die("Cannot Open $syslogng_conf: $!");
                my @data = <FILE>;
                foreach my $line (@data) {
                    chomp $line;
                    print SFILE "$line";
                }
                print SFILE "\n";
                print SFILE "# <lzconfig> BEGIN: Added by LogZilla installation on $now\n";
                print SFILE "# Allows Apache user to HUP the syslog-ng process\n";
                print SFILE "$webuser ALL=NOPASSWD:$lzbase/scripts/hup.pl\n";
                print SFILE "# Allows Apache user to apply new licenses from the web interface\n";
                print SFILE "$webuser ALL=NOPASSWD:$lzbase/scripts/licadd.pl\n";
                print SFILE "# Allows Apache user to import data from archive\n";
                print SFILE "$webuser ALL=NOPASSWD:$lzbase/scripts/doimport.sh\n";
                print SFILE "$webuser ALL=NOPASSWD:$lzbase/scripts/dorestore.sh\n";
                print SFILE "# </lzconfig> END: Added by LogZilla installation on $now\n";
                close(SFILE);
                print "Appended sudoer access for $webuser to $file\n";

                if ( $os !~ /Ubuntu/i ) {
                    my $find = qr/^Defaults.*requiretty/;
                    open SFILE, "<$file";
                    my @lines = <SFILE>;
                    close SFILE;
                    if ( grep( /$find/, @lines ) ) {
                        print "Non-ubuntu OS's will require removal (or comment out) of the following line from $file:\n";
                        print "Defaults    requiretty\n";
                    }
                }
            }
        } else {
            print "$file does not exist\nUnable to continue!";
            exit;
        }

    } else {
        print "Skipping SUDO setup.\n";
        print "You will need to add the following to your sudoers so that LogZilla has permission to apply changes from the web interface\n";
        print "Note: You should change \"www-data\" below to match the user that runs Apache\n";
        print "# <lzconfig> BEGIN: Added by LogZilla installation on $now\n";
        print "# Allows Apache user to HUP the syslog-ng process\n";
        print "www-data ALL=NOPASSWD:$lzbase/scripts/hup.pl\n";
        print "www-data ALL=NOPASSWD:$lzbase/scripts/licadd.pl\n";
        print "www-data ALL=NOPASSWD:$lzbase/scripts/doimport.sh\n";
        print "www-data ALL=NOPASSWD:$lzbase/scripts/dorestore.sh\n";
        print "# </lzconfig> END: Added by LogZilla installation on $now\n";

    }
}

sub kill {
    my $PROGRAM = shift;
    my @ps      = `ps ax`;
    @ps = map { m/(\d+)/; $1 } grep { /\Q$PROGRAM\E/ } @ps;
    for (@ps) {
        ( kill 9, $_ ) or die("Unable to kill process for $PROGRAM\n");
    }
    my $time = gmtime();

    #print "Killed $PROGRAM @ps\n";
}

sub install_sphinx {

    # [[ticket:306]]
    my $now   = strftime( '%Y-%m-%d %H:%M:%S', localtime );
    my $procs = `cat /proc/cpuinfo | grep ^proce | wc -l`;
    my $arch  = `uname -m`;
    if ( $procs > 3 ) {
        $j4 = "-j4";
    }

    # TH: ID64 works also on IA32 machines
    # if ($arch =~ /64/) {
    # $arch = "--enable-id64";
    # }
    my $makecmd = "make $j4 install";
    print("\n\033[1m\n\n========================================\033[0m\n");
    print("\n\033[1m\tSphinx Indexer\n\033[0m");
    print("\n\033[1m========================================\n\n\033[0m\n\n");
    print "Install will attempt to extract and compile your sphinx indexer.\n";
    print "This option may not work on all systems, so please watch for errors.\n";
    print "The steps taken are as follows:\n";
    print "killall searchd (to stop any currently running Sphinx searchd processes).\n";
    # [[ticket:417]] - extract sphinx srouce from tarball
    print "tar xzvf $lzbase/sphinx/sphinx_source.tgz -C $lzbase/sphinx\n";
    print "cd $lzbase/sphinx/src\n";
    print "./configure --enable-id64 --with-syslog --prefix `pwd`/..\n";
    print "$makecmd\n";
    print "cd $lzbase/sphinx\n";
    print "./indexer.sh full\n";

    my $ok = &getYN( "Ok to continue?", "y" );
    if ( $ok =~ /[Yy]/ ) {
        my $checkprocess = `ps -C searchd -o pid=`;
        if ($checkprocess) {
            system("kill -9 $checkprocess");
        }
        system("tar xzvf $lzbase/sphinx/sphinx_source.tgz -C $lzbase/sphinx && cd $lzbase/sphinx/src && ./configure --enable-id64 --with-syslog --prefix `pwd`/.. && $makecmd");
        print "Starting Sphinx search daemon and re-indexing data...\n";
        system("(rm -f $lzbase/sphinx/data/* && cd $lzbase/sphinx && ./indexer.sh full)");
    } else {
        print "Skipping Sphinx Installation\n";
    }
}

sub setup_apparmor {

    # Attempt to fix AppArmor
    my $file = "/etc/apparmor.d/usr.sbin.mysqld";
    if ( -e "$file" ) {
        open FILE, "<$file";
        my @lines = <FILE>;
        close FILE;
        if ( !grep( /logzilla_import/, @lines ) ) {
            print("\n\033[1m\n\n========================================\033[0m\n");
            print("\n\033[1m\tAppArmor Setup\n\033[0m");
            print("\n\033[1m========================================\n\n\033[0m\n\n");
            print "In order for MySQL to import and export data, you must take measures to allow it access from AppArmor.\n";
            print "Install will attempt do do this for you, but please be sure to check /etc/apparmor.d/usr.sbin.mysqld and also to restart the AppArmor daemon once install completes.\n";
            my $ok = &getYN( "Ok to continue?", "y" );
            if ( $ok =~ /[Yy]/ ) {
                print "Adding the following to lines to $file:\n";
                print "/tmp/logzilla_import.txt r,\n$lzbase/exports/** rw,\n";
                open my $config, '+<', "$file" or warn "FAILED: $!\n";
                my @all = <$config>;
                seek $config, 0, 0;
                splice @all, -1, 0, "# <lzconfig> (please do not remove this line)\n  /tmp/logzilla_import.txt r,\n  $lzbase/exports/** rw,\n  /tmp/** r,\n# </lzconfig> (please do not remove this line)\n";
                print $config @all;
                close $config;
            }
            print "\n\nAppArmor must be restarted, would you like to restart it now?\n";
            my $ok = &getYN( "Ok to continue?", "y" );
            if ( $ok =~ /[Yy]/ ) {
                my $r = `/etc/init.d/apparmor restart`;
            } else {
                print("\033[1m\n\tPlease be sure to restart apparmor..\n\033[0m");
            }
        }
    }
}

sub setup_rclocal {
    my $file = "/etc/rc.local";
    if ( -e "$file" ) {
        open my $config, '+<', "$file" or warn "FAILED: $!\n";
        my @all = <$config>;
        if ( !grep( /sphinx/, @all ) ) {
            seek $config, 0, 0;
            splice @all, -1, 0, "# <lzconfig>\n(cd $lzbase/sphinx && bin/searchd)\n# </lzconfig>\n";
            print $config @all;
        }
        close $config;
    } else {
        print("\n\033[1m\tERROR!\n\033[0m");
        print "Unable to locate your $file\n";
        print "You will need to manually add the Sphinx Daemon startup to your system...\n";
        print "Sphinx startup command:\n";
        print "$lzbase/sphinx/bin/searchd -c $lzbase/sphinx/sphinx.conf\n";
    }
}

sub fbutton {
    my $dbh = db_connect( $dbname, $lzbase, $dbroot, $dbrootpass );

    # Feedback button
    print("\n\033[1m\n\n========================================\033[0m\n");
    print("\n\033[1m\tFeedback and Support\n\033[0m");
    print("\n\033[1m========================================\n\n\033[0m\n\n");

    print "\nIf it's ok with you, install will include a small 'Feedback and Support'\n";
    print "icon which will appear at the bottom right side of the web page\n";
    print "This non-intrusive button will allow you to instantly open support \n";
    print "requests with us as well as make suggestions on how we can make LogZilla better.\n";
    print "You can always disable it by selecting 'Admin>Settings>FEEDBACK' from the main menu\n";
    my $ok = &getYN( "Ok to add support and feedback?", "y" );
    if ( $ok =~ /[Yy]/ ) {
        my $sth = $dbh->prepare( "
            update settings set value='1' where name='FEEDBACK';
            " ) or die "Could not update settings table: $DBI::errstr";
        $sth->execute;
    }
}

sub hup_syslog {

    # syslog-ng HUP
    print "\n\n";
    my $checkprocess = `ps -C syslog-ng -o pid=`;
    if ($checkprocess) {
        print "\n\nSyslog-ng MUST be restarted, would you like to send a HUP signal to the process?\n";
        my $ok = &getYN( "Ok to HUP syslog-ng?", "y" );
        if ( $ok =~ /[Yy]/ ) {
            if ( $checkprocess =~ /(\d+)/ ) {
                my $pid = $1;
                print STDOUT "HUPing syslog-ng PID $pid\n";
                my $r = `kill -HUP $pid`;
            } else {
                print STDOUT "Unable to find PID for syslog-ng\n";
            }
        } else {
            print("\033[1m\n\tPlease be sure to restart syslog-ng..\n\033[0m");
        }
    }
}

sub hup_crond {
    print "\n\n";
    my $checkprocess = `cat /var/run/crond.pid`;
    if ($checkprocess) {
        print "\n\nCron.d should be restarted, would you like to send a HUP signal to the process?\n";
        my $ok = &getYN( "Ok to HUP CRON?", "y" );
        if ( $ok =~ /[Yy]/ ) {
            if ( $checkprocess =~ /(\d+)/ ) {
                my $pid = $1;
                print STDOUT "HUPing CRON PID $pid\n";
                my $r = `kill -HUP $pid`;
            } else {
                print STDOUT "Unable to find PID for CRON.D in /var/run\n";
            }
        } else {
            print("\033[1m\n\tPlease be sure to restart CRON..\n\033[0m");
        }
    }
}

print("\n\033[1m\tLogZilla installation complete!\n\033[0m");

# Wordwrap system: deal with the next character
sub wrap_one_char {
    my $output   = shift;
    my $pos      = shift;
    my $word     = shift;
    my $char     = shift;
    my $reserved = shift;
    my $length;

    my $cTerminalLineSize = 79;
    if ( not( ( $char eq "\n" ) || ( $char eq ' ' ) || ( $char eq '' ) ) ) {
        $word .= $char;

        return ( $output, $pos, $word );
    }

    # We found a separator.  Process the last word

    $length = length($word) + $reserved;
    if ( ( $pos + $length ) > $cTerminalLineSize ) {

       # The last word doesn't fit in the end of the line. Break the line before
       # it
        $output .= "\n";
        $pos = 0;
    }
    ( $output, $pos ) = append_output( $output, $pos, $word );
    $word = '';

    if ( $char eq "\n" ) {
        $output .= "\n";
        $pos = 0;
    } elsif ( $char eq ' ' ) {
        if ($pos) {
            ( $output, $pos ) = append_output( $output, $pos, ' ' );
        }
    }

    return ( $output, $pos, $word );
}

# Wordwrap system: word-wrap a string plus some reserved trailing space
sub wrap {
    my $input    = shift;
    my $reserved = shift;
    my $output;
    my $pos;
    my $word;
    my $i;

    if ( !defined($reserved) ) {
        $reserved = 0;
    }

    $output = '';
    $pos    = 0;
    $word   = '';
    for ( $i = 0 ; $i < length($input) ; $i++ ) {
        ( $output, $pos, $word ) = wrap_one_char( $output, $pos, $word,
            substr( $input, $i, 1 ), 0 );
    }

    # Use an artifical last '' separator to process the last word
    ( $output, $pos, $word ) = wrap_one_char( $output, $pos, $word, '', $reserved );

    return $output;
}

# Print message
sub msg {
    my $msg = shift;

    print $msg . "\n";
    exit;
}

sub do_upgrade {
    my $rev = shift;
    print("\n\033[1m\tUpgrading, please be patient!\nIf you have a large DB, this could take a long time...\n\033[0m");
    my $dbh = db_connect( $dbname, $lzbase, $dbroot, $dbrootpass );
    if ( $rev eq "0" ) {
        print "You are running an unsupported version of LogZilla (<3.1)\n";
        print "An attempt will be made to upgrade to $version$subversion...\n";
        my $ok = &getYN( "Continue? (yes/no)", "y" );
        if ( $ok =~ /[Yy]/ ) {
            add_snare_to_logtable();
            do_programs();
            tbl_add_severities();
            tbl_add_facilities();
            create_snare_table();
            do_email_alerts();
            update_procs();
            make_archive_tables();
            make_dbuser();
            add_table_triggers();

            if ( colExists( "logs", "priority" ) eq 1 ) {
                tbl_logs_alter_from_30();
            }
            print "\n\tUpgrade complete, continuing installation...\n\n";
        }
    }
    elsif ( $rev eq "1122" ) {
        print "Upgrading Database from v3.1.122 to $version$subversion...\n";
        add_snare_to_logtable();
        create_snare_table();
        do_email_alerts();
        update_procs();
        make_archive_tables();
        make_dbuser();
        add_table_triggers();
        print "\n\tUpgrade complete, continuing installation...\n\n";

    }
    elsif ( $rev eq "php-syslog-ng" ) {
        print "You are running an unsupported version of LogZilla (Php-syslog-ng v2.x)\n";
        print "An attempt will be made to upgrade to $version$subversion...\n";
        my $ok = &getYN( "Continue? (yes/no)", "y" );
        if ( $ok =~ /[Yy]/ ) {
            add_snare_to_logtable();
            do_programs();
            tbl_add_severities();
            tbl_add_facilities();
            create_snare_table();
            do_email_alerts();
            update_procs();
            make_dbuser();
            add_table_triggers();

            if ( colExists( "logs", "priority" ) eq 1 ) {
                tbl_logs_alter_from_299();
            }
            make_partitions();
            make_archive_tables();
            print "\n\tUpgrade complete, continuing installation...\n\n";
        }
    }
    elsif ( $rev eq "32" ) {
        update_procs();
        make_archive_tables();
        make_dbuser();
        add_table_triggers();
        print "\n\tUpgrade complete, continuing installation...\n\n";
    }
    elsif ( $rev eq "all" ) {
        print "Your version is not an officially supported upgrade.\n";
        print "An attempt will be made to upgrade to $version$subversion...\n";
        my $ok = &getYN( "Continue? (yes/no)", "y" );
        if ( $ok =~ /[Yy]/ ) {
            add_snare_to_logtable();
            do_programs();
            tbl_add_severities();
            tbl_add_facilities();
            create_snare_table();
            do_email_alerts();
            update_procs();
            make_archive_tables();
            make_dbuser();
            add_table_triggers();
            print "\n\tUpgrade complete, continuing installation...\n\n";
        }
    }
    elsif ( $rev eq 2 ) {
        print "Attempting upgrade from php-syslog-ng (v2.x) to LogZilla (v3.x)\n";
        print "Not Implemented yet...sorry\n";
        exit;
    }
    else {
        print "Your version is not a candidate for upgrade.\n";
        exit;
    }
    update_help();

    # Insert ui_layout data
    if ( tblExists("ui_layout") eq 1 ) {
        upgrade_ui_layout();
    } else {
        my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/ui_layout.sql`;
    }
    update_settings();
    hup_syslog();
}

sub db_connect {
    my $dbname     = shift;
    my $lzbase     = shift;
    my $dbroot     = shift;
    my $dbrootpass = shift;
    my $dsn        = "DBI:mysql:$dbname:;mysql_read_default_group=logzilla;"
      . "mysql_read_default_file=$lzbase/scripts/sql/lzmy.cnf";
    my $dbh = DBI->connect( $dsn, $dbroot, $dbrootpass );

    if ( !$dbh ) {
        print "Can't connect to the mysql database: ", $DBI::errstr, "\n";
        exit;
    }

    return $dbh;
}

sub db_exists {
    my $dbh = DBI->connect( "DBI:mysql:mysql:$dbhost:$dbport", $dbroot, $dbrootpass );
    my $sth = $dbh->prepare("show databases like '$dbname'") or die "Could not get DB's: $DBI::errstr";
    $sth->execute;
    while ( my @data = $sth->fetchrow_array() ) {
        if ( $data[0] == "$dbtable" ) {
            return 1;
        } else {
            return 0;
        }
    }
}

sub getVer {
    my $dbh = db_connect( $dbname, $lzbase, $dbroot, $dbrootpass );
    if ( colExists( "settings", "id" ) eq 1 ) {
        my $ver = $dbh->selectrow_array( "
            SELECT value from settings where name='VERSION';
            " );
        my ( $major, $minor ) = split( /\./, $ver );
        my $sub = $dbh->selectrow_array("SELECT value from settings where name='VERSION_SUB'; ");
        $sub =~ s/^\.//;
        return ( $major, $minor, $sub );
    } else {

        # If there is no settings table in the DB, it's php-syslog-ng v2.x
        return ( 2, 99, 0 );
    }
}

sub add_table_triggers {
    my $dbh = db_connect( $dbname, $lzbase, $dbroot, $dbrootpass );
    print "Dropping Table Triggers...\n";
    $dbh->do("DROP TRIGGER IF EXISTS counts") or die "Could not drop trigger: $DBI::errstr";
    $dbh->do("DROP TRIGGER IF EXISTS system_log") or die "Could not drop trigger: $DBI::errstr";
    print "Adding Table Triggers...\n";
    $dbh->do( "
        CREATE TRIGGER `system_log`
        BEFORE INSERT ON system_log
        FOR EACH ROW
        BEGIN
        SET NEW.timestamp = NOW();
        END
        " ) or die "Could not add triggers: $DBI::errstr";

}

sub add_snare_to_logtable {
    if ( colExists( "$dbtable", "eid" ) eq 0 ) {
        my $dbh = db_connect( $dbname, $lzbase, $dbroot, $dbrootpass );
        print "Adding SNARE eids to $dbtable...\n";
        $dbh->do("ALTER TABLE $dbtable ADD `eid` int(10) unsigned NOT NULL DEFAULT '0'") or die "Could not update $dbtable: $DBI::errstr";
        print "Adding SNARE index to $dbtable...\n";
        $dbh->do("ALTER TABLE $dbtable ADD index eid(eid)") or die "Could not update $dbtable: $DBI::errstr";
    }
}

sub create_snare_table {
    my $dbh = db_connect( $dbname, $lzbase, $dbroot, $dbrootpass );
    if ( tblExists("snare_eid") eq 1 ) {
        copy_old_snare();
    } else {
        print "Adding SNARE table...\n";
        my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/snare_eid.sql`;
    }
}

sub copy_old_snare {
    my $dbh = db_connect( $dbname, $lzbase, $dbroot, $dbrootpass );
    print "Updating SNARE table...\n";
    $dbh->do("RENAME TABLE snare_eid TO snare_eid_orig") or die "Could not update $dbname: $DBI::errstr";
    my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/snare_eid.sql`;
    print $res;
    $dbh->do("REPLACE INTO snare_eid SELECT * FROM snare_eid_orig; ") or die "Could not update $dbname: $DBI::errstr";
    $dbh->do("DROP TABLE snare_eid_orig") or die "Could not update $dbname: $DBI::errstr";
}

sub copy_old_archives {
    my $dbh = db_connect( $dbname, $lzbase, $dbroot, $dbrootpass );
    print "Updating Archives table...\n";
    $dbh->do("RENAME TABLE archives TO archives_orig") or die "Could not update $dbname: $DBI::errstr";
    my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/archives.sql`;
    print $res;
    $dbh->do("REPLACE INTO archives SELECT * FROM archives_orig; ") or die "Could not update $dbname: $DBI::errstr";
    $dbh->do("DROP TABLE archives_orig") or die "Could not update $dbname: $DBI::errstr";
}

sub verify_columns {

# As of v4.0, we will just do this for all columns regardless of install or upgrade to make sure they exist.
    my $dbh = db_connect( $dbname, $lzbase, $dbroot, $dbrootpass );
    print "Verifying Table Columns...\n";
    my @tables = ( 'hosts', 'programs', 'snare_eid', 'mne' );
    my @cols = ( 'lastseen', 'seen', 'hidden' );
    foreach (@tables) {
        print "Validating $_ table:\n";
        my $table = $_;
        if ( colExists( "$table", "id" ) eq 0 ) {
            print "Creating $table table...\n";
            my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/$table.sql`;
            print "$res\n";
        }
        foreach (@cols) {
            my $col = $_;
            print "Validating $table.$col\n";
            if ( colExists( "$table", "$col" ) ne 1 ) {
                print "Updating $table $col column...\n";
                if ( $col eq "lastseen" ) {
                    $dbh->do("ALTER TABLE $table ADD `lastseen` datetime NOT NULL default '2012-01-01 00:00:00'; ") or die "Could not update $dbname: $DBI::errstr";
                }
                elsif ( $col eq "seen" ) {
                    $dbh->do("ALTER TABLE $table ADD `seen` int(10) unsigned NOT NULL DEFAULT '1'; ") or die "Could not update $dbname: $DBI::errstr";
                }
                elsif ( $col eq "hidden" ) {
                    $dbh->do("ALTER TABLE $table ADD `hidden` enum('false','true') DEFAULT 'false'; ") or die "Could not update $dbname: $DBI::errstr";
                }
            }
        }
    }

    # Test for RBAC
    my @tables = ( 'hosts', 'users' );
    foreach (@tables) {
        my $table = $_;
        if ( colExists( "$table", "rbac_key" ) eq 0 ) {
            $dbh->do("ALTER TABLE $table ADD `rbac_key` int(10) unsigned NOT NULL DEFAULT '1'; ") or die "Could not update $dbname: $DBI::errstr";
            $dbh->do("ALTER TABLE $table ADD KEY `rbac` (`rbac_key`); ") or die "Could not update $dbname: $DBI::errstr";
        }
    }
    # Test for EPX
    my $table = 'events_per_second';
    if ( colExists( "$table", "name" ) eq 0 ) {
        print "Creating $table table...\n";
        my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/epx.sql`;
        print "$res\n";
    }
    # fix for notes column not having the default value set in LogZilla v4.25
    $dbh->do("ALTER TABLE logs MODIFY `notes` varchar(255) NOT NULL DEFAULT '';") or die "Could not update $dbname: $DBI::errstr";
}

sub update_version {
    my $dbh = db_connect( $dbname, $lzbase, $dbroot, $dbrootpass );
    my $sth = $dbh->prepare( "
        update settings set value='$version' where name='VERSION';
        " ) or die "Could not update settings table: $DBI::errstr";
    $sth->execute;
    my $sth = $dbh->prepare( "
        update settings set value='$subversion' where name='VERSION_SUB';
        " ) or die "Could not update settings table: $DBI::errstr";
    $sth->execute;
}

sub tbl_logs_alter_from_30 {
    my $dbh = db_connect( $dbname, $lzbase, $dbroot, $dbrootpass );
    print "Attempting to modify an older logs table to work with the new version.\n";
    print "This could take a VERY long time, DO NOT cancel this operation\n";
    if ( colExists( "$dbtable", "priority" ) eq 1 ) {

        print "Updating column: priority->severity\n";
        $dbh->do("ALTER TABLE $dbtable CHANGE `priority` severity enum('0','1','2','3','4','5','6','7') NOT NULL") or die "Could not update $dbname: $DBI::errstr";

        print "Updating column: facility\n";
        $dbh->do("ALTER TABLE $dbtable CHANGE `facility` `facility` enum('0','1','2','3','4','5','6','7','8','9','10','11','12','13','14','15','16','17','18','19','20','21','22','23') NOT NULL") or die "Could not update $dbname: $DBI::errstr";

        print "Dropping tag column\n";
        $dbh->do("ALTER TABLE $dbtable DROP COLUMN tag") or die "Could not update $dbname: $DBI::errstr";

        print "Updating column: program\n";
        $dbh->do("ALTER TABLE $dbtable CHANGE `program` `program` int(10) unsigned NOT NULL") or die "Could not update $dbname: $DBI::errstr";

        print "Updating column: mne\n";
        $dbh->do("ALTER TABLE $dbtable CHANGE `mne` `mne` int(10) unsigned NOT NULL") or die "Could not update $dbname: $DBI::errstr";
        print "Adding Sphinx Counter table\n";
        my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/sph_counter.sql`;

    }
}

sub tbl_logs_alter_from_299 {
    my $dbh = db_connect( $dbname, $lzbase, $dbroot, $dbrootpass );
    print("\n\033[1m\tWARNING!\n\033[0m");
    print "Attempting to modify an older logs table to work with the new version.\n";
    print "This could take a VERY long time, DO NOT cancel this operation\n";
    if ( colExists( "$dbtable", "priority" ) eq 1 ) {

        print "Updating column: priority->severity\n";
        $dbh->do("ALTER TABLE $dbtable CHANGE `priority` severity enum('0','1','2','3','4','5','6','7') NOT NULL") or die "Could not update $dbname: $DBI::errstr";

        print "Updating column: facility\n";
        $dbh->do("ALTER TABLE $dbtable CHANGE `facility` `facility` enum('0','1','2','3','4','5','6','7','8','9','10','11','12','13','14','15','16','17','18','19','20','21','22','23') NOT NULL") or die "Could not update $dbname: $DBI::errstr";

        print "Dropping tag column\n";
        $dbh->do("ALTER TABLE $dbtable DROP COLUMN tag") or die "Could not update $dbname: $DBI::errstr";

        print "Dropping level column\n";
        $dbh->do("ALTER TABLE $dbtable DROP COLUMN level") or die "Could not update $dbname: $DBI::errstr";

        print "Dropping seq column\n";
        $dbh->do("ALTER TABLE $dbtable DROP COLUMN seq") or die "Could not update $dbname: $DBI::errstr";

        print "Updating column: program\n";
        $dbh->do("ALTER TABLE $dbtable CHANGE `program` `program` int(10) unsigned NOT NULL") or die "Could not update $dbname: $DBI::errstr";

        print "Updating column: host\n";
        $dbh->do("ALTER TABLE $dbtable CHANGE `host` `host` varchar(128) NOT NULL") or die "Could not update $dbname: $DBI::errstr";

        print "Updating column: fo\n";
        $dbh->do("ALTER TABLE $dbtable CHANGE `fo` `fo` datetime NOT NULL") or die "Could not update $dbname: $DBI::errstr";

        print "Updating column: lo\n";
        $dbh->do("ALTER TABLE $dbtable CHANGE `lo` `lo` datetime NOT NULL") or die "Could not update $dbname: $DBI::errstr";

        print "Adding column: mne\n";
        $dbh->do("ALTER TABLE $dbtable ADD `mne` int(10) unsigned NOT NULL") or die "Could not update $dbname: $DBI::errstr";

        print "Adding column: suppress\n";
        $dbh->do("ALTER TABLE $dbtable ADD `suppress` datetime NOT NULL DEFAULT '2010-03-01 00:00:00'") or die "Could not update $dbname: $DBI::errstr";

        print "Adding column: notes\n";
        $dbh->do("ALTER TABLE $dbtable ADD `notes` varchar(255) NOT NULL") or die "Could not update $dbname: $DBI::errstr";

        print "Altering column: msg\n";
        $dbh->do("ALTER TABLE $dbtable CHANGE `msg` `msg` varchar(2048) NOT NULL") or die "Could not update $dbname: $DBI::errstr";

        print "Dropping index: priority\n";
        $dbh->do("ALTER TABLE $dbtable DROP INDEX priority") or die "Could not update $dbname: $DBI::errstr";

        print "Adding index: severity\n";
        $dbh->do("ALTER TABLE $dbtable ADD INDEX severity (severity)") or die "Could not update $dbname: $DBI::errstr";

        print "Adding index: mne\n";
        $dbh->do("ALTER TABLE $dbtable ADD INDEX mne (mne)") or die "Could not update $dbname: $DBI::errstr";

        print "Adding index: suppress\n";
        $dbh->do("ALTER TABLE $dbtable ADD INDEX suppress (suppress)") or die "Could not update $dbname: $DBI::errstr";

        print "Adding primary key\n";
        $dbh->do("ALTER TABLE $dbtable DROP PRIMARY KEY, ADD PRIMARY KEY (`id`, `lo`)") or die "Could not update $dbname: $DBI::errstr";
        print "Dropping users table primary key\n";
        $dbh->do("ALTER TABLE users DROP PRIMARY KEY") or die "Could not update $dbname: $DBI::errstr";

        print "Modifying users table: add id and primary key\n";
        $dbh->do("ALTER TABLE users ADD `id` int(9) NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY id (id);") or die "Could not update $dbname: $DBI::errstr";

        print "Updating column: users.username\n";
        $dbh->do("ALTER TABLE users CHANGE `username` `username` varchar(15) NOT NULL") or die "Could not update $dbname: $DBI::errstr";
        print "Adding column: users.group\n";
        $dbh->do("ALTER TABLE users ADD `group` int(3) NOT NULL DEFAULT '2'") or die "Could not update $dbname: $DBI::errstr";

        print "Adding column: users.totd\n";
        $dbh->do("ALTER TABLE users ADD `totd` enum('show','hide') NOT NULL DEFAULT 'show'") or die "Could not update $dbname: $DBI::errstr";

        print "Setting up $siteadmin user\n";
        $dbh->do("REPLACE INTO `users` (username,pwhash) VALUES ('$siteadmin',md5('$siteadminpw'))") or die "Could not update $dbname: $DBI::errstr";

        print "Dropping table: actions\n";
        $dbh->do("DROP TABLE actions") or die "Could not update $dbname: $DBI::errstr";

        print "Dropping MERGE table: all_logs\n";
        $dbh->do("DROP TABLE all_logs") or die "Could not update $dbname: $DBI::errstr";

        print "Dropping table: cemdb\n";
        $dbh->do("DROP TABLE cemdb") or die "Could not update $dbname: $DBI::errstr";

        print "Dropping table: search_cache\n";
        $dbh->do("DROP TABLE search_cache") or die "Could not update $dbname: $DBI::errstr";

        print "Dropping table: user_access\n";
        $dbh->do("DROP TABLE user_access") or die "Could not update $dbname: $DBI::errstr";

        print "Adding Sphinx Counter table\n";
        my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/sph_counter.sql`;

        print "Adding Cache Table\n";
        my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/cache.sql`;
        print $res;

        print "Adding Groups Table\n";
        my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/groups.sql`;
        print $res;

        print "Adding History Table\n";
        my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/history.sql`;
        print $res;

        print "Adding lzecs Table\n";
        my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/lzecs.sql`;
        print $res;

        print "Creating Suppress Table\n";
        my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/suppress.sql`;
        print $res;

        print "Creating Totd Table\n";
        my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/totd.sql`;

        print "Creating views\n";
        create_views();
    }
}

sub do_email_alerts {
    my $dbh = db_connect( $dbname, $lzbase, $dbroot, $dbrootpass );
    if ( tblExists("triggers") eq 0 ) {
        print "Adding Email Alerts...\n";
        my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/triggers.sql`;
    } else {
        print "Updating Email Alerts...\n";
        if ( colExists( "triggers", "description" ) eq 0 ) {
            $dbh->do("ALTER TABLE triggers ADD `description` varchar(255) NOT NULL DEFAULT ''") or die "Could not update $dbtable: $DBI::errstr";
        }
        if ( colExists( "triggers", "to" ) eq 1 ) {
            $dbh->do("ALTER TABLE triggers CHANGE `to` `mailto` varchar (255)") or die "Could not update $dbtable: $DBI::errstr";
        }
        if ( colExists( "triggers", "from" ) eq 1 ) {
            $dbh->do("ALTER TABLE triggers CHANGE `from` `mailfrom` varchar (255)") or die "Could not update $dbtable: $DBI::errstr";
        }
        if ( colExists( "triggers", "disabled" ) eq 0 ) {
            $dbh->do("ALTER TABLE triggers ADD `disabled` enum('Yes','No') NOT NULL DEFAULT 'Yes'") or die "Could not update $dbtable: $DBI::errstr";
        }

        #continue
        $dbh->do("RENAME TABLE triggers TO triggers_orig") or die "Could not update $dbname: $DBI::errstr";
        my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/triggers.sql`;
        print $res;
        $dbh->do("REPLACE INTO triggers SELECT * FROM triggers_orig; ") or die "Could not update $dbname: $DBI::errstr";
        $dbh->do("DROP TABLE triggers_orig") or die "Could not update $dbname: $DBI::errstr";
    }
}

sub do_programs {
    my $dbh = db_connect( $dbname, $lzbase, $dbroot, $dbrootpass );
    if ( tblExists("programs") eq 0 ) {
        print "Adding Programs Table...\n";
        my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/programs.sql`;
    } else {
        print "Updating Programs Table...\n";
        $dbh->do("RENAME TABLE programs TO programs_orig") or die "Could not update $dbname: $DBI::errstr";
        my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/programs.sql`;
        print $res;
        $dbh->do("REPLACE INTO programs SELECT * FROM programs_orig; ") or die "Could not update $dbname: $DBI::errstr";
        $dbh->do("DROP TABLE programs_orig") or die "Could not update $dbname: $DBI::errstr";
    }
}

sub tbl_add_severities {
    if ( tblExists("severities") eq 0 ) {
        print "Adding Severities Table...\n";
        my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/severities.sql`;
        print $res;
    }
}

sub tbl_add_facilities {
    if ( tblExists("facilities") eq 0 ) {
        print "Adding Facilities Table...\n";
        my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/facilities.sql`;
        print $res;
    }
}

sub update_help {
    print "Updating help files...\n";
    my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/help.sql`;
    print $res;
}

sub upgrade_ui_layout {
    my $dbh = db_connect( $dbname, $lzbase, $dbroot, $dbrootpass );
    print "Updating UI Layout...\n";
    $dbh->do("RENAME TABLE ui_layout TO ui_layout_orig") or die "Could not update $dbname: $DBI::errstr";
    my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/ui_layout.sql`;
    print $res;
    $dbh->do("REPLACE INTO ui_layout SELECT * FROM ui_layout_orig; ") or die "Could not update $dbname: $DBI::errstr";
    $dbh->do("DROP TABLE ui_layout_orig") or die "Could not update $dbname: $DBI::errstr";
}

sub copy_old_settings {
    my $dbh = db_connect( $dbname, $lzbase, $dbroot, $dbrootpass );
    print "Updating Settings...\n";
    $dbh->do("RENAME TABLE settings TO settings_orig") or die "Could not update $dbname: $DBI::errstr";
    my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/settings.sql`;
    print $res;
    $dbh->do("REPLACE INTO settings SELECT * FROM settings_orig; ") or die "Could not update $dbname: $DBI::errstr";
    $dbh->do("DROP TABLE settings_orig") or die "Could not update $dbname: $DBI::errstr";
}

sub copy_old_rbac {
    my $dbh = db_connect( $dbname, $lzbase, $dbroot, $dbrootpass );
    print "Updating RBAC...\n";
    $dbh->do("RENAME TABLE rbac TO rbac_orig") or die "Could not update $dbname: $DBI::errstr";
    my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/rbac.sql`;
    print $res;
    $dbh->do("REPLACE INTO rbac SELECT * FROM rbac_orig; ") or die "Could not update $dbname: $DBI::errstr";
    $dbh->do("DROP TABLE rbac_orig") or die "Could not update $dbname: $DBI::errstr";
}

sub copy_old_view_limits {
    my $dbh = db_connect( $dbname, $lzbase, $dbroot, $dbrootpass );
    print "Updating view_limits...\n";
    $dbh->do("RENAME TABLE view_limits TO view_limits_orig") or die "Could not update $dbname: $DBI::errstr";
    my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/view_limits.sql`;
    print $res;
    $dbh->do("REPLACE INTO view_limits SELECT * FROM view_limits_orig; ") or die "Could not update $dbname: $DBI::errstr";
    $dbh->do("DROP TABLE view_limits_orig") or die "Could not update $dbname: $DBI::errstr";
}

sub update_procs {
    my $dbh = db_connect( $dbname, $lzbase, $dbroot, $dbrootpass );
    print "Updating SQL Procedures...\n";
    # Import procedures
    system "perl -i -pe 's| logs | $dbtable |g' sql/procedures.sql" and warn "Could not modify sql/procedures.sql $!\n";
    my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/procedures.sql`;
    print $res;

    # Insert system_log table
    my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/system_log.sql`;
    print $res;

    # Insert rbac table
    if ( tblExists("rbac") eq 1 ) {
        copy_old_rbac();
    } else {
        my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/rbac.sql`;
    }
    
    # Insert view_limits table
    if ( tblExists("view_limits") eq 1 ) {
        copy_old_view_limits();
    } else {
        my $res = `mysql -u$dbroot -p'$dbrootpass' -h $dbhost -P $dbport $dbname < sql/view_limits.sql`;
    }

}

sub insert_test {
    print "Inserting first message as a test ...\n";
        system("$lzbase/scripts/test/genlog -hn 1 -n 1 | $lzbase/scripts/log_processor -d 1 -v");
}

sub colExists {
    my $table = shift;
    my $col   = shift;
    my $dbh   = db_connect( $dbname, $lzbase, $dbroot, $dbrootpass );
    my $sth   = $dbh->column_info( undef, $dbname, $table, '%' );
    my $ref   = $sth->fetchall_arrayref;
    my @cols  = map { $_->[3] } @$ref;

    #print "DEB: looking for $col\n";
    #print "DEB: @cols\n";
    if ( grep( /\b$col\b/, @cols ) ) {
        return 1;
    } else {
        return 0;
    }
}

sub tblExists {
    my $tbl = shift;
    my $dbh = db_connect( $dbname, $lzbase, $dbroot, $dbrootpass );
    my $sth = $dbh->table_info( undef, undef, $tbl, "TABLE" );
    if ( $sth->fetch ) {
        return 1;
    } else {
        return 0;
    }
}

sub add_ioncube {
    print("\n\033[1m\n\n========================================\033[0m\n");
    print("\n\033[1m\tIONCube License Manager\n\033[0m");
    print("\n\033[1m========================================\n\n\033[0m\n\n");
    print "Extracting IONCube files to /usr/local/ioncube\n";
    my $arch = `uname -m`;
    if ( $arch =~ /64/ ) {
        system("tar xzvf ioncube/ioncube_loaders_lin_x86-64.tar.gz -C /usr/local");
    } else {
        system("tar xzvf ioncube/ioncube_loaders_lin_x86.tar.gz -C /usr/local");
    }
    my $phpver = `/usr/bin/php -v | head -1`;
    my $ver = $1 if ( $phpver =~ /PHP (\d\.\d)/ );
    if ( $ver !~ /[45]\.[04]/ ) {
        my $ok = &getYN( "\nInstall will try to add the license loader to php.ini for you is this ok?", "y" );
        if ( $ok =~ /[Yy]/ ) {
            my $file = "/etc/php5/apache2/php.ini";
            if ( !-e "$file" ) {
                $file = &prompt( "Please enter the location of your php.ini file", "$file" );
            }
            if ( !-e "$file" ) {
                print "unable to locate $file\n";
            } else {
                open my $config, '+<', "$file" or warn "FAILED: $!\n";
                my @all = <$config>;
                if ( !grep( /lzconfig/, @all ) ) {
                    seek $config, 0, 0;
                    splice @all, 1, 0, ";# <lzconfig> (please do not remove this line)\nzend_extension = /usr/local/ioncube/ioncube_loader_lin_$ver.so\n;# </lzconfig> (please do not remove this line)\n";
                    print $config @all;
                }
                close $config;

                if ( -e "/etc/init.d/apache2" ) {
                    my $ok = &getYN( "Is it ok to restart Apache to apply changes?", "y" );
                    if ( $ok =~ /[Yy]/ ) {
                        my $r = `/etc/init.d/apache2 restart`;
                    } else {
                        print("\033[1m\n\tPlease be sure to restart your Apache server..\n\033[0m");
                    }
                } else {
                    print("\033[1m\n\tPlease be sure to restart your Apache server..\n\033[0m");
                }
            }
        }
    } else {
        print "\nWARNING: Your PHP version ($ver) does not appear to be a candidate for auto-populating the php.ini file.\nPlease read /usr/local/ioncube/README.txt for more information.\n";
    }
}

sub install_license {

    print("\n\033[1m\n\n========================================\033[0m\n");
    print("\n\033[1m\tLicense\n\033[0m");
    print("\n\033[1m========================================\n\n\033[0m\n\n");
    print "If you have already ordered your license, install will attempt to connect to the licensing server and download it.\n";
    print "It is highly recommended that you use this method in order to avoid any possible copy/paste issues with your license.\n";
    print "If you skip this step, or if something goes wrong, you will still have an opportunity to enter your license in the web interface.\n\n";
    print "You can also run \"$0 install_license\" at any time.\n";
    my $ok = &getYN( "Would you like to attempt automatic license install? (y/n)", "y" );
    if ( $ok =~ /[Yy]/ ) {
        my @lines = `ifconfig -a`;
        my ( $ip, $mac );
        for (@lines) {
            if (/\s*HWaddr (\S+)/) {
                $mac = $1;
            }
            if (/\s*inet addr:([\d.]+)/) {
                $ip = $1;
                last;    # we only want the first interface
            }
        }
        $ip  =~ s/[^a-zA-Z0-9]//g;
        $mac =~ s/[^a-zA-Z0-9]//g;
        my $hash = md5_hex("$ip$mac");

        print "requesting license file for IP $ip and MAC $mac through hash $hash\n"; #for debugging purposes only

        my $url  = "http://licserv.logzilla.pro/files/$hash.txt";
        my $file = "$lzbase/html/license.txt";

        if ( is_success( getstore( $url, $file ) ) ) {
            print "License Installed Successfully\n";
        } else {
            print "\n\033[1m[ERROR] Failed to download: $url\n\033[0m";
            print "Unable to find your license on the license server\n";
            print "You can try using the web interface or contact LogZilla support (support\@logzilla.pro) for assistance\n";
        }
    }
}

sub rm_config_block {
    my $d = strftime( '%m%d%H%M', localtime );
    my $file = shift;
    if ( -e $file ) {
        system "cp $file $file.lzbackup.$d";
        my @data;
        open my $config, '<', "$file" or warn "FAILED: $!\n";
        while (<$config>) {
            next if ( /# <lzconfig>/ .. /# <\/lzconfig>/ );
            next if ( /# http:\/\/nms.gdd.net\/index.php\/Install_Guide_for_LogZilla_v3.2/ .. /# END LogZilla/ );
            next if (/logzilla/);
            next if (/ioncube/);
            push( @data, $_ );
        }
        close $config;
        open FILE, ">$file" or die "Unable to open $file: $!";
        print FILE @data;
        close FILE;
    } else {
        print "$file does not exist\n";
    }
}

sub run_tests {
    print("\n\033[1m\n\n========================================\033[0m\n");
    print("\n\033[1m\tPost-Install Self Tests\n\033[0m");
    print("\n\033[1m========================================\n\n\033[0m\n\n");
    print("\n\033[1m\n\n/*---------------------*/\033[0m\n");
    print("\033[1m     Usability Tests\n\033[0m");
    print("\033[1m/*---------------------*/\n\n\033[0m\n\n");
    opendir( DIR, "$lzbase/t/log_processor" );
    foreach my $file ( sort { $a <=> $b } readdir(DIR) )
    {

        if ( $file =~ /\d+/ ) {
            print "Running test: $file\n";
            my $cmd = `$lzbase/t/log_processor/$file`;
            print "$cmd\n";
        }
    }
    opendir( DIR, "$lzbase/t/sql" );
    foreach my $file ( sort { $a <=> $b } readdir(DIR) )
    {

        if ( $file =~ /\d+/ ) {
            print "Running test: $file\n";
            my $cmd = `$lzbase/t/sql/$file`;
            print "$cmd\n";
        }
    }
    closedir(DIR);
    closedir(DIR);
    print("\n\033[1m\n\n/*---------------------*/\033[0m\n");
    print("\033[1m    Performance Tests\n\033[0m");
    print("\033[1m/*---------------------*/\n\n\033[0m\n\n");
    opendir( DIR, "$lzbase/t/log_processor/perf" );
    foreach my $file ( sort { $a <=> $b } readdir(DIR) )
    {

        if ( $file =~ /\d+/ ) {
            print "Running test: $file\n";
            my $cmd = `$lzbase/t/log_processor/perf/$file`;
            print "$cmd\n";
        }
    }
    closedir(DIR);
}

sub EULA {
    print <<EOF;

SOFTWARE LICENSE & SUPPORT SUBSCRIPTION AGREEMENT STANDARD TERMS AND CONDITIONS

THIS SOFTWARE LICENSE AND SUPPORT SUBSCRIPTION AGREEMENT (this "Agreement") is entered into and effective as of the date you ("Customer") receive the licensed Software which it accompanies (the "Effective Date").

THE PROVISIONS OF THIS AGREEMENT ALLOCATE THE RISKS BETWEEN CUSTOMER AND LOGZILLA.  
 
1.  Definitions.  

"Development Use" means use of the Software by customer to design, develop and/or test new applications for Production Use.
"Documentation" means LogZilla's current user manuals, operating instructions and installation guides generally provided with the Software to its licensees. 
"Maintenance Release" means Upgrades and Updates (as defined in the attached Exhibit A) to the Software which are made available to licensees pursuant to the standard Support Services Terms and Conditions.  
"Order" means the document by which Software and Support Services are ordered by Customer.  The Order shall reference and be solely governed by this Agreement.  The Order may be electronic (via Logzilla's web portal) or written.
"Production Use" means using the Software with Customer's applications for internal business purposes only, which may include third party customers' access to or use of such applications.  Production Use does not include the right to reproduce the software for sublicensing, resale, or distribution, including without limitation, operation on a time sharing or service bureau basis or distributing the software as part of an ASP, VAR, OEM, distributor or reseller arrangement.
"Software" means the object code versions of the Software described on an Order and the related Documentation. 
"Support Services" means technical support for Software under LogZilla's then-current policies. LogZilla's current, standard Support Services Terms and Conditions are attached hereto. 
"Subscription Term" means the first year after the Effective Date of this Agreement and a related Order, including any applicable renewal terms.
"Territory" means the United States and any additional territories explicitly agreed to by the parties, as set forth on an Order. 

2.  License. 
a. License Grant.  LogZilla grants Customer a fee-bearing, non-exclusive and non-transferable (except as permitted herein) license to use the Software and the Documentation, solely for Customer's Development Use and/or Production Use, as specified in an Order, subject to the terms and conditions of this Agreement and the following limitations: (i) Customer may not copy the Software, except for archival or disaster recovery purposes, and if Customer does copy for these purposes, Customer will preserve any proprietary rights notices on the Software and  place such notices on any and all copies Customer has made or makes; (ii) Customer agrees not to lease, rent or sublicense the Software to any third party, or otherwise use it except as permitted in this Agreement; (iii) Customer may modify the Software as it deems fit for its own internal purposes.  Title, ownership rights and all intellectual property rights in and to the Software shall remain the sole and exclusive property of LogZilla. LogZilla retains all rights not expressly granted to Customer in this Agreement.

b.  Consultant Use of Software.  Customer may permit its third party consultants to access and use the Software solely for Customer's operations permitted hereunder, provided that said consultants have signed an agreement with Customer protecting LogZilla's intellectual property with terms no less stringent than the terms and conditions of this Agreement, and that Customer ensures that any such consultant's use of the Software complies with the terms of this Agreement.

c.  Audit.   LogZilla may, at any time during the term of this Agreement and with thirty (30) days prior written notice, request and gain access to Customer's premises, subject to Customer's reasonable security procedures, for the limited purpose of conducting an audit to verify that Customer is in compliance with this Agreement.  Customer will promptly grant such access and cooperate with LogZilla in the audit.  The audit will be restricted in scope, manner and duration to that reasonably necessary to achieve its purpose and not disrupt Customer's operations.  Customer shall be liable for promptly remedying any underpayments revealed during the audit.  If the audit reveals an underpayment discrepancy in excess of five per cent (5%), Customer will also be liable for the costs of the audit.

3.  Confidential Information.  By virtue of this Agreement, the parties may have access to information that is confidential to one another ("Confidential Information").  Confidential Information shall be limited to the Software, the terms and pricing under this Agreement, and all information clearly identified as confidential.  A party's Confidential Information shall not include information that: (i) is or becomes a part of the public domain through no act or omission of the other party; (ii) was in the other party's lawful possession prior to the disclosure and had not been obtained by the other party either directly or indirectly from the disclosing party; (iii) is lawfully disclosed to the other party by a third party without restriction on disclosure; or (iv) is independently developed by the other party. The parties agree to hold each other's Confidential Information in confidence during the term of this Agreement and for a period of two (2) years after termination of this Agreement.  The parties agree, unless required by law, not to make each other's Confidential Information available in any form to any third party for any purpose other than the implementation of this Agreement.  LogZilla may reasonably use Customer's name and a description of Customer's use of the Software for its investor relations and marketing purposes, unless Customer provides written notice to LogZilla that it may not do so.

4.  Payments, Shipments and Taxes.  The total non-refundable (subject to Articles 5(b) and 6(b)(iii)), non-cancelable license and Support Services fees for each Order will be due and payable within thirty (30) days from the date of LogZilla's invoice.  The terms and conditions of this Agreement shall prevail regardless of any preprinted or conflicting terms on a purchase order, other correspondence, and any and all verbal communication. Customer will pay all sales, use, VAT, and other consumption taxes, personal property taxes and other taxes (other than those based on LogZilla's net income) unless Customer furnishes satisfactory proof of exemption.  LogZilla may assess interest charges of one percent (1%) per month for late payments.

5.  Limited Warranty.   

a.  Exclusive Warranty.  For a period of ninety (90) days after delivery of the Software, LogZilla warrants that the Software shall materially conform to the Documentation.  LogZilla does not warrant that operation of the Software will be uninterrupted or "bug" free.

b.  Remedies.  If LogZilla breaches the foregoing warranty and Customer promptly notifies LogZilla in writing of the nature of the breach, LogZilla shall make commercially reasonable efforts to promptly repair or replace the non-conforming Software without charge.  If, after a reasonable opportunity to cure, LogZilla does not repair or replace the non-conforming Software, Customer must return the Software and Documentation to LogZilla, or certify in writing that all copies have been destroyed, and LogZilla will refund the license fees it received from Customer for the Software.  This is Customer's sole and exclusive remedy for breach of the exclusive warranty in Article 5(a).

c.  Disclaimer of Warranty.  THE FOREGOING WARRANTY IS EXCLUSIVE AND IN LIEU OF ALL OTHER WARRANTIES, EXPRESS OR IMPLIED, INCLUDING WARRANTIES OF FITNESS FOR A PARTICULAR PURPOSE, NONINFRINGEMENT, AND MERCHANTABILITY. 

6.  Intellectual Property Indemnification.

a.  Defense.  If a third party claims that Customer's use of the Software infringes any United States patent, copyright, trademark or trade secret, Customer must promptly notify LogZilla in writing.  LogZilla will defend Customer against such claim if Customer reasonably cooperates with LogZilla and allows LogZilla to control the defense and all related settlement negotia¬tions, and then LogZilla will indemnify Customer from and against any damages finally awarded for such infringement.

b.  Injunctive Relief.  If an injunction is sought or obtained against Customer's use of the Software as a result of a third party infringement claim, LogZilla may, at its sole option and expense, (i) procure for Customer the right to continue using the affected Software, (ii) replace or modify the affected Software with functionally equivalent software so that it does not infringe, or, if either (i) or (ii) is not commercially feasible,  (iii) terminate the licenses and refund the license fees received from Customer for the affected Software less a pro rata usage charge based on Customer's prior use, if applicable. 

c.  Disclaimer of Liability.  LogZilla shall have no liability for any third party claim of infringement based upon (i) use of other than the then current, unaltered version of the applicable Software, unless the infringing portion is also in the then current, unaltered release; (ii) use, operation or combination of the applicable Soft¬ware with any programs, data, equip¬ment or documentation that is not deemed by LogZilla to work in conjunction with the Software, if such infringement would have been avoided but for such use,  operation or combination; or (iii) any third party software. The foregoing constitutes the entire liability of LogZilla, and Customer's sole and exclusive remedy with respect to any third party claims of infringement of such intellectual property rights.

7.  Limitation of Liability.  

a.  Limitation.  LogZilla's aggregate liability to Customer for damages concerning performance or nonperformance by LogZilla or in any way related to this Agreement, and regardless of whether the claim for such damages is based in contract, tort, strict liability, or otherwise, shall not exceed the license fees received by LogZilla from Customer for the affected Software for the twelve (12) month period preceding the occurrence of such liability.

b.  No Consequential Damages.  In no event shall LogZilla be liable for any indirect, incidental, special, punitive or consequential damages, including without limitation damages for lost data or lost profits, even if LogZilla has been advised as to the possibility of such damages.  

8.  Term and Termination.  This Agreement, including Exhibit A and any Order(s), will continue for the duration set forth in any Order(s) and will automatically renew in one (1) year increments unless either party terminates the Agreement by providing written notice to the other at least thirty (30) days prior to the anniversary of the Effective Date. Either party will be in default if it declares bankruptcy or otherwise fails to perform any of its duties or obligations and does not undertake an effort to substan¬tially cure such default within thirty (30) days after written notice is given to the defaulting party, except that any breach of Article 3 shall be grounds for immediate termination.  In the event of default, the non-defaulting party may terminate this Agree¬ment by providing written notice of termination to the defaulting party.  If Customer is the defaulting party, Customer must promptly, at LogZilla's direction, destroy or return all affected Software and Documentation.  Upon termination of this Agreement for non-default, the provisions of Articles 1, 2, 3, 4, 5(c), 6(c), 7, 8 and 10 will survive.   Upon termination of this Agreement for default, the provisions of Articles 1, 3, 4, 5(c), 6(c), 7, 8 and 10 will survive.

9.  Subscription Term & Support Services.

a.  Support Services.  Support Services are included as part of this subscription Agreement.  Support Services ordered by Customer will be provided under LogZilla's Support Services policies in effect on the date Support Services are ordered.  LogZilla's Support Services policies as of the Effective Date are attached hereto as Exhibit A.  Except as otherwise provided herein, Support Services fees paid are nonrefundable.

b.  Renewal of Subscription Term.  At the expiration of each Subscription Term, Customer may continue to receive license rights and Support Services in one (1) year increments under LogZilla's then current fees and policies.  LogZilla shall provide Customer reasonable notice of subscription fees due.  If Customer elects not to renew the subscription, Customer shall notify LogZilla of its intent not to renew at least thirty (30) days prior to the end of the applicable Subscription Term.  Reinstatement fees may apply under LogZilla's policies when Customer reinstates its subscription. 

10. General.  

a.  Force Majeure.  Neither party shall be liable for any delay or failure in performance due to causes beyond its reasonable control.

b.  Export Compliance.  Customer may not download or otherwise export or re-export the Software or any underlying information or technology except in full compliance with all United States and other applicable laws and regulations. 

c.  Assignment.  Customer may not assign this Agreement without LogZilla's prior written consent which will not be unreasonably withheld.  

d.  Severability.  If any part of this Agreement is held to be unenforceable, in whole or in part, such holding will not affect the validity of the other parts of the Agreement. 

e.  Waiver.  The waiver of a breach of any provision of this Agreement will not operate or be interpreted as a waiver of any other or subsequent breach.  

f.  Notices.  All notices permitted or required under this Agreement shall be in writing and shall be delivered in person, by facsimile, overnight courier service or mailed by first class, registered or certified mail, postage prepaid, to the address of the party specified above or such other address as either party may specify in writing, Attention: Office of the General Counsel.   Such notice shall be deemed to have been given upon receipt. 

g.  Governing Law.  This Agreement will be governed by both the substantive and procedural laws of North Carolina, U.S.A., excluding its conflict of law rules and the United Nations Convention for the International Sale of Goods.  

h.  United States Government Rights.  The Software provided under this Agreement is commercial computer software developed exclusively at private expense, and is in all respects the proprietary data belonging solely to LogZilla or its licensors. 

Department of Defense Customers: If the Software is acquired by or on behalf of agencies or units of the Department of Defense (DOD), then, pursuant to DOD FAR Supplement Section 227.7202 and its successors (48 C.F.R. 227.7202) the Government's right to use, reproduce or disclose the Software and any accompanying Documentation acquired under this Agreement is subject to the restrictions of this Agreement. 

Civilian Agency  Customers: If the Software is acquired by or on behalf of civilian agencies of the United States Government, then, pursuant to FAR Section 12.212 and its successors (48 C.F.R. 12.212), the Government's right to use, reproduce or disclose the Software and any accompanying Documentation acquired under this Agreement is subject to the restrictions of this Agreement. 


 

ENTIRE AGREEMENT.  Any amendment or modification to the Agreement must be in writing signed by both parties. This Agreement constitutes the entire agreement and supersedes all prior or contemporaneous oral or written agreements regarding the subject matter hereof.  Customer agrees that (i) any and all Orders will be governed by these Standard Terms and Conditions and (ii) the appropriate fees will be timely paid.  The terms and conditions of this Agreement shall prevail regardless of any preprinted or conflicting terms on Orders.  

 
    EXHIBIT A
END USER SUPPORT SERVICES ADDENDUM
    STANDARD TERMS AND CONDITIONS

 
1.  Definitions.

"Error" means either (a) a failure of the Software to conform to the specifications set forth in the Documentation, resulting in the inability to use, or restriction in the use of, the Software, and/or (b) a problem requiring new procedures, clarifications, additional information and/or requests for product enhancements.

"Update" means either a software modification or addition that, when made or added to the Software, corrects the Error, or a procedure or routine that, when observed in the regular operation of the Software, eliminates the practical adverse effect of the Error on Customer.

"Upgrade" means a revision of the Software released by LogZilla to its end user customers generally, during the Support Services Term, to add new and different functions or to increase the capacity of the Software.  Upgrade does not include the release of a new product or added features for which there may be a separate charge. 

2.  LogZilla Customer Support Services. On the Order, Customer may select either (a) LogZilla Production Support for Production Use licenses or (b) LogZilla Development Support for Development Use licenses.  Each includes Maintenance Releases and support.  Subject to additional terms and conditions, Customer may also order customized Support Options and/or Mission Critical Support.
 
3.  Updates.   LogZilla will make commercially reasonable efforts to provide an Update designed to solve or by-pass a reported Error. If such Error has been corrected in a Maintenance Release, Customer must install and implement the applicable Maintenance Release; otherwise, the Update may be provided in the form of a temporary fix, procedure or routine, to be used until a Maintenance Release containing the permanent Update is available. Customer shall reasonably determine the priority level of Errors, pursuant to the following protocols.  

After Customer provides LogZilla with notice of an Error, LogZilla will make commercial best efforts to begin working on a solution to the reported Error within 12 hours.

4.  Maintenance Releases and Upgrades.  During the Support Services Term, LogZilla shall make Maintenance Releases available to Customer if, as and when LogZilla makes any such Maintenance Releases generally available to its customers.   If a question arises as to whether a product offering is an Upgrade or a new product or feature, LogZilla's categorization will govern, provided that LogZilla treats the product offering as a new product or feature for its end user customers generally.
5.  Conditions for Providing Support.  LogZilla's obligation to provide Support Services is conditioned upon the following:  (a) Customer makes reasonable efforts to correct the Error after consulting with LogZilla; (b) Customer provides LogZilla with sufficient information and resources to correct the Error either at LogZilla's Customer Support Center or via remote access to Customer's site, as well as access to the personnel, hardware, and any additional software involved in discovering the Error; (c) Customer promptly installs all Maintenance Releases; and (d) Customer procures, installs and maintains all equipment, telephone lines, communication interfaces and other hardware necessary to operate the Software.
6.  Exclusions from LogZilla's Support Services.  LogZilla is not obligated to provide Support Services in the following situations: (a) the Software has been changed, modified or damaged (except if under the direct supervision of LogZilla); (b) the Error is caused by Customer's negligence, hardware malfunction or other causes beyond the reasonable control of LogZilla; (c) the Error is caused by third party software not licensed through LogZilla; (d) Customer has not installed and implemented Maintenance Release(s) so that the Software is a version supported by LogZilla; or (e) Customer has not paid the Support Services fees when due.
7.  Termination of Support Services. LogZilla reserves the right to discontinue the Support Services should LogZilla, in its sole discretion, determine that continued support for any Software is no longer economically practicable. LogZilla will give Customer at least three (3) months prior written notice of any such discontinuance of Support Services and will refund any unaccrued Support Services fees Customer may have prepaid with respect to the affected Software.  LogZilla shall have no obligation to support or maintain any version of the Software except (i) the then current version of the Software, and (ii) the immediately preceding version of the Software for a period of six (6) months after it is first superseded. LogZilla reserves the right to suspend performance of the Support Services if Customer fails to pay any amount that is payable to LogZilla under the Agreement within thirty (30) days after such amount becomes due.
8.  Customer Feedback.  Customer is not required to, but is encouraged to, provide comprehensive data to LogZilla in connection with any reported Error, including any attempts at bug fixes that Customer may have made, so that the Error may be fixed as soon as practicable and that code-based solutions may be incorporated into future iterations of the Software.\n
EOF
    print "Do you accept the LogZilla License Terms? (yes/no)";
        chomp( my $input = <STDIN> );
    if ( $input !~ /[Yy]/ ) {
        print "Please try again when you are ready to accept.\n";
        exit 1;
    }
}
