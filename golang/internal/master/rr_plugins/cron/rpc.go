package cron

import (
	"context"
	"encoding/json"
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
	Spec    string `json:"spec"`
	Handler string `json:"handler"`
	Data    string `json:"data"`
}

func (r *rpc) Add(input Input, output *string) error {
	r.pl.mutex.Lock()
	defer r.pl.mutex.Unlock()
	const op = errors.Op("cron_plugin:AddCron")
	if len(input.Name) == 0 {
		return errors.E(op, "name不能为空")
	}
	if len(input.Spec) == 0 {
		return errors.E(op, "spec不能为空")
	}
	if len(input.Handler) == 0 {
		return errors.E(op, "handler不能为空")
	}
	if len(input.Data) == 0 {
		return errors.E(op, "data不能为空")
	}
	_, ok := r.pl.routerMap[input.Name]
	if ok {
		return errors.E(op, "重复添加")
	}

	entryId, err := r.pl.cron.AddFunc(input.Spec, func() {
		data := make(map[string]string)
		data["data"] = input.Data
		data["handler"] = input.Handler
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
		r.log.Debug("开始执行")
		_, err = r.pl.exec(context.Background(), pl)
		if err != nil {
			r.log.Error(errors.E(op, err).Error())
			return
		}
	})
	if err != nil {
		return errors.E(op, err)
	}
	r.pl.routerMap[input.Name] = Router{
		Id:      entryId,
		Spec:    input.Spec,
		Handler: input.Handler,
		Data:    input.Data,
	}

	return nil
}

func (r *rpc) Del(name string, output *string) error {
	r.pl.mutex.Lock()
	defer r.pl.mutex.Unlock()
	const op = errors.Op("plugin_cron:DelCron")
	router, ok := r.pl.routerMap[name]
	if !ok {
		return errors.E(op, "任务不存在")
	}
	r.pl.cron.Remove(router.Id)
	delete(r.pl.routerMap, name)

	return nil
}
