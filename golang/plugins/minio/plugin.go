package minio

import (
	"context"
	"github.com/minio/minio-go/v7"
	"github.com/minio/minio-go/v7/pkg/credentials"
	"github.com/roadrunner-server/endure/v2/dep"
	"github.com/roadrunner-server/errors"
	"github.com/roadrunner-server/pool/state/process"
	"go.uber.org/zap"
	"net/http/httputil"
)

const pluginName = "minio"

type Logger interface {
	NamedLogger(name string) *zap.Logger
}

type Configurer interface {
	UnmarshalKey(name string, out any) error
	Has(name string) bool
}

type Config struct {
	ServerAddress   string `mapstructure:"server_address"`
	AccessKeyId     string `mapstructure:"access_key_id"`
	SecretAccessKey string `mapstructure:"secret_access_key"`
}

type Plugin struct {
	log           *zap.Logger
	cfg           *Config
	minioClient   *minio.Client
	servicePlugin ServicePlugin
	serverProxy   *httputil.ReverseProxy
}

type ServicePlugin interface {
	Name() string
	Workers() []*process.State
	RPC() any
}

func (p *Plugin) Init(cfg Configurer, log Logger) error {
	p.log = log.NamedLogger(pluginName)
	p.log.Info(`minio插件初始化中...`)

	err := cfg.UnmarshalKey(pluginName, &p.cfg)
	if err != nil {
		return errors.E(errors.Op("minio插件配置解析失败"), err)
	}
	if len(p.cfg.ServerAddress) == 0 || len(p.cfg.AccessKeyId) == 0 || len(p.cfg.SecretAccessKey) == 0 {
		return errors.E(errors.Op("minio插件缺少配置参数"))
	}

	p.minioClient, err = minio.New(p.cfg.ServerAddress, &minio.Options{
		Creds:  credentials.NewStaticV4(p.cfg.AccessKeyId, p.cfg.SecretAccessKey, ""),
		Secure: false,
	})
	if err != nil {
		return errors.E(errors.Op("minio client初始化失败"), err)
	}

	p.log.Info(`minio插件初始化成功...`)
	return nil
}

// Serve
// 检查minio服务端进程是否存在
func (p *Plugin) Serve() chan error {
	//errCh := make(chan error, 1)
	//
	//workers := p.servicePlugin.Workers()
	//if len(workers) <= 0 {
	//	errCh <- errors.E(errors.Op(`未找到minio server`))
	//	return errCh
	//}
	//var minioWorkerState *process.State
	//for _, worker := range workers {
	//	if strings.Contains(worker.Command, `minio`) {
	//		minioWorkerState = worker
	//		break
	//	}
	//}
	//if minioWorkerState == nil {
	//	errCh <- errors.E(errors.Op(`未找到minio server`))
	//	return errCh
	//}

	p.log.Info(`minio插件开始服务`)

	return nil
}

func (p *Plugin) Stop(ctx context.Context) error {
	p.log.Info(`minio插件停止服务`)
	return nil
}

// Collects
// 注入service插件依赖
func (p *Plugin) Collects() []*dep.In {
	return []*dep.In{
		dep.Fits(func(pp any) {
			if pp.(ServicePlugin).Name() == `service` {
				p.servicePlugin = pp.(ServicePlugin)
			}
		}, (*ServicePlugin)(nil)),
	}
}

func (p *Plugin) Name() string {
	return pluginName
}

func (p *Plugin) RPC() any {
	return &rpc{
		log: p.log,
		pl:  p,
	}
}
