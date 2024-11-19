package wxfinance

import (
	"context"
	"github.com/roadrunner-server/endure/v2/dep"
	"go.uber.org/zap"
	"session_archive/golang/plugins/minio"
)

const pluginName = "wxfinance"

type Logger interface {
	NamedLogger(name string) *zap.Logger
}

type Plugin struct {
	log         *zap.Logger
	minioPlugin MinioPlugin
}

type MinioPlugin interface {
	Name() string
	GetFileByMD5(minio.GetFileByMD5Request) (string, error)
	UploadFile(minio.UploadFileRequest) (minio.UploadFileResponse, error)
}

func (p *Plugin) Init(log Logger) error {
	p.log = log.NamedLogger(pluginName)
	p.log.Info(`wxfinance插件初始化成功`)
	return nil
}

func (p *Plugin) Serve() chan error {
	p.log.Info(`wxfinance插件开始服务`)
	return nil
}

func (p *Plugin) Stop(ctx context.Context) error {
	p.log.Info(`wxfinance插件停止服务`)
	return nil
}

func (p *Plugin) Name() string {
	return pluginName
}

// Collects
// 注入minio插件依赖
func (p *Plugin) Collects() []*dep.In {
	return []*dep.In{
		dep.Fits(func(pp any) {
			if pp.(MinioPlugin).Name() == `minio` {
				p.minioPlugin = pp.(MinioPlugin)
			}
		}, (*MinioPlugin)(nil)),
	}
}

func (p *Plugin) RPC() any {
	return &rpc{
		log: p.log,
		pl:  p,
	}
}
