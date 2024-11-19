package proxy

import (
	"context"
	"github.com/roadrunner-server/errors"
	"go.uber.org/zap"
	"net/http"
	"net/http/httputil"
	"net/url"
	"strings"
)

const pluginName = "proxy"

type Logger interface {
	NamedLogger(name string) *zap.Logger
}

type Plugin struct {
	log *zap.Logger

	fileServerProxy  *httputil.ReverseProxy
	minioServerProxy *httputil.ReverseProxy
}

func (p *Plugin) Init(log Logger) error {
	p.log = log.NamedLogger(pluginName)

	// 静态文件服务
	serverUrl, err := url.Parse(`http://127.0.0.1:10101`)
	if err != nil {
		return errors.E(errors.Op("静态文件服务地址不合法"))
	}
	p.fileServerProxy = httputil.NewSingleHostReverseProxy(serverUrl)

	// minio
	serverUrl, err = url.Parse(`http://minio:9000`)
	if err != nil {
		return errors.E(errors.Op("minio插件配置的server_address不合法"))
	}
	p.minioServerProxy = httputil.NewSingleHostReverseProxy(serverUrl)

	p.log.Info(`proxy插件初始化成功`)
	return nil
}

func (p *Plugin) Serve() chan error {
	p.log.Info(`proxy插件开始服务`)
	return nil
}

func (p *Plugin) Middleware(next http.Handler) http.Handler {
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		if r.URL.Path == "/" || strings.HasPrefix(r.URL.Path, "/home") {
			p.fileServerProxy.ServeHTTP(w, r)
		} else if strings.HasPrefix(r.URL.Path, "/ws") {
			http.Error(w, "Forbidden", http.StatusForbidden)
		} else if strings.HasPrefix(r.URL.Path, "/minio") {
			p.minioServerProxy.ServeHTTP(w, r)
		} else if strings.HasPrefix(r.URL.Path, "/management") {
			p.fileServerProxy.ServeHTTP(w, r)
		} else if strings.HasPrefix(r.URL.Path, "/docs") {
			p.fileServerProxy.ServeHTTP(w, r)
		} else if r.URL.Path == "/docker-compose-prod.yml" {
			p.fileServerProxy.ServeHTTP(w, r)
		} else {
			next.ServeHTTP(w, r)
		}
	})
}

func (p *Plugin) Stop(ctx context.Context) error {
	p.log.Info(`proxy插件停止服务`)
	return nil
}

func (p *Plugin) Name() string {
	return pluginName
}
