package common

import (
	"go.uber.org/zap"
)

type rpc struct {
	pl  *Plugin
	log *zap.Logger
}

func (r *rpc) Hello(input string, output *string) error {
	*output = "hello, " + input
	r.log.Info(`调用了Hello方法`)
	return nil
}
