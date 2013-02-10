#!/bin/sh
# Simple script used to wait for MySQL prior to starting Sphinx
# Also sets console message for VMs
# This script is called from /etc/rc.local during bootup
for i in 1 2 3 4 5 6; do
	if [ -S /var/run/mysql/mysql.sock ]; then
	break
	else
		sleep 1
		echo -n "."
	fi
done
(cd /path_to_logzilla/sphinx && bin/searchd)
(cd /path_to_logzilla/scripts/VM && ./banner.pl)
