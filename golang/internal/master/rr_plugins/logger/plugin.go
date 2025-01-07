package logger

import (
	"context"

	"github.com/roadrunner-server/endure/v2/dep"
	"github.com/roadrunner-server/errors"
	"go.uber.org/zap"
)

// PluginName declares plugin name.
const PluginName = "logs"

type Configurer interface {
	// UnmarshalKey takes a single key and unmarshal it into a Struct.
	UnmarshalKey(name string, out any) error
	// Has checks if a config section exists.
	Has(name string) bool
}

// Logger is the main logger interface to provide a named (per-plugin) logger
type Logger interface {
	NamedLogger(name string) *zap.Logger
}

// Plugin manages zap logger.
type Plugin struct {
	base     *zap.Logger
	cfg      *Config
	channels ChannelConfig
}

// Init logger service.
func (p *Plugin) Init(cfg Configurer) error {
	const op = errors.Op("config_plugin_init")
	var err error
	// if not configured, configure with default params
	if !cfg.Has(PluginName) {
		p.cfg = &Config{}
		p.cfg.InitDefault()

		p.base, err = p.cfg.BuildLogger()
		if err != nil {
			return errors.E(op, err)
		}

		return nil
	}

	err = cfg.UnmarshalKey(PluginName, &p.cfg)
	if err != nil {
		return errors.E(op, err)
	}

	err = cfg.UnmarshalKey(PluginName, &p.channels)
	if err != nil {
		return errors.E(op, err)
	}

	p.cfg.InitDefault()
	p.base, err = p.cfg.BuildLogger()

	if err != nil {
		return errors.E(op, err)
	}
	return nil
}

func (p *Plugin) Serve() chan error {
	return make(chan error, 1)
}

func (p *Plugin) Stop(context.Context) error {
	_ = p.base.Sync()
	return nil
}

func (p *Plugin) Provides() []*dep.Out {
	return []*dep.Out{
		dep.Bind((*Logger)(nil), p.ServiceLogger),
	}
}

// ServiceLogger returns a logger dedicated to the specific channel. Similar to Named() but also reads the core params.
func (p *Plugin) ServiceLogger() *Log {
	return NewLogger(p.channels, p.base, p.cfg.Prefix)
}

// Name returns a user-friendly plugin name
func (p *Plugin) Name() string {
	return PluginName
}
