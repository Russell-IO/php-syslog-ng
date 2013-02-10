#!/usr/bin/perl

#
# mysql_cnf_generator.pl
#
# Developed by Clayton Dukes <cdukes@logzilla.pro>
# Copyright (c) 2012 logzilla.pro
# All rights reserved.
#
# Changelog:
# 2012-12-08 - created
#

###################################################
# This script could totally hose your system. 
# You probably shouldn't use it.
###################################################

use strict;

$| = 1;

my $checkprocess = `ps -C mysqld -o pid=`;
if ($checkprocess) {
  print "Stopping MySQL\n";
  system("service mysql stop");
}
&chk_ib_logs;
&setup_mycnf("/etc/mysql/conf.d/logzilla.cnf");

if ($checkprocess) {
  print "Starting MySQL\n";
  system("service mysql start");
}




sub chk_ib_logs {
    my @f = </var/lib/mysql/ib_logfile*>;
    foreach my $f (@f) {
        next if $f =~ /orig/;
        my $size = -s $f;
        if ( $size <= 5242880 ) {
            print "Your ib_logfiles are too small (5MB)\n";
            print "Renaming $f to $f.orig. You can delete it later if you like.\n";
            system("mv $f $f.orig");
        }
    }
}

sub setup_mycnf {
    my $file = shift;
    system("touch $file");
    my $sysmem = `cat /proc/meminfo |  grep "MemTotal" | awk '{print \$2}'`;
    $sysmem = ( $sysmem * 1024 );
    my $poolsize                = ( $sysmem * 6 / 10 );
    my $innodb_logfile_size     = ( $poolsize / 4 );
    # Set max log file size to 256M
    $innodb_logfile_size = 268435456 if ($innodb_logfile_size > 268435456);
    my $innodb_log_buffer_size  = ( $innodb_logfile_size / 8 );
    my $Hpoolsize               = humanBytes($poolsize);
    my $Hinnodb_logfile_size    = humanBytes($innodb_logfile_size);
    my $Hinnodb_log_buffer_size = humanBytes($innodb_log_buffer_size);
    if ( -e "$file" ) {
        open my $config, '+<', "$file" or warn "FAILED: $!\n";
        my @arr = <$config>;
        if ( !grep( /logzilla|lzconfig/, @arr ) ) {
            print "Creating MySQL config for LogZilla at $file\n";
            open FILE, ">>$file" or die $!;
            print FILE <<EOF;
[mysqld]
#<lzconfig> BEGIN LogZilla settings
# Based on http://www.mysqlperformanceblog.com/2007/11/01/innodb-performance-optimization-basics/
# Do not depend on these settings to be correct for your server. Please consult your DBA
# You can also run /path_to_logzilla/scripts/tools/mysqltuner.pl for help.
event_scheduler=on
symbolic-links=0
skip-name-resolve
myisam_use_mmap
myisam-block-size = 14384
query_cache_size = 32M
query_cache_limit = 32M
thread_cache_size = 8
table_cache = 2048
key_buffer_size = 128M
innodb_log_buffer_size=8M
innodb_flush_log_at_trx_commit=2
innodb_thread_concurrency=8
innodb_flush_method=O_DIRECT # use only if you have raid with bbu
innodb_support_xa=false
skip_innodb_checksums
skip_innodb_doublewrite
log-error=/var/log/mysql/error.log


# Set innodb_buffer_pool_size to 50-80% of total system memory if this is a dedicated LogZilla server
innodb_buffer_pool_size = $Hpoolsize

# innodb log file size
# http://dev.mysql.com/doc/refman/5.0/en/innodb-configuration.html
# Note: If you modify innodb_log_file_size, you will first need to shut down mysql,
# and delete/rename your current /var/lib/mysql/ib_logfile* files so that mysql can create new ones.
# Check your /var/log/mysql/error.log on startup to make sure it worked properly.
# Set the log file size to about 25% of the buffer pool size not to exceed 256M
# http://www.mysqlperformanceblog.com/2008/11/21/how-to-calculate-a-good-innodb-log-file-size/
innodb_log_file_size = $Hinnodb_logfile_size
innodb_log_buffer_size = $Hinnodb_log_buffer_size

##########################
# Logging
##########################
#log=/var/log/mysql/general.log
#slow-query-log=/var/log/mysql/mysql-slow.log

# Log to the DB instead of files:
# http://www.dzone.com/snippets/log-sql-queries-mysql-table
log-output = TABLE
# Disable logging in production environments.
# Uncomment below to enable for testing.
# slow-query-log 
# general-log
# long_query_time = 1
# expire_logs_days = 1


##########################
# Meta data stats
# Enable this to speed up log_processor startup.
# On slow, or very large servers, InnoDB can take > 30 seconds to start
# It's important that you know what you are doing this for, so please read before enabling it:
# http://dev.mysql.com/doc/refman/5.1/en/innodb-parameters.html#sysvar_innodb_stats_on_metadata
##########################
innodb_stats_on_metadata = 0


#</lzconfig> END LogZilla settings
EOF
        }
    }
}

sub humanBytes {
    my $bytes = shift();
    if ( $bytes > 1099511627776 )    #   TB: 1024 GiB
    {
        return sprintf( "%.0fT", $bytes / 1099511627776 );
    }
    elsif ( $bytes > 1073741824 )    #   GB: 1024 MiB
    {
        return sprintf( "%.0fG", $bytes / 1073741824 );
    }
    elsif ( $bytes > 1048576 )       #   MB: 1024 KiB
    {
        return sprintf( "%.0fM", $bytes / 1048576 );
    }
    elsif ( $bytes > 1024 )          #   KB: 1024 B
    {
        return sprintf( "%.0fK", $bytes / 1024 );
    }
    else                             #   bytes
    {
        return "$bytes" . ( $bytes == 1 ? "" : "s" );
    }
}
