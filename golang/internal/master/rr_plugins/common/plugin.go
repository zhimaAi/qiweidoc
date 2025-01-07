package common

import (
	"context"
	"go.uber.org/zap"
	"sync"
	"time"
)

const pluginName = "common"

type Logger interface {
	NamedLogger(name string) *zap.Logger
}

type Plugin struct {
	log *zap.Logger

	cronCollectModuleMu     sync.Mutex
	cronCollectModuleTicker *time.Ticker
	cronCollectModuleDone   chan struct{}
}

func (p *Plugin) Init(log Logger) error {
	p.log = log.NamedLogger(pluginName)
	p.log.Debug(`common插件初始化成功`)

	p.cronCollectModuleDone = make(chan struct{})

	return nil
}

func (p *Plugin) Serve() chan error {
	p.log.Debug(`common插件开始服务`)
	return nil
}

func (p *Plugin) Stop(ctx context.Context) error {
	p.log.Debug(`common插件停止服务`)

	close(p.cronCollectModuleDone)

	return nil
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
