#/bin/bash

# Apparmor users:
# This script WILL NOT run unless you unblock MySQL read/writes in Apparmor first.
# See here: http://nms.gdd.net/index.php/Install_Guide_for_LogZilla_v3.x#Apparmor_Blocking
u=`cat /path_to_logzilla/scripts/sql/lzmy.cnf | grep user | sed 's/user[[:space:]]=[[:space:]]//g'`
p=`cat /path_to_logzilla/scripts/sql/lzmy.cnf | grep password | sed 's/password[[:space:]]=[[:space:]]//g'`
d=`cat /path_to_logzilla/scripts/sql/lzmy.cnf | grep database | sed 's/database[[:space:]]=[[:space:]]//g'`
h=`cat /path_to_logzilla/scripts/sql/lzmy.cnf | grep host | sed 's/host[[:space:]]=[[:space:]]//g'`
o=`cat /path_to_logzilla/scripts/sql/lzmy.cnf | grep port | sed 's/port[[:space:]]=[[:space:]]//g'`
mysql -h $h -P $o --user $u --password=$p -e "call export;" $d
r=`echo "SELECT value FROM settings where name='ARCHIVE_BACKUP'"|mysql $d -s -h $h -P $o --user $u --password=$p`
cd /path_to_logzilla/scripts
for i in $( ls /path_to_logzilla/exports/*.txt );
do 
    gzip -f $i
    if [ ! -z "$r" ]; then
        echo $r >backup
	sh backup $i
    fi
done 

