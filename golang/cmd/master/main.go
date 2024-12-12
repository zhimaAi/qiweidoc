package main

import (
	"log"
	"os"
	"os/signal"
	"session_archive/golang/initialize"
	"syscall"

	"github.com/joho/godotenv"
)

func main() {
	// 加载.env文件
	if err := godotenv.Load(); err != nil {
		panic(err)
	}

	// 初始化nats
	if err := initialize.InitNats(); err != nil {
		panic(err)
	}
	defer initialize.CloseNats()

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
		stopFiberProxy()
		stopEndureContainer()
	case <-sigCh:
		log.Printf("收到退出信号...")
		stopFiberProxy()
		stopEndureContainer()
	}

	close(errCh)
}
