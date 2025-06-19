package main

import (
    "context"
    "encoding/json"
    "fmt"
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
        Name:        "wxfinance",
        Version:     "1.0.0",
        Description: "封装企微会话存档接口",
    })
    if err != nil {
        panic(err)
    }
    group := srv.AddGroup("wxfinance")

    // 拉消息
    if err = group.AddEndpoint("FetchData", micro.HandlerFunc(fetchData)); err != nil {
        panic(err)
    }

    // 下载文件
    if err = group.AddEndpoint("FetchAndStreamMediaData", micro.HandlerFunc(fetchAndStreamMediaData)); err != nil {
        panic(err)
    }

    <-ctx.Done()
}

func fetchData(request micro.Request) {
    var input FetchDataRequest
    if err := json.Unmarshal(request.Data(), &input); err != nil {
        log.Println(err)
        return
    }

    res, err := FetchData(&input)
    if err != nil {
        log.Println(err)
        return
    }

    if err = request.RespondJSON(res); err != nil {
        log.Println(err)
    }
}

func fetchAndStreamMediaData(request micro.Request) {
    go func() {
        var input FetchMediaDataRequest
        if err := json.Unmarshal(request.Data(), &input); err != nil {
            log.Println(err)
        }

        res, err := FetchAndStreamMediaData(&input)
        if err != nil {
            log.Println(err)
            return
        }

        log.Println(res)
        if err = request.RespondJSON(res); err != nil {
            log.Println(err)
        }
    }()
}
