#!/bin/bash

# 拉代码
git clone https://gitlab.zmwk.cn/shellphy/session_archive.git .

# 构建前端
npm --prefix ./static/management/ install
npm --prefix ./static/management/ run build

# 构建php
composer --working-dir php update
composer --working-dir php yii cycle/schema/rebuild

# 构建golang
make build
