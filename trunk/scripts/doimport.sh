#!/bin/bash
# cd: only passing the date string with no dashes now, e.g: 20120131
test -f /path_to_logzilla/exports/_import_$1.log && exit 1
nohup /path_to_logzilla/scripts/import.sh $1 >/path_to_logzilla/exports/_import_$1.log &
