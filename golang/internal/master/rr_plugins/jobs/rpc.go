package jobs

import (
	"github.com/roadrunner-server/errors"
	"go.uber.org/zap"
)

type rpc struct {
	pl  *Plugin
	log *zap.Logger
}

type PushInput struct {
	Queue   string `json:"queue"` // 队列名称，可选，默认为 "default"
	Handler string `json:"handler"`
	Data    string `json:"data"`
}

type QueueSizeInput struct {
	Queue string `json:"queue"` // 队列名称，可选，默认为 "default"
}

type QueueSizeOutput struct {
	Size int `json:"size"`
}

type QueueInfo struct {
	Waiting    int `json:"waiting"`    // 等待中的任务数
	Processing int `json:"processing"` // 正在处理的任务数
	Total      int `json:"total"`      // 总任务数
}

type AllQueuesSizeOutput struct {
	Queues map[string]QueueInfo `json:"queues"`
}

// Push 向队列中添加异步任务
func (r *rpc) Push(input PushInput, output *string) error {
	const op = errors.Op("jobs_plugin:Push")

	if len(input.Handler) == 0 {
		return errors.E(op, "handler不能为空")
	}
	if len(input.Data) == 0 {
		return errors.E(op, "data不能为空")
	}

	// 如果未指定队列名称，使用默认队列
	queueName := input.Queue
	if queueName == "" {
		queueName = "default"
	}

	job := Job{
		Queue:   queueName,
		Handler: input.Handler,
		Data:    input.Data,
	}

	err := r.pl.Push(job)
	if err != nil {
		return errors.E(op, err)
	}

	*output = "ok"

	return nil
}

// QueueSize 查询所有队列中有多少待执行任务
func (r *rpc) QueueSize(_ string, output *AllQueuesSizeOutput) error {
	queuesInfo := r.pl.AllQueuesInfo()

	// 转换为 QueueInfo 格式
	result := make(map[string]QueueInfo)
	for name, info := range queuesInfo {
		result[name] = QueueInfo{
			Waiting:    info.Waiting,
			Processing: info.Processing,
			Total:      info.Total,
		}
	}

	output.Queues = result
	r.log.Debug("查询所有队列大小", zap.Any("queues", result))
	return nil
}
