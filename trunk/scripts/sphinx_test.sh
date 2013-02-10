#!/bin/sh

#
# sphinx_test.sh
#
# Developed by Clayton Dukes <cdukes@logzilla.pro>
# Copyright (c) 2012 logzilla.pro
# All rights reserved.
#
# Changelog:
# 2012-12-12 - created
#

mysql="/usr/bin/mysql"
u="root"
p="mysql"
db="syslog"
lz="/path_to_logzilla"
cnt=500000

if [ $# -lt 1 ]; then
    echo "use command line option \"new\", \"cpdb\" or \"delta\" or \"full\" or \"merge\" \"500\""
    exit
fi

for var in "$@"; do
    if [ "$var" = "500" ]; then
        echo "[`date '+%H:%M:%S'`] Generating 500k Messages";
        $lz/scripts/test/genlog --number-of-events 500000 | $lz/scripts/log_processor
    fi
    if [ "$var" = "new" ]; then
        echo "[`date '+%H:%M:%S'`] Clearing DB\n"
        echo "truncate logs" | $mysql -u$u -p$p $db
        echo "[`date '+%H:%M:%S'`] Resetting Sphinx"
        rm $lz/sphinx/data/*
        $mysql -u$u -p$p $db < $lz/scripts/sql/sph_counter.sql
        echo "[`date '+%H:%M:%S'`] Creating Main Index";
        cd $lz/sphinx && ./indexer.sh full
        echo "[`date '+%H:%M:%S'`] Generating $cnt Messages";
        $lz/scripts/test/genlog --number-of-events $cnt | $lz/scripts/log_processor
    fi
    if [ "$var" = "cpdb" ]; then
        echo "[`date '+%H:%M:%S'`] Copying $cnt Messages from the logs table into itself";
        echo "insert into logs (host,facility,severity,program,msg,mne,suppress,counter,fo,lo) select host,facility,severity,program,msg,mne,suppress,counter,fo,lo from logs limit $cnt" | $mysql -u$u -p$p $db

    fi
    tmp="/tmp/$$.tmp"
    if [ "$var" = "delta" ]; then
        echo "[`date '+%H:%M:%S'`] Running Delta (results written to $tmp)"
        cd $lz/sphinx && ./indexer.sh delta > $tmp
        mem_limit=`php $lz/sphinx/sphinx.conf | grep mem_limit | awk '{print $3}' | sed 's/M//g'`
        write_b=`php $lz/sphinx/sphinx.conf | grep write_buffer | awk '{print $3}' | sed 's/M//g'`
        docs_collected=`cat $tmp | grep collected | awk '{print $2}'`
        docs_mb=`cat $tmp | grep collected | awk '{print $4}'`
        mhits=`cat $tmp | grep Mhits | awk '{print $2}'`
        total_docs=`cat $tmp | grep docs | head -2 | tail -1 | awk '{print $2}'`
        bytes=`cat $tmp | grep docs | head -2 | tail -1 | awk '{print $4}'`
        runtime=`cat $tmp | grep docs  | tail -1 | awk '{print $2}'`
        bytes_sec=`cat $tmp | grep docs  | tail -1 | awk '{print $4}'`
        docs_sec=`cat $tmp | grep docs  | tail -1 | awk '{print $6}'`
        echo "mem_limit (MB),write_buffer (MB),docs collected,docs_mb,mhits,total docs,bytes,runtime,bytes/sec,docs/sec"
        echo "$mem_limit,$write_b,$docs_collected,$docs_mb,$mhits,$total_docs,$bytes,$runtime,$bytes_sec,$docs_sec"
    fi
    if [ "$var" = "full" ]; then
        echo "[`date '+%H:%M:%S'`] Running FULL (results written to $tmp)"
        cd $lz/sphinx && ./indexer.sh full > $tmp
        mem_limit=`php $lz/sphinx/sphinx.conf | grep mem_limit | awk '{print $3}' | sed 's/M//g'`
        write_b=`php $lz/sphinx/sphinx.conf | grep write_buffer | awk '{print $3}' | sed 's/M//g'`
        docs_collected=`cat $tmp | grep collected | head -2 | tail -1 | awk '{print $2}'`
        docs_mb=`cat $tmp | grep collected | head -2 | tail -1 | awk '{print $4}'`
        mhits=`cat $tmp | grep Mhits | head -2 | tail -1 | awk '{print $2}'`
        total_docs=`cat $tmp | grep docs | grep total | head -3 | tail -1 | awk '{print $2}'`
        bytes=`cat $tmp |  grep bytes | head -3 | tail -1 | awk '{print $4}'`
        runtime=`cat $tmp |  grep sec | head -2 | tail -1 | awk '{print $2}'`
        bytes_sec=`cat $tmp |  grep sec | head -2 | tail -1 | awk '{print $4}'`
        docs_sec=`cat $tmp |  grep sec | head -2 | tail -1 | awk '{print $6}'`
        echo "mem_limit (MB),write_buffer (MB),docs collected,docs_mb,mhits,total docs,bytes,runtime,bytes/sec,docs/sec"
        echo "$mem_limit,$write_b,$docs_collected,$docs_mb,$mhits,$total_docs,$bytes,$runtime,$bytes_sec,$docs_sec"
    fi
    if [ "$var" = "merge" ]; then
        echo "[`date '+%H:%M:%S'`] Running MERGE (results written to $tmp)"
        cd $lz/sphinx && ./indexer.sh merge > $tmp
        mergetime=`cat $tmp | grep merged | tail -1 | awk '{print $3}'`
        echo "Merge took $mergetime seconds"
    fi
done


