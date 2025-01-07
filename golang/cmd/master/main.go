package main

import (
	"github.com/joho/godotenv"
	"log"
	"os"
	"os/signal"
	"session_archive/golang/internal/master"
	"syscall"
)

func main() {
	// 加载.env文件
	if err := godotenv.Load(); err != nil {
		panic(err)
	}

	// 初始化数据库
	master.InitPostgres()

	// 初始化nats
	master.InitNats()

	// 初始化模块
	master.InitModules()

	var errCh = make(chan error)

	// 启动代理服务
	go func() {
		if err := master.StartHttp(); err != nil {
			errCh <- err
		}
	}()

	sigCh := make(chan os.Signal, 1)
	signal.Notify(sigCh, os.Interrupt, syscall.SIGTERM, syscall.SIGINT)

	select {
	case err := <-errCh:
		log.Printf("服务出错了: %v", err)
		master.StopHttp()
		master.StopAllModules()
	case <-sigCh:
		log.Printf("收到退出信号...")
		master.StopHttp()
		master.StopAllModules()
	}

	close(errCh)
}
