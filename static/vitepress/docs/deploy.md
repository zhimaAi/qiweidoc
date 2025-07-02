# 部署指导

## 一，准备工作

- 联网的 x86-64 架构的 Linux 服务器一台（示例：ubuntu 24.04 LTS 64 bit），配置不低于 2 核 4 GB（如果会话聊天内容包含大量文件，还需要有较大的磁盘空间）
- 已经备案的域名，且备案主体与当前企业主体相同或有关联关系的域名，详情参考[企微官方配置指引](https://open.work.weixin.qq.com/wwopen/common/readDocument/40754)
- 已开通企业微信
## 二，服务部署

#### 1，安装 docker

```shell
sudo curl -sSL https://get.docker.com/ | CHANNEL=stable sh
```

如果是境内服务器，可能会安装不了，请参考华为云提供的[安装方法](https://mirrors.huaweicloud.com/mirrorDetail/5ea14d84b58d16ef329c5c13?mirrorName=docker-ce&catalog=docker)。

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

创建 compose 项目文件：

```
touch .env
```

编辑 .env 文件

```shell
# 如果是新部署的就随便取一个名字,如果之前部署过并且需要保留之前的数据,那么参考下面的说明
COMPOSE_PROJECT_NAME=qiweidoc

COMPOSE_FILE=docker-compose-prod.yml

# 如果需要自动配置 https 证书，请配置如下
# ACME_DOMAINS=zhimahuihua.com,demo.zhimahuihua.com
# ACME_EMAIL=shellphy@2bai.com.cn
```

> 说明：如果之前是通过docker镜像直接启动的,运行命令：
> ```
> docker ps | grep main-1 | awk '{print $NF}' | awk -F'-' '{print $1}' 
> ```
> 可以得到原compose项目名，然后把该名称填充到 COMPOSE_PROJECT_NAME 中。
> 
> 比如之前部署过项目，运行命令：
> ```
> $ docker ps | grep main-1 | awk '{print $NF}' | awk -F'-' '{print $1}'
> zm_session_archive
> ```
> 那么 .env 中的 COMPOSE_PROJECT_NAME 应该填写 `zm_session_archive`
> 最后把旧的容器停止并删除: 
> ```shell
> docker compose down
> ```


再运行：

```shell
docker-compose up -d
```

#### 4，配置 nginx（可选）

容器默认会监听服务器的 80 端口 和 443 端口，如果你的服务器上有 nginx 而且也监听了 80 端口和 443 端口，可能会出现端口冲突，应该通过环境变量来修改默认端口号，如：

```shell
echo EXTERNAL_HTTP_PORT=8080 > .env
echo EXTERNAL_HTTPS_PORT=4443 >> .env
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

