package logger

import (
	"go.uber.org/zap"
)

type Log struct {
	base     *zap.Logger
	channels ChannelConfig
	prefix   string
}

func NewLogger(channels ChannelConfig, base *zap.Logger, prefix string) *Log {
	return &Log{
		channels: channels,
		base:     base,
		prefix:   prefix,
	}
}

func (l *Log) NamedLogger(name string) *zap.Logger {
	fullName := l.prefix + name

	if cfg, ok := l.channels.Channels[name]; ok {
		ll, err := cfg.BuildLogger()
		if err != nil {
			panic(err)
		}
		return ll.Named(fullName)
	}

	return l.base.Named(fullName)
}
