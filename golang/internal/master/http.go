package master

import (
	"crypto/tls"
	"github.com/gofiber/fiber/v2"
	"github.com/gofiber/fiber/v2/log"
	"golang.org/x/crypto/acme/autocert"
	"os"
	"session_archive/golang/internal/master/define"
	"strings"
)

func StartHttp() error {
	define.FiberApp = fiber.New(fiber.Config{DisableStartupMessage: true})

	define.FiberApp.Use(proxyMiddleware)

	define.FiberApp.Static("/docs", "./static/vitepress/docs/.vitepress/dist")
	define.FiberApp.Static("/docker-compose-prod.yml", "./docker/docker-compose-prod.yml")
	define.FiberApp.Static("/", "./static/home")

	define.FiberApp.Post("/internal/modules/start", startModule)
	define.FiberApp.Post("/internal/modules/stop", stopModule)
	define.FiberApp.Post("/internal/modules/list", getModuleList)
	define.FiberApp.Post("/internal/modules/info", getModuleInfo)

	handleHttps()
	return define.FiberApp.Listen(":8080")
}

func StopHttp() {
	log.Info("fiber proxy stopping...")
	if err := define.FiberApp.ShutdownWithTimeout(3); err != nil {
		log.Error(err)
		return
	}
	log.Info("fiber proxy stopped")
}

// handleHttps 检查并启用https
func handleHttps() {
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
			log.Errorf(err.Error())
		} else {
			go func() {
				err := define.FiberApp.Listener(ln)
				if err != nil {
					log.Errorf(err.Error())
				}
			}()
		}
	}
}
