#!/bin/bash
set -e

total_mem=$(awk '/MemTotal/ {print $2}' /proc/meminfo)
min_connections=100

# 总是执行配置优化
echo "Optimizing PostgresSQL configuration with timescaledb-tune..."
if [ "$total_mem" -lt 4000000 ]; then
    /root/go/bin/timescaledb-tune --quiet --yes --conf-path=/etc/postgresql/postgresql.conf --max-conns=$min_connections
else
    /root/go/bin/timescaledb-tune --quiet --yes --conf-path=/etc/postgresql/postgresql.conf
fi

# 执行原始的 entrypoint
exec docker-entrypoint.sh "$@"
