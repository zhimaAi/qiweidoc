package broadcast

import (
	"context"
	"encoding/json"
	"github.com/gofiber/fiber/v2/log"
	"github.com/nats-io/nats.go"
	"github.com/roadrunner-server/errors"
	"github.com/roadrunner-server/goridge/v3/pkg/frame"
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
	pluginName  = "broadcast"
	RRMode      = "RR_MODE"
	RRModeMicro = "broadcast"
	Subject     = "module_event"
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

type Router struct {
	From    string `json:"from"`
	Handler string `json:"handler"`
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
	router map[string]Router
}

func (p *Plugin) Name() string {
	return pluginName
}

func (p *Plugin) Init(cfg Configurer, log Logger, server Server) error {
	const op = errors.Op("broadcast_plugin_init")
	if !cfg.Has("nats") {
		return errors.E(op, errors.Disabled)
	}
	if err := cfg.UnmarshalKey("nats", &p.natsCfg); err != nil {
		return errors.E(op, err)
	}
	if !cfg.Has(pluginName) {
		return errors.E(op, errors.Disabled)
	}
	err := cfg.UnmarshalKey(pluginName, &p.cfg)
	if err != nil {
		return errors.E(op, err)
	}

	p.log = log.NamedLogger(pluginName)
	p.server = server

	p.router = make(map[string]Router)

	return nil
}

func (p *Plugin) Serve() chan error {
	p.log.Debug("broadcast插件启动")
	var err error
	errCh := make(chan error, 1)

	const op = errors.Op("plugin_broadcast:Serve")

	p.mu.Lock()
	defer p.mu.Unlock()

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

	p.pool, err = p.server.NewPool(context.Background(), p.cfg.Pool, map[string]string{RRMode: RRModeMicro}, nil)
	if err != nil {
		errCh <- err
		return errCh
	}

	type Message struct {
		Event string `json:"event"`
		From  string `json:"from"`
		Data  string `json:"data"`
	}

	_, err = p.natsConn.Subscribe(Subject, func(msg *nats.Msg) {
		p.log.Debug("收到消息: " + string(msg.Data))
		var message Message
		if err = json.Unmarshal(msg.Data, &message); err != nil {
			p.log.Error(errors.E(op, err).Error())
			return
		}
		router, ok := p.router[message.Event]
		if !ok || router.From != message.From {
			p.log.Debug("没有定义对应的路由")
			return
		}

		data := make(map[string]string)
		data["data"] = message.Data
		data["from"] = router.From
		data["handler"] = router.Handler
		d, _ := json.Marshal(data)
		pl := &payload.Payload{
			Context: []byte(""),
			Body:    d,
			Codec:   frame.CodecProto,
		}
		p.log.Debug("开始执行")
		_, err = p.exec(context.Background(), pl)
		if err != nil {
			p.log.Error(errors.E(op, err).Error())
			return
		}
	})
	if err != nil {
		errCh <- err
		return errCh
	}
	return nil
}

func (p *Plugin) Stop(ctx context.Context) error {
	p.log.Debug("broadcast插件停止")
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
