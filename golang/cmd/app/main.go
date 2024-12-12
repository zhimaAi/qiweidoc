package main

import (
	"fmt"
	"log/slog"
	"os"
	"os/signal"
	"session_archive/golang/define"
	"session_archive/golang/initialize"
	"session_archive/golang/pkg/nats_util"
	"session_archive/golang/plugins/common"
	"session_archive/golang/plugins/cron"
	"session_archive/golang/plugins/httpbatch"
	"session_archive/golang/plugins/minio"
	"session_archive/golang/plugins/wxfinance"
	"strings"
	"syscall"

	"github.com/joho/godotenv"
	"github.com/roadrunner-server/config/v5"
	"github.com/roadrunner-server/endure/v2"
	"github.com/roadrunner-server/gzip/v5"
	"github.com/roadrunner-server/headers/v5"
	httpPlugin "github.com/roadrunner-server/http/v5"
	"github.com/roadrunner-server/jobs/v5"
	"github.com/roadrunner-server/nats/v5"
	"github.com/roadrunner-server/rpc/v5"
	"github.com/roadrunner-server/server/v5"
	"github.com/roadrunner-server/service/v5"
	"github.com/roadrunner-server/static/v5"
	"github.com/shellphy/logger/v5"
	"github.com/spf13/cast"
)

var (
	endureApp  *endure.Endure
	configFile = "golang/cmd/app/config.yml"
)

func startEndureApp() error {

	name := strings.TrimSpace(os.Getenv(`MODULE_NAME`))
	version := strings.TrimSpace(os.Getenv(`MODULE_VERSION`))
	description := strings.TrimSpace(os.Getenv(`MODULE_DESCRIPTION`))
	httpPort := strings.TrimSpace(os.Getenv(`MODULE_HTTP_PORT`))
	metadata := map[string]string{
		"type": "http",
		"port": httpPort,
	}

	define.ModuleName = name
	define.HttpPort = cast.ToUint(os.Getenv(`MODULE_HTTP_PORT`))
	define.RpcPort = cast.ToUint(os.Getenv(`MODULE_RPC_PORT`))

	// 向nats注册服务
	if err := nats_util.RegisterNatsService(define.NatsConn, name, version, description, metadata); err != nil {
		panic(err)
	}

	// 定义需要用到的插件
	plugins := []any{
		&logger.Plugin{},
		&rpc.Plugin{},
		&server.Plugin{},
		&service.Plugin{},

		// http
		&httpPlugin.Plugin{},
		&static.Plugin{},
		&headers.Plugin{},
		&gzip.Plugin{},

		// jobs
		&jobs.Plugin{},
		&nats.Plugin{},

		// 自定义插件
		&common.Plugin{},
		&httpbatch.Plugin{},
		&minio.Plugin{},
		&wxfinance.Plugin{},
		&cron.Plugin{},
	}

	var overrides []string

	// rpc配置
	overrides = append(overrides, fmt.Sprintf(`rpc.listen=tcp://127.0.0.1:%d`, define.RpcPort))

	// 添加php worker环境变量
	overrides = append(overrides, fmt.Sprintf(`server.env[0]=MODULE_NAME=%s`, name))

	// 覆盖http配置
	overrides = append(overrides, fmt.Sprintf(`http.pool.command=php php/yii %s http`, name))
	overrides = append(overrides, fmt.Sprintf(`http.address=0.0.0.0:%d`, define.HttpPort))
	staticDir := strings.TrimSpace(os.Getenv(`MODULE_STATIC_DIR`))
	if len(staticDir) > 0 {
		overrides = append(overrides, fmt.Sprintf(`http.static.dir=%s`, staticDir))
	}

	// 覆盖service配置
	overrides = append(overrides, fmt.Sprintf(`service.init-module.env[0]=MODULE_NAME=%s`, name))

	// 覆盖jobs配置
	overrides = append(overrides, fmt.Sprintf(`nats.addr=%s`, define.NatsAddr))
	consumeList := strings.TrimSpace(os.Getenv(`CONSUME_LIST`))
	if len(consumeList) > 0 {
		overrides = append(overrides, fmt.Sprintf(`jobs.pool.command=php php/yii %s jobs`, name))
		overrides = append(overrides, `jobs.pool.supervisor.max_worker_memory=128`)
		overrides = append(overrides, `jobs.pool.supervisor.ttl=300s`)
		overrides = append(overrides, `jobs.pool.supervisor.idle_ttl=5s`)
		workerNum := 0
		var fullConsumeNameList []string
		for _, consume := range strings.Split(consumeList, `,`) {
			deleteSteamOnStop := cast.ToString(cast.ToBool(os.Getenv(`CONSUME_` + strings.ToUpper(consume) + `_DELETE`)))
			prefetchCount := cast.ToInt(os.Getenv(`CONSUME_` + strings.ToUpper(consume) + `_COUNT`))
			fullConsumeName := name + `_` + consume
			fullConsumeNameList = append(fullConsumeNameList, fullConsumeName)
			if prefetchCount > 0 {
				overrides = append(overrides, fmt.Sprintf(`jobs.pipelines.%s.config.delete_stream_on_stop=%s`, fullConsumeName, deleteSteamOnStop))
				overrides = append(overrides, fmt.Sprintf(`jobs.pipelines.%s.config.delete_after_ack=true`, fullConsumeName))
				overrides = append(overrides, fmt.Sprintf(`jobs.pipelines.%s.driver=nats`, fullConsumeName))
				overrides = append(overrides, fmt.Sprintf(`jobs.pipelines.%s.config.subject=%s`, fullConsumeName, fullConsumeName))
				overrides = append(overrides, fmt.Sprintf(`jobs.pipelines.%s.config.stream=%s`, fullConsumeName, fullConsumeName))
				overrides = append(overrides, fmt.Sprintf(`jobs.pipelines.%s.config.prefetch=%d`, fullConsumeName, prefetchCount))
				workerNum += prefetchCount
			}
		}

		overrides = append(overrides, fmt.Sprintf(`jobs.consume=%s`, strings.Join(fullConsumeNameList, ",")))

		if workerNum == 0 {
			workerNum = 1
		}
		overrides = append(overrides, fmt.Sprintf(`jobs.pool.num_workers=%d`, workerNum))
	}

	// 开发环境特殊配置
	if os.Getenv(`MODE`) == `dev` {
		overrides = append(overrides, "logs.mode=development")
		overrides = append(overrides, "logs.level=info")
		overrides = append(overrides, "http.pool.debug=true")
		overrides = append(overrides, "tcp.pool.debug=true")
		overrides = append(overrides, "jobs.pool.debug=true")
	}

	// 创建基础配置插件
	cfg := &config.Plugin{
		Path:    configFile,
		Timeout: 30,
		Version: "2024.2.1",
		Flags:   overrides,
	}

	// 创建并运行容器
	endureApp = endure.New(slog.LevelInfo)
	if err := endureApp.RegisterAll(append(plugins, cfg)...); err != nil {
		panic(err)
	}
	if err := endureApp.Init(); err != nil {
		panic(err)
	}
	errCh, err := endureApp.Serve()
	if err != nil {
		return err
	}

	go func() {
		for e := range errCh {
			fmt.Println("RoadRunner service error:", e)
		}
	}()

	return nil
}

func stopEndureApp() {
	if err := endureApp.Stop(); err != nil {
		panic(err)
	}
}

func main() {
	// 加载.env文件
	err := godotenv.Load()
	if err != nil {
		panic(err)
	}

	// 初始化nats
	if err := initialize.InitNats(); err != nil {
		panic(err)
	}
	defer initialize.CloseNats()

	// 初始化数据库连接
	if err := initialize.InitDb(); err != nil {
		panic(err)
	}
	defer initialize.CloseDb()

	var errCh = make(chan error)

	// 启动插件容器
	go func() {
		if err := startEndureApp(); err != nil {
			errCh <- err
		}
	}()

	sigCh := make(chan os.Signal, 1)
	signal.Notify(sigCh, os.Interrupt, syscall.SIGTERM, syscall.SIGINT)

	select {
	case err := <-errCh:
		fmt.Println("服务出错了:", err)
		defer stopEndureApp()
	case <-sigCh:
		fmt.Println("收到退出信号...")
		defer stopEndureApp()
	}

	close(errCh)
}
