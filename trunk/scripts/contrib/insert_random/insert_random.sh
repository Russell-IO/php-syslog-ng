#!/bin/sh
count=1
while [ $count -le 10 ]; do
rand=`perl -e 'print int(rand(800));'`
echo "$count"
echo "insert into logs (host,facility,severity,program,msg,counter,mne,fo,lo)  select 'host$rand',facility,severity,program,msg,counter,mne,'2011-06-$count' + interval rand()+24 hour,'2011-06-$count' + interval rand()+24 hour from logs limit $rand" | mysql -usyslogadmin -psyslogadmin syslog
    count=`expr $count + 1`
done
