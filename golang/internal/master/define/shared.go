package define

import (
	"github.com/gofiber/fiber/v2"
	"github.com/jackc/pgx/v5/pgxpool"
	"github.com/nats-io/nats.go"
	"github.com/roadrunner-server/endure/v2"
	"sync"
)

type ModuleInfo struct {
	Name      string
	Version   string
	Plugins   []string
	RR        *endure.Endure
	StartedAt string
	HttpPort  int
	RpcPort   int
}

type ModuleManifest struct {
	Name    string   `json:"name"`
	Version string   `json:"version"`
	Plugins []string `json:"plugins"`
}

// ModuleRespInfo http接口里返回的结构体
type ModuleRespInfo struct {
	Name      string `json:"name"`
	Version   string `json:"version"`
	Paused    bool   `json:"paused"`
	StartedAt string `json:"started_at"`
}

// 全局变量
var (
	MainHost    = "zhimahuihua.com"
	MinioUrl    = "http://minio:9000"
	ModuleList  = make(map[string]ModuleInfo)
	FiberApp    *fiber.App
	PgPool      *pgxpool.Pool
	NatsConn    *nats.Conn
	ModuleMutex sync.Mutex
)
