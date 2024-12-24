-- pg_stat_statements 扩展，用于SQL性能监控
CREATE EXTENSION IF NOT EXISTS pg_stat_statements;

-- pg_prewarm 扩展，用于把表数据预先加载到内存提升性能
CREATE EXTENSION IF NOT EXISTS pg_prewarm;

-- pg_bigm 扩展，用于全文检索
CREATE EXTENSION IF NOT EXISTS pg_bigm;

-- roaringbitmap 扩展，用于位图索引
CREATE EXTENSION IF NOT EXISTS roaringbitmap;

-- timescaledb 扩展，用于时序数据
CREATE EXTENSION IF NOT EXISTS timescaledb;

-- pg_cron 扩展，用于数据库内部任务调度
CREATE EXTENSION IF NOT EXISTS pg_cron;

-- pg_buffercache 扩展，用于查看共享缓冲区中缓存的数据，帮助分析数据库缓存的利用情况
CREATE EXTENSION IF NOT EXISTS pg_buffercache;

-- 设置时区
SET timezone TO 'Asia/Shanghai';
