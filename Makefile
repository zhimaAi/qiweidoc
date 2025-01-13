.PHONY: master
master:
	go version
	go mod tidy
	GOARCH=amd64 GOOS=linux go build -o golang/build/master -ldflags "-s -w" golang/cmd/master/main.go

.PHONY: exec
exec:
	-pkill -f golang
	-pkill -f php
	./golang/build/master
