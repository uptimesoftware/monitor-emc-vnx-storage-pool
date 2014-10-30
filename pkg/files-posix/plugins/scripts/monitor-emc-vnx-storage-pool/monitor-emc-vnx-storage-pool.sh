#!/bin/sh
inst=`grep ^inst /etc/init.d/uptime_httpd | cut -d= -f2`
MIBDIRS=$inst/mibs
export MIBDIRS

/usr/local/uptime/apache/bin/php monitor-emc-vnx-storage-pool.php
