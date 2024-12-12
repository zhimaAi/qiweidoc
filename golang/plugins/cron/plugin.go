package cron

import (
	"context"
	"encoding/json"
	"net"
	"session_archive/golang/define"

	"github.com/google/uuid"
	jobsProto "github.com/roadrunner-server/api/v4/build/jobs/v1"
	goridgeRpc "github.com/roadrunner-server/goridge/v3/pkg/rpc"

	stdErrors "errors"
	netRpc "net/rpc"

	"github.com/roadrunner-server/errors"
	"github.com/spf13/cast"
	"go.uber.org/zap"
)

const pluginName = "cron"

type Logger interface {
	NamedLogger(name string) *zap.Logger
}

type Plugin struct {
	log       *zap.Logger
	cancel    context.CancelFunc
	rpcClient *netRpc.Client
}

func (p *Plugin) Init(log Logger) error {
	p.log = log.NamedLogger(pluginName)
	p.log.Info(`cron插件初始化成功`)
	return nil
}

func (p *Plugin) Serve() chan error {
	p.log.Info("cron插件开始服务")
	const op = errors.Op("cron_plugin_serve")

	errCh := make(chan error, 1)

	// 连接rpc
	conn, err := net.Dial("tcp", "127.0.0.1:"+cast.ToString(define.RpcPort))
	if err != nil {
		errCh <- errors.E(op, err)
		return errCh
	}
	p.rpcClient = netRpc.NewClientWithCodec(goridgeRpc.NewClientCodec(conn))

	// 清除之前以前旧的定时任务
	if err := p.clearCron(); err != nil {
		errCh <- err
		return errCh
	}

	// 启动协程来监听通知
	ctx, cancel := context.WithCancel(context.Background())
	p.cancel = cancel
	go p.listenForNotifications(ctx, errCh)

	return errCh
}

func (p *Plugin) listenForNotifications(ctx context.Context, errCh chan<- error) {
	const op = errors.Op("cron_plugin_listen_for_notifications")

	conn, err := define.PgPool.Acquire(ctx)
	if err != nil {
		errCh <- errors.E(op, err)
		return
	}
	defer conn.Release()

	_, err = conn.Exec(ctx, "LISTEN "+define.ModuleName)
	if err != nil {
		errCh <- errors.E(op, err)
		return
	}

	for {
		select {
		case <-ctx.Done():
			p.log.Info("Context cancelled, stopping notification listener")
			return
		default:
			if notification, err := conn.Conn().WaitForNotification(ctx); err != nil {
				if stdErrors.Is(err, context.Canceled) {
					p.log.Info("Notification listening stopped due to context cancellation")
					return
				}
				p.log.Error("Error waiting for notification", zap.Error(err))
				errCh <- errors.E(errors.Op("waitForNotification"), err)
			} else {
				p.log.Debug("Received notification",
					zap.String("channel", notification.Channel),
					zap.String("payload", notification.Payload),
				)
				var taskProto TaskProto
				if err := json.Unmarshal([]byte(notification.Payload), &taskProto); err != nil {
					p.log.Error(errors.E(op, err).Error())
					continue
				}

				uuidV4, err := uuid.NewRandom()
				if err != nil {
					p.log.Error(errors.E(op, err).Error())
				}
				j := &jobsProto.PushRequest{
					Job: &jobsProto.Job{
						Job:     taskProto.Job,
						Id:      uuidV4.String(),
						Payload: []byte(taskProto.Payload),
						Options: &jobsProto.Options{Pipeline: taskProto.Pipeline},
					},
				}
				if err := p.rpcClient.Call("jobs.Push", j, nil); err != nil {
					p.log.Error(errors.E(op, err).Error())
					continue
				}
			}
		}
	}
}

func (p *Plugin) Stop(ctx context.Context) error {
	const op = errors.Op("cron_plugin_stop")

	p.log.Info("cron插件停止服务")

	if err := p.clearCron(); err != nil {
		return errors.E(op, err)
	}

	if p.cancel != nil {
		p.cancel()
	}

	return nil
}

func (p *Plugin) Name() string {
	return pluginName
}

func (p *Plugin) RPC() any {
	return &rpc{
		log: p.log,
		pl:  p,
	}
}

func (p *Plugin) clearCron() error {
	const op = errors.Op("cron_plugin_clear_cron")

	sql := `
		DO $$
			DECLARE
    		job_id bigint;
		BEGIN
    		FOR job_id IN SELECT jobid FROM cron.job WHERE username = '` + define.ModuleName + `'
    		LOOP
        		PERFORM cron.unschedule(job_id);
    		END LOOP;
		END $$;
		`

	_, err := define.PgPool.Exec(context.Background(), sql)
	if err != nil {
		return errors.E(op, err)
	}

	return nil
}
