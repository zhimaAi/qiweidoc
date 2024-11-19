#!/bin/bash
set -e

# 总是执行配置优化
echo "Optimizing PostgresSQL configuration with timescaledb-tune..."
/root/go/bin/timescaledb-tune --quiet --yes --conf-path=/etc/postgresql/postgresql.conf

# 执行原始的 entrypoint
exec docker-entrypoint.sh "$@"
