# 企微动态链接库
LIBRARY_PATH=$(shell pwd)/golang/plugins/wxfinance/linux
PROCESS_NAME=master

# app
.PHONY: app
app:
	go version
	go mod tidy
	set GOARCH=amd64 && set GOOS=linux && go build -o golang/build/app -ldflags "-s -w" golang/cmd/app/main.go

# master
.PHONY: master
master:
	go version
	go mod tidy
	set GOARCH=amd64 && set GOOS=linux && go build -o golang/build/master -ldflags "-s -w" golang/cmd/master/*.go

.PHONY: php
php:
	COMPOSER_ALLOW_SUPERUSER=1 composer --working-dir php install

.PHONY: docs
docs:
	npm install --prefix ./static/vitepress/ && \
    npm --prefix ./static/vitepress/ run docs:build

.PHONY: modules
modules:
	npm install --prefix ./static/management/ && \
    npm --prefix ./static/management/ run build:modules

.PHONY: build
build:
	make app
	make master
	make php
	make docs
	make modules

.PHONY: exec
exec:
	@PID=$$(pgrep $(PROCESS_NAME)); \
    if [ -n "$$PID" ]; then \
        echo "Stopping process $(PROCESS_NAME) (PID: $$PID)"; \
        kill $$PID || true; \
    else \
        echo "No running $(PROCESS_NAME) process found"; \
    fi; \
    LD_LIBRARY_PATH=$(LIBRARY_PATH) ./golang/build/$(PROCESS_NAME)
