package roadrunner

import (
	"fmt"
	"github.com/roadrunner-server/amqp/v5"
	"github.com/roadrunner-server/beanstalk/v5"
	"github.com/roadrunner-server/boltdb/v5"
	"github.com/roadrunner-server/centrifuge/v5"
	"github.com/roadrunner-server/fileserver/v5"
	"github.com/roadrunner-server/gzip/v5"
	"github.com/roadrunner-server/headers/v5"
	"github.com/roadrunner-server/informer/v5"
	"github.com/roadrunner-server/jobs/v5"
	"github.com/roadrunner-server/kafka/v5"
	"github.com/roadrunner-server/kv/v5"
	"github.com/roadrunner-server/lock/v5"
	"github.com/roadrunner-server/memcached/v5"
	"github.com/roadrunner-server/memory/v5"
	"github.com/roadrunner-server/metrics/v5"
	"github.com/roadrunner-server/prometheus/v5"
	"github.com/roadrunner-server/redis/v5"
	"github.com/roadrunner-server/resetter/v5"
	"github.com/roadrunner-server/send/v5"
	"github.com/roadrunner-server/server/v5"
	"github.com/roadrunner-server/service/v5"
	"github.com/roadrunner-server/sqs/v5"
	"github.com/roadrunner-server/static/v5"
	"github.com/roadrunner-server/status/v5"
	"github.com/roadrunner-server/tcp/v5"
	"github.com/shellphy/logger/v5"
	"github.com/shellphy/nats/v5"
	"runtime/debug"

	appLogger "github.com/roadrunner-server/app-logger/v5"
	configImpl "github.com/roadrunner-server/config/v5"
	"github.com/roadrunner-server/endure/v2"
	gps "github.com/roadrunner-server/google-pub-sub/v5"
	grpcPlugin "github.com/roadrunner-server/grpc/v5"
	httpPlugin "github.com/roadrunner-server/http/v5"
	rrOtel "github.com/roadrunner-server/otel/v5"
	proxyIP "github.com/roadrunner-server/proxy_ip_parser/v5"
	"github.com/roadrunner-server/roadrunner/v2024/container"
	rpcPlugin "github.com/roadrunner-server/rpc/v5"
	rrt "github.com/temporalio/roadrunner-temporal/v5"
)

const (
	rrModule string = "github.com/roadrunner-server/roadrunner/v2024"
)

type RR struct {
	container *endure.Endure
	stop      chan struct{}
	Version   string
	cfg       *configImpl.Plugin
}

// NewRR creates a new RR instance that can then be started or stopped by the caller
func NewRR(cfgFile string, override []string, pluginList []any) (*RR, error) {

	// create endure container config
	containerCfg, err := container.NewConfig(cfgFile)
	if err != nil {
		return nil, err
	}

	cfg := &configImpl.Plugin{
		Path:                 cfgFile,
		Timeout:              containerCfg.GracePeriod,
		Flags:                override,
		Version:              getRRVersion(),
		ExperimentalFeatures: true,
	}

	// create endure container
	endureOptions := []endure.Options{
		endure.GracefulShutdownTimeout(containerCfg.GracePeriod),
	}

	if containerCfg.PrintGraph {
		endureOptions = append(endureOptions, endure.Visualize())
	}

	// create endure container
	ll, err := container.ParseLogLevel(containerCfg.LogLevel)
	if err != nil {
		return nil, err
	}
	endureContainer := endure.New(ll, endureOptions...)

	// register another container plugins
	err = endureContainer.RegisterAll(append(pluginList, cfg)...)
	if err != nil {
		return nil, err
	}

	// init container and all services
	err = endureContainer.Init()
	if err != nil {
		return nil, err
	}

	return &RR{
		container: endureContainer,
		stop:      make(chan struct{}, 1),
		Version:   cfg.Version,
		cfg:       cfg,
	}, nil
}

func (rr *RR) Config() *configImpl.Plugin {
	return rr.cfg
}

// Serve starts RR and starts listening for requests.
// This is a blocking call that will return an error if / when one occurs in a plugin
func (rr *RR) Serve() error {
	// start serving the graph
	errCh, err := rr.container.Serve()
	if err != nil {
		return err
	}

	select {
	case e := <-errCh:
		return fmt.Errorf("error: %w\nplugin: %s", e.Error, e.VertexID)
	case <-rr.stop:
		return rr.container.Stop()
	}
}

func (rr *RR) Plugins() []string {
	return rr.container.Plugins()
}

// Stop stops roadrunner
func (rr *RR) Stop() {
	rr.stop <- struct{}{}
}

// DefaultPluginsList returns all the plugins that RR can run with and are included by default
func DefaultPluginsList() []any {
	return Plugins()
}

// Tries to find the version info for a given module's path
// empty string if not found
func getRRVersion() string {
	bi, ok := debug.ReadBuildInfo()
	if !ok {
		return ""
	}

	for i := 0; i < len(bi.Deps); i++ {
		if bi.Deps[i].Path == rrModule {
			return bi.Deps[i].Version
		}
	}

	return ""
}

// Plugins return active plugins for the endured container. Feel free to add or remove any plugins.
func Plugins() []any { //nolint:funlen
	return []any{
		// bundled
		// informer plugin (./rr workers, ./rr workers -i)
		&informer.Plugin{},
		// resetter plugin (./rr reset)
		&resetter.Plugin{},
		// mutexes(locks)
		&lock.Plugin{},
		// logger plugin
		&logger.Plugin{},
		// psr-3 logger extension
		&appLogger.Plugin{},
		// metrics plugin
		&metrics.Plugin{},
		// rpc plugin (workers, reset)
		&rpcPlugin.Plugin{},
		// server plugin (NewWorker, NewWorkerPool)
		&server.Plugin{},
		// service plugin
		&service.Plugin{},
		// centrifuge
		&centrifuge.Plugin{},
		//
		// ========= JOBS bundle
		&jobs.Plugin{},
		&amqp.Plugin{},
		&sqs.Plugin{},
		&nats.Plugin{},
		&kafka.Plugin{},
		&beanstalk.Plugin{},
		&gps.Plugin{},
		// =========
		//
		// http server plugin with middleware
		&httpPlugin.Plugin{},
		&static.Plugin{},
		&headers.Plugin{},
		&status.Plugin{},
		&gzip.Plugin{},
		&prometheus.Plugin{},
		&send.Plugin{},
		&proxyIP.Plugin{},
		&rrOtel.Plugin{},
		&fileserver.Plugin{},
		// ===================
		// gRPC
		&grpcPlugin.Plugin{},
		// ===================
		//  KV + Jobs
		&memory.Plugin{},
		//  KV + Jobs
		&boltdb.Plugin{},
		//  ============== KV
		&kv.Plugin{},
		&memcached.Plugin{},
		&redis.Plugin{},
		//  ==============
		// raw TCP connections handling
		&tcp.Plugin{},
		// temporal plugin
		&rrt.Plugin{},
	}
}
