FROM golang:1.23-bookworm AS builder
WORKDIR /app

COPY go.mod go.sum ./
RUN go mod download

COPY golang/cmd/httpbatch .
COPY .env.prod .env
RUN CGO_ENABLED=1 GOOS=linux go build -a -installsuffix cgo -o main *.go

CMD ["/app/main"]
