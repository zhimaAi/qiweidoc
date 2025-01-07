package main

import (
	"context"
	"encoding/json"
	"github.com/joho/godotenv"
	"github.com/nats-io/nats.go"
	"github.com/nats-io/nats.go/micro"
	"log"
	"os"
	"strings"
)

func main() {
	if err := godotenv.Load(); err != nil {
		panic(err)
	}
	ctx := context.Background()

	natsAddr := strings.TrimSpace(os.Getenv(`NATS_ADDR`))
	if len(natsAddr) == 0 {
		natsAddr = "nats://nats:4222"
	}

	nc, err := nats.Connect(natsAddr)
	if err != nil {
		panic(err)
	}

	log.Println("started")
	srv, err := micro.AddService(nc, micro.Config{
		Name:        "httpbatch",
		Version:     "1.0.0",
		Description: "封装http批量请求",
	})
	if err != nil {
		panic(err)
	}
	group := srv.AddGroup("httpbatch")

	if err := group.AddEndpoint("Request", micro.HandlerFunc(request)); err != nil {
		panic(err)
	}

	<-ctx.Done()
}

func request(request micro.Request) {
	go func() {
		var input BatchHTTPInput
		log.Println("收到请求了")

		if err := json.Unmarshal(request.Data(), &input); err != nil {
			log.Println(err)
			return
		}

		res, err := Request(&input)
		if err != nil {
			log.Println(err)
			return
		}

		if err = request.RespondJSON(res); err != nil {
			log.Println(err)
		}
	}()
}
