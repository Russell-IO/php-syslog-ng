#!/bin/sh

#
# checkmem.sh
#
# Developed by Clayton Dukes <cdukes@logzilla.pro>
# Copyright (c) 2012 logzilla.pro
# All rights reserved.
#
# Changelog:
# 2012-03-06 - created
#

# Simple script used to verify that your server has enough ram for Sphinx to function properly

summary=`/path_to_logzilla/scripts/tools/summary > /tmp/mem.tmp`
total=`cat /tmp/mem.tmp | grep Total | awk '{print $3}'`
free=`cat /tmp/mem.tmp | grep Free | awk '{print $3}'`
spmem=`(cd /path_to_logzilla/sphinx/data && du  -hsc *.spa *.spk *.spi *.sph *.ram | grep total | awk '{print $1}')`
echo "Total Mem = $total"
echo "Free Mem  = $free"
echo "Memory used by Sphinx indexes = $spmem"
