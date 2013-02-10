#!/bin/bash
test -f /path_to_logzilla/exports/restore.running && exit 1
nohup /path_to_logzilla/scripts/restore.sh $1 >/path_to_logzilla/exports/restore.running &
