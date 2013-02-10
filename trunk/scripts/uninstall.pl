#!/usr/bin/perl

# Script used to uninstall some or all of LogZilla

use strict;
$| = 1;
use DBI;
use Switch;
use POSIX;

# Get LogZilla base directory
use Cwd;
my $lzbase = getcwd;
$lzbase =~ s/\/scripts//g;

system("stty erase ^H");

use vars qw/ %opt /;
my ( $debug, $config, $dbh );

#
# Command line options processing
#
sub init()
{
    use Getopt::Std;
    my $opt_string = 'hd:c:';
    getopts( "$opt_string", \%opt ) or usage();
    usage() if $opt{h};
    $debug = defined( $opt{'d'} ) ? $opt{'d'} : '0';
    $config = defined( $opt{'c'} ) ? $opt{'c'} : "$lzbase/html/config/config.php";
}

init();

#
# Help message
#
sub usage()
{
    print STDERR << "EOF";
This program is used to restore LogZilla to defaults.
    usage: $0 [-hdc] 
    -h        : this (help) message
    -d        : debug level (0-5) (0 = disabled [default])
    -c        : config file (overrides the default config.php file location set in the '\$config' variable in this script)
    example: $0 -d 5 -c $lzbase/html/config/config.php
EOF
    exit;
}
if ( !-f $config ) {
    print STDOUT "Can't open config file \"$config\" : $!\nTry $0 -h\n";
    exit;
}
open( CONFIG, $config );
my @config = <CONFIG>;
close(CONFIG);

my ( $dbtable, $dbuser, $dbpass, $db, $dbhost, $dbport );
foreach my $var (@config) {
    next unless $var =~ /DEFINE/;    # read only def's
    $db = $1 if ( $var =~ /'DBNAME', '(\w+)'/ );
}
if ( !$db ) {
    print "Error: Unable to read $db config variables from $config\n";
    exit;
}
my $dsn = "DBI:mysql:$db:;mysql_read_default_group=logzilla;"
  . "mysql_read_default_file=$lzbase/scripts/sql/lzmy.cnf";
$dbh = DBI->connect( $dsn, $dbuser, $dbpass );
if ( !$dbh ) {
    print LOG "Can't connect to database: ",    $DBI::errstr, "\n";
    print STDOUT "Can't connect to database: ", $DBI::errstr, "\n";
    exit;
}
my $sth = $dbh->prepare("SELECT name,value FROM settings");
$sth->execute();
if ( $sth->errstr() ) {
    print LOG "FATAL: Unable to execute SQL statement: ", $sth->errstr(), "\n";
    print STDOUT "FATAL: Unable to execute SQL statement: ", $sth->errstr(), "\n";
    exit;
}
while ( my @settings = $sth->fetchrow_array() ) {
    $dbtable = $settings[1] if ( $settings[0] =~ /^TBL_MAIN$/ );
}

my ($all);

sub p {
    my ( $prompt, $default ) = @_;
    my $defaultValue = $default ? "[$default]" : "";
    print "$prompt $defaultValue: ";
    chomp( my $input = <STDIN> );
    return $input ? $input : $default;
}

print("\n\033[1m**********************\n\033[0m");
print("\033[1m* LogZilla Uninstall *\n\033[0m");
print "This script will guide you through a step-by-step process to remove some or all of your LogZilla installation from the server.\n";
print "Note that even if you select \"All\", that the $lzbase directory will still be left so that you can re-install at a later time if you like\n";
print("\033[1m**********************\n\033[0m");

my $q = &p( "Please select one of the following options:\n\t[R]eset Administrator's Password\n\t[S]elect components to remove\n\t[A]ll LogZilla components removed/deleted\nChoose one:", "S" );
if ( $q =~ /^[Ss]/ ) {
    my $q = &p( "Database:\n\t[D]rop $db database\n\t[C]lear $db database (clears all logs data for a \"clean\" db)\n\t[S]kip this step\nChoose one:", "S" );
    if ( $q =~ /^[Dd]/ ) {
        drop_db();
    } elsif ( $q =~ /^[Cc]/ ) {
        clean_db();
    } else {
        print "Skipping, no changes made to the $db database.\n";
    }
    my $q = &p( "Remove LogZilla entries from syslog-ng.conf?", "Y" );
    if ( $q =~ /^[Yy]/ ) {
        &rm_config_block("/etc/syslog-ng/syslog-ng.conf");
    }
    my $q = &p( "Remove sudo entries from /etc/sudoers?", "Y" );
    if ( $q =~ /^[Yy]/ ) {
        &rm_config_block("/etc/sudoers");
    }
    my $q = &p( "Remove /etc/cron.d/logzilla?", "Y" );
    if ( $q =~ /^[Yy]/ ) {
        unlink("/etc/cron.d/logzilla");
    }
    my $q = &p( "Remove AppArmor profile for LogZilla?", "Y" );
    if ( $q =~ /^[Yy]/ ) {
        &rm_config_block("/etc/apparmor.d/usr.sbin.mysqld");
    }
    my $q = &p( "Remove IONCube license loader?", "Y" );
    if ( $q =~ /^[Yy]/ ) {
        rm_ioncube();
    }
    my $q = &p( "Remove Sphinx boot loader from rc.local?", "Y" );
    if ( $q =~ /^[Yy]/ ) {
        rm_rclocal();
    }
    my $q = &p( "Remove old Sphinx indexes?", "Y" );
    if ( $q =~ /^[Yy]/ ) {
        rm_sphinx();
    }
    my $q = &p( "Reset all paths to default?", "Y" );
    if ( $q =~ /^[Yy]/ ) {
        reset_paths();
    }
}
if ( $q =~ /^[Rr]/ ) {
    &reset_password();
}
if ( $q =~ /^[Aa]/ ) {
    print("\n\033[1m\tThis will remove all LogZilla configurations and drop the $db database.\n\033[0m");
    my $q = &p( "Are you SURE you want to do this?", "Y" );
    if ( $q =~ /^[Yy]/ ) {
        print "Removing LogZilla from syslog-ng\n";
        &rm_config_block("/etc/syslog-ng/syslog-ng.conf");
        print "Removing LogZilla from sudoers\n";
        &rm_config_block("/etc/sudoers");
        print "Removing LogZilla cron.d\n";
        unlink("/etc/cron.d/logzilla");
        print "Removing LogZilla from AppArmor.d\n";
        &rm_config_block("/etc/apparmor.d/usr.sbin.mysqld");
        rm_ioncube();
        rm_rclocal();
        rm_sphinx();
        rm_logrotate();
        drop_db();
        reset_paths();
    }
}

sub rm_ioncube {
    my $file = "/etc/php5/apache2/php.ini";
    if ( !-e "$file" ) {
        $q = &p( "Please specify the location of your php.ini file:", "$file" );
    }
    if ( -e "$file" ) {
        &rm_config_block("$file");
    } else {
        print "Unable to locate $file\n";
    }
    if ( -e "/etc/init.d/apache2" ) {
        my $ok = &p( "Is it ok to restart Apache to apply changes?", "y" );
        if ( $ok =~ /[Yy]/ ) {
            my $r = `/etc/init.d/apache2 restart`;
        } else {
            print("\033[1m\n\tPlease be sure to restart your Apache server..\n\033[0m");
        }
    } else {
        print("\033[1m\n\tPlease be sure to restart your Apache server..\n\033[0m");
    }
}

sub rm_rclocal {
    my $file = "/etc/rc.local";
    if ( !-e "$file" ) {
        $q = &p( "Please specify the location of your rc.local file:", "$file" );
    }
    if ( -e "$file" ) {
        &rm_config_block("$file");
    } else {
       print "Unable to locate $file\n";
    }
}


sub rm_sphinx {
    my $checkprocess = `ps -C searchd -o pid=`;
    if ($checkprocess) {
        system("(cd $lzbase/sphinx && bin/searchd --stop)");
    }
    system("rm -f $lzbase/sphinx/data/idx_*");
}

sub rm_logrotate {
    print "Removing LogZilla from log rotate.d\nNOTE: YOU MUST MANUALLY DELETE /path_to_logs\n";
    my $file = "/etc/logrotate.d/logzilla";
    if ( -f $file ) {
        unlink($file);
    } else {
        print "$file does not exist\n";
    }
}

sub drop_db {
    print("\n\033[1m\tThis will DESTROY the $db database, it can not be recovered without re-installing.\n\033[0m");
    my $q = &p( "Are you SURE you want to drop the $db database?", "N" );
    if ( $q =~ /^[Yy]/ ) {
        print "Dropping Database, please be patient. This may take some time on large systems...\n";
        $dbh->do("drop database $db") or die "Could not drop: $DBI::errstr";
    } else {
        print "DROP $db skipped\n";
    }
}

sub rm_config_block {
    my $d = strftime('%m%d%H%M',localtime);
    my $file = shift;
    if ( -f $file ) {
        system "cp $file $file.lzbackup.$d";
        my @data;
        open my $config, '<', "$file" or warn "FAILED: $!\n";
        while (<$config>) {
            next if ( /# <lzconfig>/ .. /# <\/lzconfig>/ );
            next if ( /# http:\/\/nms.gdd.net\/index.php\/Install_Guide_for_LogZilla_v3.2/ .. /# END LogZilla/ );
            next if ( /logzilla/ );
            next if ( /ioncube/ );
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

sub clean_db {
    print("\n\033[1m\tThis will CLEAR all log data from the $db database. (the table definitions will remain)\n\033[0m");
    my $ok = &p( "ARE YOU SURE?? (yes/no)", "n" );
    if ( $ok =~ /[Yy]/ ) {

        print "Clearing $dbtable...\n";
        my $sth = $dbh->prepare("delete from $dbtable") or die "Could not delete from: $DBI::errstr";
        #my $sth = $dbh->prepare("REPLACE INTO `sph_counter` VALUES (1,1,'idx_logs',NOW()),(2,1,'idx_delta_logs',NOW());") or die "Could not reset Sphinx counters: $DBI::errstr";
        $sth->execute;

        print "Clearing cache...\n";
        my $sth = $dbh->prepare("delete from cache") or die "Could not truncate: $DBI::errstr";
        $sth->execute;

        print "Clearing hosts...\n";
        my $sth = $dbh->prepare("delete from hosts") or die "Could not truncate: $DBI::errstr";
        $sth->execute;

        print "Clearing mne...\n";
        my $sth = $dbh->prepare("delete from mne") or die "Could not truncate: $DBI::errstr";
        $sth->execute;

        print "Clearing programs...\n";
        my $sth = $dbh->prepare("delete from programs") or die "Could not truncate: $DBI::errstr";
        $sth->execute;

        print "Clearing suppress...\n";
        my $sth = $dbh->prepare("delete from suppress") or die "Could not truncate: $DBI::errstr";
        $sth->execute;

        print "Clearing history...\n";
        my $sth = $dbh->prepare("delete from history") or die "Could not truncate: $DBI::errstr";
        $sth->execute;

        print "Clearing snare_eid...\n";
        my $sth = $dbh->prepare("delete from snare_eid") or die "Could not truncate: $DBI::errstr";
        $sth->execute;
        exit;
    }
}

sub reset_password {
    my $user     = &p( "Enter the name of the admin user", "admin" );
    my $password = &p( "Enter the new password for $user", "admin" );
    $password = qq{$password};

    $dbh->do("UPDATE users SET username='$user', pwhash=md5('$password') where id=1") or die "Could not reset password: $DBI::errstr";
    print "The password for $user has been set to $password\n";
}

sub reset_paths {
    my $reset_base    = "/path_to" . "_logzilla";
    my $reset_logbase = "/path_to" . "_logs";

    my $search = $lzbase;
    $search =~ s/\//\\\//g;
    print "\nUpdating file paths\n";
    my @flist = `find $lzbase -name '*.sh' -o -name '*.pl' -o -name '*.conf' -o -name '*.rc' -o -name 'logzilla.*' -type f | egrep -v '/install.pl|sphinx\/src|\\.svn' | xargs grep -l "$search"`;

    #print "@flist\n";
    foreach (@flist) {
        chomp $_;
        print "Modifying $_\n";
        system "perl -i -pe 's|$search|$reset_base|g' $_" and warn "Could not modify $_ $!\n";
    }
    print "Setting default path for sphinx.conf to $lzbase\n";
    system "perl -i -pe 's| lzhome=\"$reset_base\"| lzhome=\"$lzbase\"|g' $lzbase/sphinx/indexer.sh" and warn "Could not modify file $!\n";

    print "\nUpdating log paths\n";
    my $logbase = "/path_to_logs";
    my $search  = $logbase;
    $search =~ s/\//\\\//g;
    my @flist = `find $lzbase -name '*.sh' -o -name '*.pl' -o -name '*.conf' -o -name '*.rc' -o -name 'logzilla.*' -type f | egrep -v '/install.pl|sphinx\/src|\\.svn' | xargs grep -l "$search"`;

    #print "@flist\n";
    foreach (@flist) {
        chomp $_;
        print "Modifying $_\n";
        system "perl -i -pe 's|$search|$reset_logbase|g' $_" and warn "Could not modify $_ $!\n";
    }
}
