package main

import (
	"crypto/tls"
	"fmt"
	"github.com/valyala/fasthttp"
	"log"
	"net/url"
	"os"
	"session_archive/golang/define"
	"session_archive/golang/pkg/nats_util"
	"strings"

	"github.com/gofiber/fiber/v2"
	"github.com/gofiber/fiber/v2/middleware/proxy"
	"golang.org/x/crypto/acme/autocert"
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

		c.Request().Header.Add("X-External", "1")

		// 官网域名直接返回
		if strings.EqualFold(c.Hostname(), mainHost) {
			return c.Next()
		}

		fullURL := c.OriginalURL()

		// Minio代理（旧版，后面不用了）
		if strings.HasPrefix(c.Path(), "/minio") {
			return proxy.Do(c, minioUrl+fullURL)
		}

		// 新版minio代理
		if strings.HasPrefix(c.Path(), "/storage") {
			path := strings.TrimPrefix(c.Path(), "/storage")

			// 构建完整的 MinIO URL
			targetUrl := minioUrl + path

			// 创建一个新的请求
			req := c.Request()
			req.SetRequestURI(targetUrl)

			// 发送请求到 MinIO
			res := c.Response()
			client := &fasthttp.Client{}
			if err := client.Do(req, res); err != nil {
				return c.Status(fiber.StatusInternalServerError).SendString("Proxy Error")
			}

			// 移除或修改可能导致问题的头部
			res.Header.Del("Location")
			res.Header.Del("Content-Disposition")
			res.Header.Del("Server")

			return nil
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

			// 从nats获取服务信息
			info, err := nats_util.GetNatsServiceInfo(define.NatsConn, parts[0]+"_temp")
			if err != nil {
				return c.Status(404).SendString("Module not found")
			}
			port, ok := info.Metadata["port"]
			if !ok {
				return c.Status(404).SendString(fmt.Sprintf("cannot find http port of module %s", parts[0]))
			}

			// 构建目标URL
			targetURL := fmt.Sprintf("http://127.0.0.1:%s/%s", port, parts[1])
			_, err = url.Parse(targetURL)
			if err != nil {
				return c.Status(404).SendString("invalid target url")
			}

			// 添加原始域名信息到请求头
			c.Request().Header.Set("X-Forwarded-Host", c.Hostname())
			return proxy.Do(c, targetURL)
		} else {
			// 默认是main模块
			info, err := nats_util.GetNatsServiceInfo(define.NatsConn, "main_temp")
			if err != nil {
				return c.Status(404).SendString("Module main not found")
			}
			port, ok := info.Metadata["port"]
			if !ok {
				return c.Status(404).SendString("cannot find http port of module main")
			}

			// 添加原始域名信息到请求头
			c.Request().Header.Set("X-Forwarded-Host", c.Hostname())
			return proxy.Do(c, fmt.Sprintf("http://127.0.0.1:%s%s", port, fullURL))
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
			log.Println(err.Error())
		} else {
			go func() {
				err := fiberApp.Listener(ln)
				if err != nil {
					log.Println(err.Error())
				}
			}()
		}
	}

	return fiberApp.Listen(":8080")
}

func stopFiberProxy() {
	log.Println("fiber proxy stopping...")
	if err := fiberApp.ShutdownWithTimeout(3); err != nil {
		log.Println(err)
		return
	}
	log.Println("fiber proxy stopped")
}
