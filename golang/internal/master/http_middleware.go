package master

import (
	"fmt"
	"github.com/gofiber/fiber/v2"
	"github.com/gofiber/fiber/v2/log"
	"github.com/gofiber/fiber/v2/middleware/proxy"
	"github.com/valyala/fasthttp"
	"net/url"
	"session_archive/golang/internal/master/define"
	"strings"
)

// proxyMiddleware 反向代理
func proxyMiddleware(c *fiber.Ctx) error {
	c.Request().Header.Add("X-External", "1")

	// 官网域名直接返回
	if strings.EqualFold(c.Hostname(), define.MainHost) {
		return c.Next()
	}

	fullURL := c.OriginalURL()

	// Minio代理（旧版，后面不用了）
	if strings.HasPrefix(c.Path(), "/minio") {
		return proxy.Do(c, define.MinioUrl+fullURL)
	}

	// 新版minio代理
	if strings.HasPrefix(c.Path(), "/storage") {
		path := strings.TrimPrefix(c.Path(), "/storage")

		// 构建完整的 MinIO URL
		targetUrl := define.MinioUrl + path

		// 创建一个新的请求
		req := fasthttp.AcquireRequest()
		defer fasthttp.ReleaseRequest(req)
		req.SetRequestURI(targetUrl)
		req.SetBody(c.Body())

		// 发送请求到 MinIO
		res := c.Response()
		client := &fasthttp.Client{}
		if err := client.Do(req, res); err != nil {
			log.Errorf("Proxy error: %v\n", err)
			log.Errorf("Target URL: %s\n", targetUrl)
			return c.Status(fiber.StatusInternalServerError).SendString("Proxy Error")
		}

		// 复制响应头部
		res.Header.VisitAll(func(key, value []byte) {
			c.Response().Header.SetBytesKV(key, value)
		})

		// 设置状态码
		c.Status(res.StatusCode())

		// 写入响应体
		return c.Send(res.Body())
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

		// 获取模块信息
		moduleInfo, ok := define.ModuleList[parts[0]]
		if !ok || moduleInfo.RR == nil {
			return c.Status(200).JSON(fiber.Map{
				"status":        "success",
				"error_message": "模块不存在或未启用",
				"error_code":    200,
				"data":          nil,
			})
		}

		// 构建目标URL
		targetURL := fmt.Sprintf("http://127.0.0.1:%d/%s", moduleInfo.HttpPort, parts[1])
		_, err := url.Parse(targetURL)
		if err != nil {
			return c.Status(404).SendString("invalid target url")
		}

		// 添加原始域名信息到请求头
		c.Request().Header.Set("X-Forwarded-Host", c.Hostname())
		return proxy.Do(c, targetURL)
	} else if strings.HasPrefix(c.Path(), "/internal") {
		return c.Next()
	} else {
		moduleInfo, ok := define.ModuleList["main"]
		if !ok {
			return c.Status(404).SendString("Module main not found")
		}

		// 添加原始域名信息到请求头
		c.Request().Header.Set("X-Forwarded-Host", c.Hostname())
		return proxy.Do(c, fmt.Sprintf("http://127.0.0.1:%d%s", moduleInfo.HttpPort, fullURL))
	}
}
