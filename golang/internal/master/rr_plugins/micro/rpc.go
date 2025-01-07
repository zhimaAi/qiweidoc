package micro

import (
	"context"
	"encoding/json"
	"fmt"
	"github.com/nats-io/nats.go/micro"
	"github.com/roadrunner-server/errors"
	"github.com/roadrunner-server/goridge/v3/pkg/frame"
	"github.com/roadrunner-server/pool/payload"
	"go.uber.org/zap"
)

type rpc struct {
	pl  *Plugin
	log *zap.Logger
}

type Input struct {
	Name    string `json:"name"`
	Handler string `json:"handler"`
}

func (r *rpc) AddEndpoint(input Input, output *string) error {
	const op = errors.Op("rpc_add_endpoint")
	if len(input.Name) == 0 || len(input.Handler) == 0 {
		r.log.Error(errors.E(op, "缺少参数").Error())
	}
	r.log.Debug("add endpoint: " + input.Name)

	err := r.pl.group.AddEndpoint(input.Name, micro.HandlerFunc(func(request micro.Request) {
		go func(request micro.Request) {
			r.log.Debug(fmt.Sprintf("received payload: %s", string(request.Data())))

			data := make(map[string]string)
			data["data"] = string(request.Data())
			data["handler"] = r.pl.router[input.Name]
			d, err := json.Marshal(data)
			if err != nil {
				r.log.Error(errors.E(op, err).Error())
				return
			}

			pl := &payload.Payload{
				Context: []byte(""),
				Body:    d,
				Codec:   frame.CodecProto,
			}
			re, err := r.Exec(context.Background(), pl)
			if err != nil {
				r.log.Error(errors.E(op, err).Error())
				return
			}

			err = request.Respond(re.Body)
			if err != nil {
				r.log.Error(errors.E(op, err).Error())
				return
			}
		}(request)

	}))
	if err != nil {
		r.log.Error(errors.E(op, err).Error())
		return err
	}
	r.pl.router[input.Name] = input.Handler

	return nil
}

func (r *rpc) Exec(ctx context.Context, pld *payload.Payload) (*payload.Payload, error) {
	r.pl.mu.RLock()
	re, err := r.pl.pool.Exec(ctx, pld, nil)
	r.pl.mu.RUnlock()
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
