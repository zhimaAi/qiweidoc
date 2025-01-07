package broadcast

import (
	"go.uber.org/zap"
)

type rpc struct {
	pl  *Plugin
	log *zap.Logger
}

type Input struct {
	Event   string `json:"event"`
	From    string `json:"from"`
	Handler string `json:"handler"`
}

func (r *rpc) Register(input Input, output *string) error {
	r.log.Debug("add router: " + input.Event)
	r.pl.router[input.Event] = Router{
		From:    input.From,
		Handler: input.Handler,
	}
	return nil
}
