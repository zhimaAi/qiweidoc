package main

import (
    "github.com/joho/godotenv"
    "log"
    "os"
    "os/signal"
    "syscall"
)

func main() {
	// 加载.env文件
	if err := godotenv.Load(); err != nil {
		panic(err)
	}

	// 初始化数据库连接
	if err := initDb(); err != nil {
		panic(err)
	}

	var errCh = make(chan error)

	// 启动代理服务
	go func() {
		if err := startFiberProxy(); err != nil {
			errCh <- err
		}
	}()

	// 启动插件容器
	go func() {
		if err := startEndureContainer(); err != nil {
			errCh <- err
		}
	}()

	sigCh := make(chan os.Signal, 1)
	signal.Notify(sigCh, os.Interrupt, syscall.SIGTERM, syscall.SIGINT)

	select {
	case err := <-errCh:
		log.Printf("服务出错了: %v", err)
		defer stopFiberProxy()
		defer stopEndureContainer()
		defer closeDb()
	case <-sigCh:
		log.Printf("收到退出信号...")
		defer stopFiberProxy()
		defer stopEndureContainer()
		defer closeDb()
	}

	close(errCh)
}
