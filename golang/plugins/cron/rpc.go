package cron

import (
	"context"
	"encoding/json"
	"fmt"
	"session_archive/golang/define"
	"strings"

	"github.com/jackc/pgx/v5/pgconn"
	"github.com/roadrunner-server/errors"
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

type TaskProto struct {
	Job      string `json:"job"`
	Payload  string `json:"payload"`
	Pipeline string `json:"pipeline"`
}

type SaveCronRequest struct {
	Name string    `json:"name"`
	Cron string    `json:"cron"`
	Task TaskProto `json:"task"`
}

func (r *rpc) Save(input SaveCronRequest, output *bool) (err error) {
	const op = errors.Op("cron_plugin_rpc_saveCron")
	if len(input.Name) == 0 {
		err = errors.E(op, "name不能为空")
		return
	}
	if len(input.Cron) == 0 {
		err = errors.E(op, "cron不能为空")
		return
	}
	if len(input.Task.Job) == 0 {
		err = errors.E(op, "task.job不能为空")
		return
	}

	task, e := json.Marshal(input.Task)
	if e != nil {
		err = errors.E(op, e)
		return
	}

	var result int
	sql := `select cron.schedule('%s', '%s', $$ notify %s, '%s' $$)`
	sql = fmt.Sprintf(sql, input.Name, input.Cron, define.ModuleName, task)
	err = define.PgPool.QueryRow(context.Background(), sql).Scan(&result)
	if err != nil {
		r.log.Error("保存定时任务失败",
			zap.String("sql", sql),
			zap.Error(err),
		)
	}
	*output = (result != 0)

	return
}

func (r *rpc) Delete(input string, output *bool) (err error) {
	sql := `select cron.unschedule($1)`
	if err = define.PgPool.QueryRow(context.Background(), sql, input).Scan(output); err != nil {
		pgErr, ok := err.(*pgconn.PgError)
		r.log.Error(pgErr.Code)
		r.log.Error(pgErr.Message)
		if ok && pgErr.Code == "XX000" && strings.HasPrefix(pgErr.Message, "could not find valid entry for job") {
			r.log.Info("尝试删除不存在的定时任务", zap.String("name", input))
			*output = false
			return nil
		}

		r.log.Error("删除定时任务失败",
			zap.String("name", input),
			zap.Error(err),
		)
	}

	return
}
