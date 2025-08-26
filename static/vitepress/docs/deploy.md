# 部署指导

## 一，准备工作

- 联网的 x86-64 架构的 Linux 服务器一台（示例：ubuntu 24.04 LTS 64 bit），配置不低于 2 核 4 GB（如果会话聊天内容包含大量文件，还需要有较大的磁盘空间, 或者配置云存储）
- 已经备案的域名，且备案主体与当前企业主体相同或有关联关系的域名，详情参考[企微官方配置指引](https://open.work.weixin.qq.com/wwopen/common/readDocument/40754)
- 已开通企业微信
## 二，服务部署

#### 1，安装 docker

```shell
curl -fsSL https://get.docker.com -o get-docker.sh
sudo DOWNLOAD_URL=https://mirrors.ustc.edu.cn/docker-ce sh get-docker.sh
```

#### 2，下载最新代码

```shell
git clone https://gitee.com/zmxkf/qiweidoc.git
cd qiweidoc
cp .env.prod .env
```

#### 3，运行

切换到 docker 目录：

```shell
cd docker
```

创建 compose 项目配置文件：

```
touch .env
```

编辑 .env 文件

```shell
COMPOSE_PROJECT_NAME=qiweidoc
COMPOSE_FILE=docker-compose-prod.yml

# 如果需要自动配置 https 证书，请配置如下
# ACME_DOMAINS=zhimahuihua.com,demo.zhimahuihua.com
# ACME_EMAIL=shellphy@2bai.com.cn
```

启动项目：

```shell
docker-compose up -d
```

#### 4，配置 nginx（可选）

容器默认会监听服务器的 80 端口 和 443 端口，如果你的服务器上有 nginx 而且也监听了 80 端口和 443 端口，可能会出现端口冲突，应该通过环境变量来修改默认端口号，在 .env 文件里添加如下配置：

```shell
EXTERNAL_HTTP_PORT=8080
EXTERNAL_HTTPS_PORT=4443
```

再运行：

```shell
docker-compose up -d
```

nginx 配置示例如下：

```nginx
server {
  listen                  80;
  server_name             example.com;

  location / {
      proxy_pass http://127.0.0.1:8080;
      proxy_set_header Host $host;
	  proxy_set_header X-Real-IP $remote_addr;
      proxy_set_header X-Forwarded-For $remote_addr;
      proxy_set_header X-Forwarded-Port $server_port;
      proxy_set_header X-Forwarded-Host $host;
      proxy_set_header X-Forwarded-Proto $scheme;
      proxy_read_timeout 1200s;
    }
}
```

#### 5,更新

```shell
git pull
cd docker
docker compose pull
docker compose restart
```
