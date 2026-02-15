#!/bin/bash

echo "SELECT concat ('drop table if exists system.', table, ' sync;') FROM system.parts WHERE active = 1 and table like '%log%' and database = 'system' GROUP BY table;" | clickhouse-client --ask -u default

exit 0
