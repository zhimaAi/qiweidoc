package main

import (
	"crypto/tls"
	"fmt"
	"github.com/gofiber/fiber/v2"
	"github.com/gofiber/fiber/v2/middleware/proxy"
	"golang.org/x/crypto/acme/autocert"
	"log"
	"net/url"
	"os"
	"session_archive/golang/plugins/module"
	"strings"
)

var (
	fiberApp *fiber.App
	mainHost = "zhimahuihua.com"
	minioUrl = "http://minio:9000"
)

func startFiberProxy() error {
	fiberApp = fiber.New(fiber.Config{DisableStartupMessage: true})

	// 路由中间件
	fiberApp.Use(func(c *fiber.Ctx) error {
		// 官网域名直接返回
		if strings.EqualFold(c.Hostname(), mainHost) {
			return c.Next()
		}

		fullURL := c.OriginalURL()

		// Minio代理
		if strings.HasPrefix(c.Path(), "/minio") {
			return proxy.Do(c, minioUrl+fullURL)
		}

		if strings.HasPrefix(c.Path(), "/ws") {
			return c.Status(403).SendString("无权限")
		}
		// 模块代理
		if strings.HasPrefix(c.Path(), "/modules/") {
			parts := strings.SplitN(fullURL[9:], "/", 2)
			if len(parts) != 2 {
				return c.Status(400).SendString("Invalid path")
			}

			// 从pg获取服务端口
			moduleName := parts[0]
			info, err := module.GetRunningPhpModuleInfo(moduleName)
			if err != nil || info == nil {
				return c.Status(404).SendString("Module not found")
			}

			// 构建目标URL
			targetURL := fmt.Sprintf("http://127.0.0.1:%d/%s", info.HttpPort, parts[1])
			_, err = url.Parse(targetURL)
			if err != nil {
				return c.Status(404).SendString("invalid target url")
			}
			return proxy.Do(c, targetURL)
		} else {
			// 默认是main模块
			info, err := module.GetRunningPhpModuleInfo(`main`)
			if err != nil || info == nil {
				return c.Status(404).SendString("Module main not found")
			}
			return proxy.Do(c, fmt.Sprintf("http://127.0.0.1:%d%s", info.HttpPort, fullURL))
		}
	})

	// 静态文件路由
	fiberApp.Static("/docs", "./static/vitepress/docs/.vitepress/dist")
	fiberApp.Static("/docker-compose-prod.yml", "./docker/docker-compose-prod.yml")
	fiberApp.Static("/", "./static/home")

	// 检查是否启用自动 HTTPS
	acmeDomains := strings.TrimSpace(os.Getenv("ACME_DOMAINS"))
	domains := strings.Split(acmeDomains, ",")
	var validDomains []string
	for _, domain := range domains {
		domain = strings.TrimSpace(domain)
		if domain != "" {
			validDomains = append(validDomains, domain)
		}
	}

	if len(validDomains) > 0 {
		m := &autocert.Manager{
			Prompt:     autocert.AcceptTOS,
			HostPolicy: autocert.HostWhitelist(validDomains...),
			Cache:      autocert.DirCache("/etc/letsencrypt"),
		}
		cfg := &tls.Config{
			GetCertificate: m.GetCertificate,
			NextProtos: []string{
				"http/1.1", "acme-tls/1",
			},
		}
		ln, err := tls.Listen("tcp", ":443", cfg)
		if err != nil {
			log.Printf(err.Error())
		} else {
			go func() {
				err := fiberApp.Listener(ln)
				if err != nil {
					log.Printf(err.Error())
				}
			}()
		}
	}

	return fiberApp.Listen(":80")
}

func stopFiberProxy() {
	if err := fiberApp.Shutdown(); err != nil {
		panic(err)
	}
}