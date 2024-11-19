# 企微动态链接库
LIBRARY_PATH=$(shell pwd)/golang/plugins/wxfinance/linux

.PHONY: run
run:
	LD_LIBRARY_PATH=$(LIBRARY_PATH) go run main.go

.PHONY: build
build:
	go version
	go mod tidy
	set GOARCH=amd64 && set GOOS=linux && LD_LIBRARY_PATH=$(LIBRARY_PATH) go build -o rr -ldflags "-s -w" main.go

.PHONY: build-mac
build-mac:
	go version
	go mod tidy
	GOARCH=amd64 GOOS=linux LD_LIBRARY_PATH=$(LIBRARY_PATH) go build -o rr -ldflags "-s -w" main.go

.PHONY: exec
exec:
	LD_LIBRARY_PATH=$(LIBRARY_PATH) ./rr
