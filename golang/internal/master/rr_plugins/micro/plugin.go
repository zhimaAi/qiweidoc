package micro

import (
	"context"
	"github.com/gofiber/fiber/v2/log"
	"github.com/nats-io/nats.go"
	"github.com/nats-io/nats.go/micro"
	"github.com/roadrunner-server/errors"
	"github.com/roadrunner-server/pool/payload"
	"github.com/roadrunner-server/pool/pool"
	staticPool "github.com/roadrunner-server/pool/pool/static_pool"
	"github.com/roadrunner-server/pool/state/process"
	"github.com/roadrunner-server/pool/worker"
	"go.uber.org/zap"
	"sync"
	"time"
)

const (
	pluginName  = "micro"
	RRMode      = "RR_MODE"
	RRModeMicro = "micro"
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

type NatsConfig struct {
	Addr string `json:"addr"`
}

type Config struct {
	Name string       `json:"name"`
	Pool *pool.Config `mapstructure:"pool"`
}

type Plugin struct {
	mu         sync.RWMutex
	stopChPool sync.Pool
	natsCfg    *NatsConfig
	cfg        *Config

	log      *zap.Logger
	server   Server
	natsConn *nats.Conn

	pool   Pool
	group  micro.Group
	router map[string]string
}

func (p *Plugin) Name() string {
	return pluginName
}

func (p *Plugin) Init(cfg Configurer, log Logger, server Server) error {
	const op = errors.Op("micro_plugin_init")
	if !cfg.Has("nats") {
		return errors.E(op, errors.Disabled)
	}
	if err := cfg.UnmarshalKey("nats", &p.natsCfg); err != nil {
		return errors.E(op, err)
	}

	if !cfg.Has(pluginName) {
		return errors.E(op, errors.Disabled)
	}
	if err := cfg.UnmarshalKey(pluginName, &p.cfg); err != nil {
		return errors.E(op, err)
	}

	p.log = log.NamedLogger(pluginName)
	p.server = server

	p.router = make(map[string]string)

	return nil
}

func (p *Plugin) Serve() chan error {
	p.log.Debug("micro插件启动")
	var err error
	errCh := make(chan error, 1)

	const op = errors.Op("micro_serve")

	p.natsConn, err = nats.Connect(p.natsCfg.Addr,
		nats.NoEcho(),
		nats.Timeout(time.Minute),
		nats.MaxReconnects(-1),
		nats.PingInterval(time.Second*10),
		nats.ReconnectWait(time.Second),
		nats.ReconnectBufSize(20*1024*1024),
		nats.ReconnectHandler(reconnectHandler()),
		nats.DisconnectErrHandler(disconnectHandler()),
	)
	if err != nil {
		errCh <- errors.E(op, err)
		return errCh
	}

	p.mu.Lock()
	defer p.mu.Unlock()

	p.pool, err = p.server.NewPool(context.Background(), p.cfg.Pool, map[string]string{RRMode: RRModeMicro}, nil)

	if err != nil {
		errCh <- errors.E(op, err)
		return errCh
	}

	srv, err := micro.AddService(p.natsConn, micro.Config{
		Name:        p.cfg.Name,
		Version:     "1.0.0",
		Description: p.cfg.Name + "模块",
	})
	if err != nil {
		errCh <- errors.E(op, err)
		return errCh
	}

	p.group = srv.AddGroup(p.cfg.Name)

	return nil
}

func (p *Plugin) Stop(ctx context.Context) error {
	p.log.Debug("micro插件停止")
	stCh := make(chan struct{}, 1)
	go func() {
		p.mu.Lock()
		p.natsConn.Close()
		p.pool.Destroy(ctx)
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

func (p *Plugin) RPC() any {
	return &rpc{
		log: p.log,
		pl:  p,
	}
}

func reconnectHandler() func(*nats.Conn) {
	return func(conn *nats.Conn) {
		log.Warn("connection lost, reconnecting", zap.String("url", conn.ConnectedUrl()))
	}
}

func disconnectHandler() func(*nats.Conn, error) {
	return func(_ *nats.Conn, err error) {
		if err != nil {
			log.Error("nats disconnected", zap.Error(err))
			return
		}

		log.Info("nats disconnected")
	}
}
