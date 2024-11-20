package golang

import (
	"fmt"
	"log"
	"os"
	"os/signal"
	"session_archive/golang/pkg/roadrunner"
	"session_archive/golang/plugins/common"
	"session_archive/golang/plugins/httpbatch"
	"session_archive/golang/plugins/minio"
	"session_archive/golang/plugins/proxy"
	"session_archive/golang/plugins/wxfinance"
	"strings"
	"sync"
	"syscall"
)

func Run(cfgFile string) {
	// 初始化roadrunner
	rr, err := getRoadrunner(cfgFile)
	if err != nil {
		log.Fatalf("初始化roadrunner失败: %v", err)
	}

	// 检查RPC地址
	rpcAddress := rr.Config().Get(`rpc.listen`).(string)
	if len(rpcAddress) == 0 {
		log.Fatal("rpc地址不能为空")
	}
	rpcAddress = strings.TrimPrefix(rpcAddress, "tcp://")

	var wg sync.WaitGroup

	// 启动roadrunner服务
	wg.Add(1)
	go func() {
		defer wg.Done()
		if err := rr.Serve(); err != nil {
			log.Printf("roadrunner服务错误: %v", err)
		}
	}()

	// 处理信号
	sigCh := make(chan os.Signal, 1)
	signal.Notify(sigCh, os.Interrupt, syscall.SIGTERM, syscall.SIGINT)
	<-sigCh

	log.Println("开始关闭服务...")

	// 停止roadrunner
	rr.Stop()
	log.Printf("roadrunner服务正在停止...")

	// 等待服务完全停止
	wg.Wait()
	log.Printf("roadrunner服务已完全停止")
}

// getRoadrunner 初始化roadrunner实例
func getRoadrunner(cfgFile string) (*roadrunner.RR, error) {
	var overrides []string

	// 环境配置
	isProd := os.Getenv(`MODE`) != `development`
	if isProd {
		overrides = append(overrides, `logs.mode=production`)
		overrides = append(overrides, `logs.level=false`)
		overrides = append(overrides, `http.pool.debug=false`)
		overrides = append(overrides, `jobs.pool.debug=false`)
	} else {
		overrides = append(overrides, `logs.mode=development`)
		overrides = append(overrides, `logs.level=debug`)
		overrides = append(overrides, `http.pool.debug=true`)
		overrides = append(overrides, `jobs.pool.debug=true`)
	}

	// ACME证书配置
	acmeDomains := os.Getenv(`acme_domains`)
	acmeEmail := os.Getenv(`acme_email`)
	if len(acmeDomains) > 0 && len(acmeEmail) > 0 {
		overrides = append(overrides,
			`http.ssl.acme.domains=`+acmeDomains,
			`http.ssl.acme.email=`+acmeEmail,
			`http.ssl.acme.certs_dir=/etc/letsencrypt`,
			`http.ssl.acme.use_production_endpoint=`+fmt.Sprintf("%t", isProd),
		)
	}

	// 获取默认插件
	plugins := roadrunner.DefaultPluginsList()

	// 自定义插件
	plugins = append(plugins, &common.Plugin{})
	plugins = append(plugins, &proxy.Plugin{})
	plugins = append(plugins, &httpbatch.Plugin{})
	plugins = append(plugins, &minio.Plugin{})
	plugins = append(plugins, &wxfinance.Plugin{})

	return roadrunner.NewRR(cfgFile, overrides, plugins)
}
