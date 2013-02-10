#!/bin/sh

#
# indexer.sh
#
# Developed by Clayton Dukes <cdukes@logzilla.pro>
# Copyright (c) 2010 gdd.net
# Licensed under terms of GNU General Public License.
# All rights reserved.
#
# Changelog:
# 2010-04-05 - created
#

################################################################
# This script is used to update the Sphinx indexes
# Several variables are needed to run, some of which are pulled
# from config.php
################################################################

ulimit -Hn 4096
ulimit -Sn 4096

# -------------------------------------------
# Must wait for daily synDB cleanup to complete
# -------------------------------------------
PID=`ps aux | grep "syncDB" | grep -v grep | awk '{print $2}'`
for pid in "$PID"; do
    while kill -0 "$pid" >/dev/null 2>&1; do
        echo "Waiting for the syncDB process on pid $pid to finish"
        sleep 5
    done
done
# -------------------------------------------
# Check to see if we are already running.
# -------------------------------------------
PID=`ps aux | grep "run_indexer.sh" | grep -v grep | awk '{print $2}'`
if [ "$PID" ]; then
    echo "LogZilla indexer is already running on PID $PID" >&2
    exit 1
fi
./run_indexer.sh $1
