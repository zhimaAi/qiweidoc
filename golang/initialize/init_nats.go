package initialize

import (
	"fmt"
	"os"
	"session_archive/golang/define"
	"strings"

	"github.com/nats-io/nats.go"
)

func InitNats() error {
	define.NatsAddr = strings.TrimSpace(os.Getenv(`NATS_ADDR`))
	if len(define.NatsAddr) == 0 {
		define.NatsAddr = "nats://nats:4222"
	}

	var err error
	define.NatsConn, err = nats.Connect(define.NatsAddr)
	if err != nil {
		return err
	}

	return nil
}

func CloseNats() {
	fmt.Println("nats closing...")
	define.NatsConn.Close()
	fmt.Println("nats closed")
}
