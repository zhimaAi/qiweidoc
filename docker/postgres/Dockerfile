FROM postgres:17.2-bookworm

RUN apt-get update && \
  apt-get install -y --no-install-recommends \
    curl \
    make \
    cmake \
    git \
    gcc \
    pkg-config \
    libssl-dev \
    postgresql-server-dev-17 \
    libcurl4-openssl-dev \
    ca-certificates \
    golang
RUN git config --global http.sslVerify false;

# 编译安装roaringbitmap插件
WORKDIR /tmp/
RUN git clone --depth 1 --branch v0.5.4 https://github.com/ChenHuajun/pg_roaringbitmap.git && \
    cd /tmp/pg_roaringbitmap && \
    make && \
    make install

# 编译安装pg_bigm插件
WORKDIR /tmp/
RUN git clone --depth 1 --branch v1.2-20240606 https://github.com/pgbigm/pg_bigm.git && \
    cd /tmp/pg_bigm && \
    make USE_PGXS=1 PG_CONFIG=/usr/bin/pg_config && \
    make USE_PGXS=1 PG_CONFIG=/usr/bin/pg_config install

# 编译安装pg_cron插件
WORKDIR /tmp/
RUN git clone --depth 1 --branch v1.6.4 https://github.com/citusdata/pg_cron.git && \
    cd /tmp/pg_cron && \
    make && \
    make install

# 编译安装timescaledb插件
WORKDIR /tmp/
RUN git clone --depth 1 --branch 2.17.2 https://github.com/timescale/timescaledb.git && \
    cd timescaledb && \
    ./bootstrap && \
    cd build && \
    make && \
    make install

# 安装 timescaledb-tune
RUN go install github.com/timescale/timescaledb-tune/cmd/timescaledb-tune@latest

#清理无用数据和软件包避免镜像过大
RUN apt-get remove -y git gcc pkg-config && \
    apt-get autoremove -y && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# 添加默认配置文件
COPY docker/postgres/postgres.conf /etc/postgresql/postgresql.conf

# 添加初始化脚本
COPY docker/postgres/00-init-db.sql /docker-entrypoint-initdb.d/00-init-schemas.sql

# 创建自定义 entrypoint 脚本
COPY docker/postgres/entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
CMD ["postgres"]
