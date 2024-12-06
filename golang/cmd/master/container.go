package main

import (
	"fmt"
	"github.com/roadrunner-server/app-logger/v5"
	"github.com/roadrunner-server/config/v5"
	"github.com/roadrunner-server/endure/v2"
	"github.com/roadrunner-server/rpc/v5"
	"github.com/roadrunner-server/service/v5"
	"github.com/shellphy/logger/v5"
	"log"
	"log/slog"
	"os"
	"session_archive/golang/plugins/common"
	"session_archive/golang/plugins/httpbatch"
	"session_archive/golang/plugins/minio"
	"session_archive/golang/plugins/module"
	"session_archive/golang/plugins/wxfinance"
)

var endureApp *endure.Endure
var configFile = "golang/cmd/master/config.yml"

func startEndureContainer() error {
	// 定义需要用到的插件
	plugins := []any{
		&logger.Plugin{},
		&app.Plugin{},
		&rpc.Plugin{},
		&service.Plugin{},

		// 自定义插件
		&common.Plugin{},
		&httpbatch.Plugin{},
		&minio.Plugin{},
		&wxfinance.Plugin{},
		&module.Plugin{},
	}

	var overrides []string

	natsAddr := os.Getenv("NATS_ADDR")
	pgHost := os.Getenv("DB_HOST")
	pgPort := os.Getenv("DB_PORT")
	pgDatabase := os.Getenv("DB_DATABASE")
	pgUsername := os.Getenv("DB_USERNAME")
	pgPassword := os.Getenv("DB_PASSWORD")
	if len(natsAddr) > 0 {
		overrides = append(overrides, fmt.Sprintf("nats.listen=%s", natsAddr))
	}
	if len(pgHost) > 0 {
		overrides = append(overrides, fmt.Sprintf("postgres.host=%s", pgHost))
	}
	if len(pgPort) > 0 {
		overrides = append(overrides, fmt.Sprintf("postgres.port=%s", pgPort))
	}
	if len(pgDatabase) > 0 {
		overrides = append(overrides, fmt.Sprintf("postgres.username=%s", pgDatabase))
	}
	if len(pgUsername) > 0 {
		overrides = append(overrides, fmt.Sprintf("postgres.username=%s", pgUsername))
	}
	if len(pgPassword) > 0 {
		overrides = append(overrides, fmt.Sprintf("postgres.password=%s", pgPassword))
	}

	// 开发环境特殊配置
	if os.Getenv(`MODE`) == `dev` {
		overrides = append(overrides, "logs.mode=development")
		overrides = append(overrides, "logs.level=info")
	}

	// 创建基础配置插件
	cfg := &config.Plugin{
		Path:    configFile,
		Timeout: 30,
		Version: "2024.2.1",
		Flags:   overrides,
	}

	// 初始化并运行容器
	endureApp = endure.New(slog.LevelInfo)
	if err := endureApp.RegisterAll(append(plugins, cfg)...); err != nil {
		return err
	}
	if err := endureApp.Init(); err != nil {
		return err
	}
	errCh, err := endureApp.Serve()
	if err != nil {
		return err
	}
	go func() {
		for e := range errCh {
			log.Printf("RoadRunner service error: %v", e)
		}
	}()

	return nil
}

func stopEndureContainer() {
	if err := endureApp.Stop(); err != nil {
		panic(err)
	}
}
