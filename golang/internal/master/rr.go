package master

import (
	"fmt"
	"github.com/roadrunner-server/app-logger/v5"
	"github.com/roadrunner-server/config/v5"
	"github.com/roadrunner-server/endure/v2"
	"github.com/roadrunner-server/gzip/v5"
	"github.com/roadrunner-server/headers/v5"
	httpPlugin "github.com/roadrunner-server/http/v5"
	"github.com/roadrunner-server/jobs/v5"
	"github.com/roadrunner-server/nats/v5"
	rpcPlugin "github.com/roadrunner-server/rpc/v5"
	"github.com/roadrunner-server/server/v5"
	"github.com/roadrunner-server/service/v5"
	"github.com/roadrunner-server/static/v5"
	"log/slog"
	"os"
	"session_archive/golang/internal/master/rr_plugins/broadcast"
	"session_archive/golang/internal/master/rr_plugins/common"
	"session_archive/golang/internal/master/rr_plugins/cron"
	"session_archive/golang/internal/master/rr_plugins/logger"
	"session_archive/golang/internal/master/rr_plugins/micro"
)

func startRR(name string, rpcPort, httpPort int, needPlugins []string) (*endure.Endure, error) {
	natsAddr := os.Getenv(`NATS_ADDR`)
	if len(natsAddr) == 0 {
		natsAddr = "nats://nats:4222"
	}

	configFile := "golang/rr.yml"

	// 定义需要用到的插件
	plugins := []any{
		&logger.Plugin{},
		&rpcPlugin.Plugin{},
		&server.Plugin{},
		&service.Plugin{},
		&app.Plugin{},

		&common.Plugin{},
	}
	for _, p := range needPlugins {
		if p == "http" {
			plugins = append(plugins,
				&httpPlugin.Plugin{},
				&static.Plugin{},
				&headers.Plugin{},
				&gzip.Plugin{},
			)
		}
		if p == "jobs" {
			plugins = append(plugins,
				&jobs.Plugin{},
				&nats.Plugin{},
			)
		}
		if p == "cron" {
			plugins = append(plugins, &cron.Plugin{})
		}
		if p == "micro" {
			plugins = append(plugins, &micro.Plugin{})
		}
		if p == "broadcast" {
			plugins = append(plugins, &broadcast.Plugin{})
		}
	}

	var overrides []string

	// 日志配置
	overrides = append(overrides, fmt.Sprintf(`logs.prefix=[%s]`, name))

	// rpc配置
	overrides = append(overrides, fmt.Sprintf(`rpc.listen=tcp://127.0.0.1:%d`, rpcPort))

	// php脚本入口
	overrides = append(overrides, fmt.Sprintf(`server.command=php php/modules/%s/yii`, name))
	overrides = append(overrides, fmt.Sprintf(`server.env.RPC_ADDRESS=%d`, rpcPort))

	// 覆盖http配置(第三个参数仅作为进程运行时标识符,方便查看进程,roadrunner会忽略掉第三个参数所以php那边不会报错)
	overrides = append(overrides, fmt.Sprintf(`http.pool.command=php php/modules/%s/yii http`, name))
	overrides = append(overrides, fmt.Sprintf(`http.address=0.0.0.0:%d`, httpPort))
	overrides = append(overrides, fmt.Sprintf(`http.static.dir=php/modules/%s/public`, name))

	// 覆盖jobs配置(第三个参数仅作为进程运行时标识符,方便查看进程,roadrunner会忽略掉第三个参数所以php那边不会报错)
	overrides = append(overrides, fmt.Sprintf(`jobs.pool.command=php php/modules/%s/yii jobs`, name))

	// nats配置
	overrides = append(overrides, fmt.Sprintf(`nats.addr=%s`, natsAddr))

	// cron配置
	overrides = append(overrides, fmt.Sprintf(`cron.name=%s`, name))
	overrides = append(overrides, fmt.Sprintf(`cron.pool.command=php php/modules/%s/yii cron`, name))

	// micro配置
	overrides = append(overrides, fmt.Sprintf(`micro.name=%s`, name))
	overrides = append(overrides, fmt.Sprintf(`micro.pool.command=php php/modules/%s/yii micro`, name))

	// broadcast配置
	overrides = append(overrides, fmt.Sprintf(`broadcast.name=%s`, name))
	overrides = append(overrides, fmt.Sprintf(`broadcast.pool.command=php php/modules/%s/yii broadcast`, name))

	// 覆盖service配置
	overrides = append(overrides, fmt.Sprintf(`service.init-module.command=php php/modules/%s/yii init-module`, name))
	overrides = append(overrides, fmt.Sprintf(`service.init-module.env.RR_RPC=tcp://127.0.0.1:%d`, rpcPort))

	// 开发环境特殊配置
	if os.Getenv(`MODE`) == `dev` {
		overrides = append(overrides, "logs.mode=development")
		overrides = append(overrides, "http.pool.debug=true")
		overrides = append(overrides, "tcp.pool.debug=true")
		overrides = append(overrides, "jobs.pool.debug=true")
		overrides = append(overrides, "micro.pool.debug=true")
		overrides = append(overrides, "cron.pool.debug=true")
		overrides = append(overrides, "http.access_logs=false")
	}
	logLevel := os.Getenv(`LOG_LEVEL`)
	if len(logLevel) == 0 {
		logLevel = "info"
	}
	overrides = append(overrides, "logs.level="+logLevel)

	// 创建基础配置插件
	cfg := &config.Plugin{
		Path:    configFile,
		Timeout: 30,
		Version: "2024.3.1",
		Flags:   overrides,
	}

	// 创建并运行容器
	container := endure.New(slog.LevelInfo)
	if err := container.RegisterAll(append(plugins, cfg)...); err != nil {
		panic(err)
	}
	if err := container.Init(); err != nil {
		panic(err)
	}
	errCh, err := container.Serve()
	if err != nil {
		return nil, err
	}

	go func() {
		for e := range errCh {
			fmt.Println("RoadRunner service error:", e)
		}
	}()

	return container, nil
}
