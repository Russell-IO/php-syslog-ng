#!/bin/bash
u=`cat /path_to_logzilla/scripts/sql/lzmy.cnf | grep user | sed 's/user[[:space:]]=[[:space:]]//g'`
p=`cat /path_to_logzilla/scripts/sql/lzmy.cnf | grep password | sed 's/password[[:space:]]=[[:space:]]//g'`
d=`cat /path_to_logzilla/scripts/sql/lzmy.cnf | grep database | sed 's/database[[:space:]]=[[:space:]]//g'`
h=`cat /path_to_logzilla/scripts/sql/lzmy.cnf | grep host | sed 's/host[[:space:]]=[[:space:]]//g'`
o=`cat /path_to_logzilla/scripts/sql/lzmy.cnf | grep port | sed 's/port[[:space:]]=[[:space:]]//g'`
export_path=$( mysql -h $h -P $o -u $u --password=$p $d -B -N -e "SELECT value from settings WHERE name='ARCHIVE_PATH';" )
r=`echo "SELECT value FROM settings where name='ARCHIVE_RESTORE'"|mysql $d -s -h $h -P $o --user $u --password=$p`
cd /path_to_logzilla/scripts
 if [ ! -z "$r" ]; then
	echo $r >restore
	sh restore $1 $export_path
 fi
rm -f /path_to_logzilla/exports/restore.running
echo "***all done***"
