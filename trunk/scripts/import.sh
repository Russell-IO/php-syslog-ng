#!/bin/bash

temp=/tmp/import_$$
u=`cat /path_to_logzilla/scripts/sql/lzmy.cnf | grep user | sed 's/user[[:space:]]=[[:space:]]//g'`
p=`cat /path_to_logzilla/scripts/sql/lzmy.cnf | grep password | sed 's/password[[:space:]]=[[:space:]]//g'`
d=`cat /path_to_logzilla/scripts/sql/lzmy.cnf | grep database | sed 's/database[[:space:]]=[[:space:]]//g'`
h=`cat /path_to_logzilla/scripts/sql/lzmy.cnf | grep host | sed 's/host[[:space:]]=[[:space:]]//g'`
o=`cat /path_to_logzilla/scripts/sql/lzmy.cnf | grep port | sed 's/port[[:space:]]=[[:space:]]//g'`
export_path=$( mysql -h $h -P $o -u $u --password=$p $d -B -N -e "SELECT value from settings WHERE name='ARCHIVE_PATH';" )
count=$( mysql -h $h -P $o -u $u --password=$p $d -B -N -e "SELECT records from archives WHERE archive='dumpfile_$1.txt';" )
date=$(date +%c)

filename="$export_path/dumpfile_$1.txt.gz"
# echo $filename
if [ ! -s "$filename" ]; then
    echo "$filename not found or is empty."
    exit  ;
fi
mkdir $temp
cd $temp
echo "Starting import of $count records on $date"
echo "Decompressing $filename and splitting into chunks..."

# Split into 25k records at a time
gzip -d -c $filename | split -a 4 -d -l 25000 - logs.

echo "Loading files into the database, please wait..."

i=1
n=`ls $temp/logs* | wc -l`
for x in $( ls $temp/logs* );
do  
  sed  "s/^/call import(/" <$x | sed "s/$/);/"| mysql -h $h -P $o -u $u --password=$p $d 
  RETVAL=$?
  [ $RETVAL -eq 0 ] && echo "Loaded chunk $i of $n"
  [ $RETVAL -ne 0 ] && echo "Failed to load chunk $i of $n"
  
  # wait after every 100k records so mysql can so some other stuff too..
  check=$((i%5))
  [ $check -eq 0 ] && echo "Sleeping for 1 second to give MySQL a chance to breathe..." &&  sleep 1
  i=`expr $i + 1`
done
rm -rf $temp
echo "Indexing new events..."
( cd /path_to_logzilla/sphinx && bin/indexer idx_logs --rotate)
#rm -f /path_to_logzilla/exports/_import_$1.log
date=$(date +%c)
echo "Import Completed on $date" 
