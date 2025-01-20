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
		const op2 = "cron_plugin:RunCron"

		r.pl.mutex.Lock()
		router, exists := r.pl.routerMap[input.Name]
		if !exists {
			r.pl.mutex.Unlock()
			r.log.Error(errors.E(op2, "任务不存在").Error())
			return
		}

		if router.IsRunning {
			r.pl.mutex.Unlock()
			r.log.Warn(errors.E(op2, input.Name+" is running").Error())
			return
		}

		router.IsRunning = true
		r.pl.routerMap[input.Name] = router

		data := make(map[string]string)
		data["data"] = router.Data
		data["handler"] = router.Handler
		r.pl.mutex.Unlock()

		d, err := json.Marshal(data)
		if err != nil {
			r.log.Error(errors.E(op2, err).Error())
			return
		}

		pl := &payload.Payload{
			Context: []byte(""),
			Body:    d,
			Codec:   frame.CodecProto,
		}

		r.log.Debug("开始执行")
		_, err = r.pl.exec(context.Background(), pl)

		r.pl.mutex.Lock()
		if routerNew, ok := r.pl.routerMap[input.Name]; ok {
			routerNew.IsRunning = false
			r.pl.routerMap[input.Name] = routerNew
		}
		r.pl.mutex.Unlock()

		if err != nil {
			r.log.Error(errors.E(op2, err).Error())
			return
		}
	})
	if err != nil {
		return errors.E(op, err)
	}
	r.pl.routerMap[input.Name] = Router{
		Id:        entryId,
		Spec:      input.Spec,
		Handler:   input.Handler,
		Data:      input.Data,
		IsRunning: false,
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
