package jobs

import (
	"context"
	"encoding/json"
	"sync"
	"sync/atomic"
	"time"

	"github.com/roadrunner-server/errors"
	"github.com/roadrunner-server/goridge/v3/pkg/frame"
	"github.com/roadrunner-server/pool/payload"
	"github.com/roadrunner-server/pool/pool"
	staticPool "github.com/roadrunner-server/pool/pool/static_pool"
	"github.com/roadrunner-server/pool/state/process"
	"github.com/roadrunner-server/pool/worker"
	"go.uber.org/zap"
)

const (
	pluginName = "jobs"
	RRMode     = "RR_MODE"
	RRModeJobs = "jobs"
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

type Job struct {
	Queue   string `json:"queue"` // 队列名称
	Handler string `json:"handler"`
	Data    string `json:"data"`
}

type Config struct {
	Name string       `json:"name"`
	Pool *pool.Config `mapstructure:"pool"`
}

type JobQueue struct {
	name       string
	jobChan    chan Job
	wg         sync.WaitGroup
	ctx        context.Context
	cancel     context.CancelFunc
	processing int32 // 正在处理的任务数量
}

type Plugin struct {
	mu     sync.RWMutex
	cfg    *Config
	log    *zap.Logger
	server Server
	pool   Pool
	queues map[string]*JobQueue // 队列名称 -> 队列
	ctx    context.Context
	cancel context.CancelFunc
}

func (p *Plugin) Name() string {
	return pluginName
}

func (p *Plugin) Init(cfg Configurer, log Logger, server Server) error {
	const op = errors.Op("jobs_plugin_init")
	if !cfg.Has(pluginName) {
		return errors.E(op, errors.Disabled)
	}
	err := cfg.UnmarshalKey(pluginName, &p.cfg)
	if err != nil {
		return errors.E(op, err)
	}

	p.log = log.NamedLogger(pluginName)
	p.server = server
	p.queues = make(map[string]*JobQueue)
	p.ctx, p.cancel = context.WithCancel(context.Background())

	return nil
}

func (p *Plugin) Serve() chan error {
	p.log.Debug("jobs插件启动")
	errCh := make(chan error, 1)

	const op = errors.Op("jobs_serve")

	p.mu.Lock()

	var err error
	p.pool, err = p.server.NewPool(context.Background(), p.cfg.Pool, map[string]string{RRMode: RRModeJobs}, nil)

	if err != nil {
		p.mu.Unlock()
		errCh <- err
		return errCh
	}

	// 创建默认队列（在持有锁的情况下直接创建，不调用 getOrCreateQueue）
	defaultQueue := &JobQueue{
		name:    "default",
		jobChan: make(chan Job, 1000),
		ctx:     p.ctx,
	}
	p.queues["default"] = defaultQueue

	p.mu.Unlock()

	// 启动默认队列的 worker（在锁外启动）
	defaultQueue.wg.Add(1)
	go p.worker(defaultQueue)

	p.log.Info("默认队列已创建并启动")

	return nil
}

func (p *Plugin) Stop(ctx context.Context) error {
	p.log.Debug("jobs插件停止")

	// 取消context，停止所有队列的worker
	p.cancel()

	// 等待所有队列的worker完成
	p.mu.Lock()
	for _, queue := range p.queues {
		queue.wg.Wait()
	}

	// 销毁pool
	if p.pool != nil {
		p.pool.Destroy(ctx)
	}
	p.mu.Unlock()

	return nil
}

// getOrCreateQueue 获取或创建队列
func (p *Plugin) getOrCreateQueue(queueName string) *JobQueue {
	p.mu.Lock()
	defer p.mu.Unlock()

	// 检查队列是否已存在
	if queue, exists := p.queues[queueName]; exists {
		return queue
	}

	// 创建新队列
	queue := &JobQueue{
		name:    queueName,
		jobChan: make(chan Job, 1000), // 缓冲队列，最多1000个任务
		ctx:     p.ctx,
	}

	p.queues[queueName] = queue

	// 启动worker goroutine来处理这个队列的任务
	queue.wg.Add(1)
	go p.worker(queue)

	p.log.Info("创建新队列", zap.String("queue", queueName))

	return queue
}

// worker 处理任务队列中的任务
func (p *Plugin) worker(queue *JobQueue) {
	defer queue.wg.Done()

	for {
		select {
		case <-queue.ctx.Done():
			p.log.Debug("jobs worker停止", zap.String("queue", queue.name))
			return
		case job := <-queue.jobChan:
			p.processJob(job, queue)
		}
	}
}

// processJob 执行具体的任务（异步发送，不等待结果）
func (p *Plugin) processJob(job Job, queue *JobQueue) {
	const op = "jobs_plugin:ProcessJob"

	// 增加正在处理的任务计数
	atomic.AddInt32(&queue.processing, 1)
	defer atomic.AddInt32(&queue.processing, -1)

	data := make(map[string]string)
	data["data"] = job.Data
	data["handler"] = job.Handler

	d, err := json.Marshal(data)
	if err != nil {
		p.log.Error(errors.E(op, err).Error())
		return
	}

	pl := &payload.Payload{
		Context: []byte(""),
		Body:    d,
		Codec:   frame.CodecProto,
	}

	p.log.Debug("发送任务到worker", zap.String("queue", job.Queue), zap.String("handler", job.Handler))

	// 异步发送任务，不等待结果（pool 是线程安全的，不需要锁）
	resultChan, err := p.pool.Exec(context.Background(), pl, nil)
	if err != nil {
		p.log.Error(errors.E(op, err).Error())
		return
	}

	// 在后台处理结果，不阻塞当前 goroutine
	go func() {
		select {
		case res := <-resultChan:
			if res.Error() != nil {
				p.log.Error("任务执行失败", zap.String("queue", job.Queue), zap.String("handler", job.Handler), zap.Error(res.Error()))
			} else {
				p.log.Debug("任务执行成功", zap.String("queue", job.Queue), zap.String("handler", job.Handler))
			}
		case <-time.After(time.Second * 30):
			p.log.Warn("任务执行超时", zap.String("queue", job.Queue), zap.String("handler", job.Handler))
		}
	}()

	p.log.Debug("任务已发送", zap.String("queue", job.Queue), zap.String("handler", job.Handler))
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
	const op = errors.Op("jobs_plugin_reset")

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

// Push 向指定队列中添加任务
func (p *Plugin) Push(job Job) error {
	const op = errors.Op("jobs_plugin:Push")

	// 如果未指定队列名称，使用默认队列
	if job.Queue == "" {
		job.Queue = "default"
	}

	// 获取或创建队列
	queue := p.getOrCreateQueue(job.Queue)

	select {
	case queue.jobChan <- job:
		return nil
	default:
		return errors.E(op, "队列已满: "+job.Queue)
	}
}

// QueueSize 返回指定队列中待执行的任务数量
func (p *Plugin) QueueSize(queueName string) int {
	if queueName == "" {
		queueName = "default"
	}

	p.mu.RLock()
	defer p.mu.RUnlock()

	if queue, exists := p.queues[queueName]; exists {
		return len(queue.jobChan)
	}

	return 0
}

// QueueInfoData 队列详细信息
type QueueInfoData struct {
	Waiting    int
	Processing int
	Total      int
}

// AllQueuesInfo 返回所有队列的详细信息
func (p *Plugin) AllQueuesInfo() map[string]QueueInfoData {
	p.mu.RLock()
	defer p.mu.RUnlock()

	result := make(map[string]QueueInfoData)
	for name, queue := range p.queues {
		waiting := len(queue.jobChan)
		processing := int(atomic.LoadInt32(&queue.processing))
		result[name] = QueueInfoData{
			Waiting:    waiting,
			Processing: processing,
			Total:      waiting + processing,
		}
	}

	return result
}
