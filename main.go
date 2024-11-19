package main

import (
	"github.com/joho/godotenv"
	"session_archive/golang"
)

func main() {
	// 加载.env文件
	err := godotenv.Load("golang/.env")
	if err != nil {
		panic(err)
	}

	golang.Run("golang/config.yml")
}
