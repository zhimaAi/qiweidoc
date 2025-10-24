.PHONY: build-wxfinance
build-wxfinance:
	CGO_ENABLED=1 GOOS=linux go build -a -installsuffix cgo -o main golang/cmd/wxfinance/*.go

.PHONY: run-wxfinance
run-wxfinance:
	LD_LIBRARY_PATH=/var/www/golang/cmd/wxfinance/linux /var/www/main

.PHONY: master
master:
	go version
	go mod tidy
	GOARCH=amd64 GOOS=linux go build -o golang/build/master -ldflags "-s -w" golang/cmd/master/main.go

.PHONY: master-win
master-win:
	go version
	go mod tidy
	set GOARCH=amd64 set GOOS=linux go build -o golang/build/master -ldflags "-s -w" golang/cmd/master/main.go

.PHONY: exec
exec:
	-pkill -f golang
	-pkill -f php
	./golang/build/master
