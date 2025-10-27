FROM golang:1.25.3-trixie AS builder
WORKDIR /app

RUN go env -w GOPROXY=https://goproxy.cn,direct
COPY go.mod go.sum ./
RUN go mod download

COPY golang/cmd/wxfinance .
COPY .env.prod .env
RUN CGO_ENABLED=1 GOOS=linux go build -a -installsuffix cgo -o main *.go

ENV LD_LIBRARY_PATH=/app/linux
CMD ["/app/main"]
