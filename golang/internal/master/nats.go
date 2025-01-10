package master

import (
	"context"
	"github.com/gofiber/fiber/v2/log"
	"github.com/joho/godotenv"
	"github.com/nats-io/nats.go"
	"github.com/nats-io/nats.go/jetstream"
	"go.uber.org/zap"
	"os"
	"session_archive/golang/internal/master/define"
	"strings"
	"time"
)

func InitNats() {
	if err := godotenv.Load(); err != nil {
		panic(err)
	}

	natsAddr := strings.TrimSpace(os.Getenv(`NATS_ADDR`))
	if len(natsAddr) == 0 {
		natsAddr = "nats://nats:4222"
	}
	nc, err := nats.Connect(natsAddr)
	if err != nil {
		panic(err)
	}
	define.NatsConn = nc
}

func moduleHasOpen(name string) bool {
	js, _ := jetstream.New(define.NatsConn)

	ctx, cancel := context.WithTimeout(context.Background(), 10*time.Second)
	defer cancel()

	kv, _ := js.CreateKeyValue(ctx, jetstream.KeyValueConfig{
		Bucket: "modules",
	})

	_, err := kv.Get(ctx, name)
	if err != nil {
		log.Warnf("name=%s, err: %v", name, err)
		return false
	}
	return true
}

func saveModuleOpen(name string) error {
	js, _ := jetstream.New(define.NatsConn)

	ctx, cancel := context.WithTimeout(context.Background(), 3*time.Second)
	defer cancel()

	kv, _ := js.CreateKeyValue(ctx, jetstream.KeyValueConfig{
		Bucket: "modules",
	})

	_, err := kv.PutString(ctx, name, "true")
	return err
}

func deleteModuleOpen(name string) error {
	js, _ := jetstream.New(define.NatsConn)

	ctx, cancel := context.WithTimeout(context.Background(), 3*time.Second)
	defer cancel()

	kv, _ := js.CreateKeyValue(ctx, jetstream.KeyValueConfig{
		Bucket: "modules",
	})

	return kv.Delete(ctx, name)
}

func reconnectHandler() func(*nats.Conn) {
	return func(conn *nats.Conn) {
		log.Warn("connection lost, reconnecting", zap.String("url", conn.ConnectedUrl()))
	}
}

func disconnectHandler() func(*nats.Conn, error) {
	return func(_ *nats.Conn, err error) {
		if err != nil {
			log.Error("nats disconnected", zap.Error(err))
			return
		}

		log.Info("nats disconnected")
	}
}
