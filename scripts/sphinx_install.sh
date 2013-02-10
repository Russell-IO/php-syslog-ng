#!/bin/bash

killall searchd
cd ../sphinx/src
tar xzvf sphinx-0.9.9.tar.gz
cd sphinx-0.9.9
./configure --prefix `pwd`/../..
make && make install
cd ../..
./indexer.sh full
bin/searchd


