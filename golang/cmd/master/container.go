package main

import (
	"log"
	"log/slog"
	"os"

	"github.com/roadrunner-server/app-logger/v5"
	"github.com/roadrunner-server/config/v5"
	"github.com/roadrunner-server/endure/v2"
	"github.com/roadrunner-server/rpc/v5"
	"github.com/roadrunner-server/service/v5"
	"github.com/shellphy/logger/v5"
)

var endureApp *endure.Endure
var configFile = "golang/cmd/master/config.yml"

func startEndureContainer() error {
	// 定义需要用到的插件
	plugins := []any{
		&app.Plugin{},
		&logger.Plugin{},
		&rpc.Plugin{},
		&service.Plugin{},
	}

	var overrides []string

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
	log.Println("endure stopping...")
	if err := endureApp.Stop(); err != nil {
		log.Println(err)
		return
	}
	log.Println("endure stopped")
}
