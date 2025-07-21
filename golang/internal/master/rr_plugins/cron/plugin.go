package cron

import (
	"context"
	"github.com/roadrunner-server/errors"
	"github.com/roadrunner-server/goridge/v3/pkg/frame"
	"github.com/roadrunner-server/pool/payload"
	"github.com/roadrunner-server/pool/pool"
	staticPool "github.com/roadrunner-server/pool/pool/static_pool"
	"github.com/roadrunner-server/pool/state/process"
	"github.com/roadrunner-server/pool/worker"
	"github.com/robfig/cron/v3"
	"go.uber.org/zap"
	"sync"
	"time"
)

const (
	pluginName  = "cron"
	RRMode      = "RR_MODE"
	RRModeMicro = "cron"
)

type Configurer interface {
	// UnmarshalKey takes a single key and unmarshal it into a Struct.
	UnmarshalKey(name string, out any) error
	// Has checks if a config section exists.
	Has(name string) bool
}

type Pool interface {
	// Workers return workers' list associated with the pool.
	Workers() (workers []*worker.Process)
	// RemoveWorker removes worker from the pool.
	RemoveWorker(ctx context.Context) error
	// AddWorker adds worker to the pool.
	AddWorker() error
	// Exec payload
	Exec(ctx context.Context, p *payload.Payload, stopCh chan struct{}) (chan *staticPool.PExec, error)
	// Reset kills all workers inside the watcher and replaces with new
	Reset(ctx context.Context) error
	// Destroy the underlying stack (but let them complete the task).
	Destroy(ctx context.Context)
}

// Server creates workers for the application.
type Server interface {
	NewPool(ctx context.Context, cfg *pool.Config, env map[string]string, _ *zap.Logger) (*staticPool.Pool, error)
}

type Logger interface {
	NamedLogger(name string) *zap.Logger
}

type Router struct {
	Id        cron.EntryID `json:"id"`
	Spec      string       `json:"spec"`
	Handler   string       `json:"handler"`
	Data      string       `json:"data"`
	IsRunning bool         `json:"is_running"`
}

type Config struct {
	Name string       `json:"name"`
	Pool *pool.Config `mapstructure:"pool"`
}

type Plugin struct {
	mu         sync.RWMutex
	stopChPool sync.Pool
	cfg        *Config

	log    *zap.Logger
	server Server

	pool      Pool
	cron      *cron.Cron
	routerMap map[string]Router
	mutex     sync.Mutex
}

func (p *Plugin) Name() string {
	return pluginName
}

func (p *Plugin) Init(cfg Configurer, log Logger, server Server) error {
	const op = errors.Op("cron_plugin_init")
	if !cfg.Has(pluginName) {
		return errors.E(op, errors.Disabled)
	}
	err := cfg.UnmarshalKey(pluginName, &p.cfg)
	if err != nil {
		return errors.E(op, err)
	}

	p.log = log.NamedLogger(pluginName)
	p.server = server
	p.cron = cron.New()
	p.routerMap = make(map[string]Router)

	return nil
}

func (p *Plugin) Serve() chan error {
	p.log.Debug("cron插件启动")
	errCh := make(chan error, 1)

	const op = errors.Op("cron_serve")

	p.mu.Lock()
	defer p.mu.Unlock()

	var err error
	p.pool, err = p.server.NewPool(context.Background(), p.cfg.Pool, map[string]string{RRMode: RRModeMicro}, nil)

	if err != nil {
		errCh <- err
		return errCh
	}

	p.cron.Start()

	return nil
}

func (p *Plugin) Stop(ctx context.Context) error {
	p.log.Debug("cron插件停止")
	stCh := make(chan struct{}, 1)
	go func() {
		p.mu.Lock()
		p.pool.Destroy(ctx)
		p.cron.Stop()
		p.mu.Unlock()
		stCh <- struct{}{}
	}()

	select {
	case <-ctx.Done():
		return ctx.Err()
	case <-stCh:
		return nil
	}
}

// Workers returns slice with the process states for the workers
func (p *Plugin) Workers() []*process.State {
	p.mu.RLock()
	defer p.mu.RUnlock()

	workers := p.workers()
	if workers == nil {
		return nil
	}

	ps := make([]*process.State, 0, len(workers))

	for i := 0; i < len(workers); i++ {
		state, err := process.WorkerProcessState(workers[i])
		if err != nil {
			return nil
		}

		ps = append(ps, state)
	}

	return ps
}

func (p *Plugin) RPC() any {
	return &rpc{
		log: p.log,
		pl:  p,
	}
}

// internal
func (p *Plugin) workers() []*worker.Process {
	if p == nil || p.pool == nil {
		return nil
	}

	return p.pool.Workers()
}

// Reset destroys the old pool and replaces it with new one, waiting for old pool to die
func (p *Plugin) Reset() error {
	const op = errors.Op("micro_plugin_reset")

	p.log.Info("reset signal was received")

	ctxTout, cancel := context.WithTimeout(context.Background(), time.Second*60)
	defer cancel()

	if p.pool == nil {
		p.log.Info("pool is nil, nothing to reset")

		return nil
	}

	err := p.pool.Reset(ctxTout)
	if err != nil {
		return errors.E(op, err)
	}

	p.log.Info("plugin was successfully reset")

	return nil
}

func (p *Plugin) exec(ctx context.Context, pld *payload.Payload) (*payload.Payload, error) {
	p.mu.RLock()
	re, err := p.pool.Exec(ctx, pld, nil)
	p.mu.RUnlock()
	if err != nil {
		return nil, err
	}

	var resp *payload.Payload
	select {
	case pl := <-re:
		if pl.Error() != nil {
			return nil, pl.Error()
		}
		if pl.Payload().Flags&frame.STREAM != 0 {
			return nil, errors.E("streaming response not supported")
		}
		resp = pl.Payload()
	default:
		return nil, errors.E("worker empty response")
	}
	return resp, nil
}
