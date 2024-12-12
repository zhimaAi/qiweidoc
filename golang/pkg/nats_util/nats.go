package nats_util

import (
	"fmt"
	"strings"
	"time"

	"github.com/goccy/go-json"
	"github.com/nats-io/nats.go"
	"github.com/nats-io/nats.go/micro"
)

// RegisterNatsService
// 注册nats服务
func RegisterNatsService(nc *nats.Conn, name string, version string, description string, metadata ...map[string]string) error {
	// 检查服务是否存在
	subject, _ := micro.ControlSubject(micro.PingVerb, name, "")
	if _, err := nc.Request(subject, nil, 5*time.Second); err == nil {
		return fmt.Errorf("service %s is already running: %v", name, err)
	}

	// 注册服务
	config := micro.Config{
		Name:        name,
		Version:     version,
		Description: description,
	}
	if len(metadata) > 0 {
		config.Metadata = metadata[0]
	}

	_, err := micro.AddService(nc, config)

	return err
}

// GetNatsServiceInfo 获取指定名称的NATS服务信息
func GetNatsServiceInfo(nc *nats.Conn, serviceName string) (*micro.Info, error) {
	subject, err := micro.ControlSubject(micro.InfoVerb, serviceName, "")
	if err != nil {
		return nil, fmt.Errorf("failed to create info subject: %v", err)
	}

	// 发送请求获取服务信息
	msg, err := nc.Request(subject, nil, 2*time.Second)
	if err != nil {
		return nil, fmt.Errorf("service %s not found or not responding: %v", serviceName, err)
	}

	// 解析服务信息
	var info micro.Info
	if err := json.Unmarshal(msg.Data, &info); err != nil {
		return nil, fmt.Errorf("failed to unmarshal service info: %v", err)
	}

	return &info, nil
}

// ListNatsServices 获取所有可用的NATS服务列表
func ListNatsServices(nc *nats.Conn) ([]micro.Info, error) {
	// 使用 PING 动词来发现所有服务
	// 注意: 这里使用 $SRV.PING.* 来匹配所有服务
	subject, err := micro.ControlSubject(micro.PingVerb, "", "")
	if err != nil {
		return nil, fmt.Errorf("failed to create ping subject: %v", err)
	}

	responses := make([]micro.Info, 0)
	inbox := nc.NewRespInbox()

	sub, err := nc.SubscribeSync(inbox)
	if err != nil {
		return nil, fmt.Errorf("failed to create subscription: %v", err)
	}
	defer func(sub *nats.Subscription) {
		_ = sub.Unsubscribe()
	}(sub)

	// 发布请求
	if err := nc.PublishRequest(subject, inbox, nil); err != nil {
		return nil, fmt.Errorf("failed to publish request: %v", err)
	}

	// 收集响应
	for {
		msg, err := sub.NextMsg(1 * time.Second) // 等待1秒钟的响应
		if err != nil {
			if strings.Contains(err.Error(), "timeout") {
				break // 超时意味着没有更多的响应了
			}
			return nil, fmt.Errorf("error receiving response: %v", err)
		}

		// 对于每个响应，获取服务的详细信息
		var ping micro.Ping
		if err := json.Unmarshal(msg.Data, &ping); err != nil {
			continue // 跳过无效的响应
		}

		// 获取服务详细信息
		info, err := GetNatsServiceInfo(nc, ping.Name)
		if err != nil {
			continue // 跳过无法获取详细信息的服务
		}

		responses = append(responses, *info)
	}

	return responses, nil
}

// GetNatsServiceStats 获取服务的统计信息
func GetNatsServiceStats(nc *nats.Conn, serviceName string) (*micro.Stats, error) {
	// 使用 STATS 动词构造请求 subject
	subject, err := micro.ControlSubject(micro.StatsVerb, serviceName, "")
	if err != nil {
		return nil, fmt.Errorf("failed to create stats subject: %v", err)
	}

	// 发送请求获取统计信息
	msg, err := nc.Request(subject, nil, 2*time.Second)
	if err != nil {
		return nil, fmt.Errorf("service %s not found or not responding: %v", serviceName, err)
	}

	// 解析统计信息
	var stats micro.Stats
	if err := json.Unmarshal(msg.Data, &stats); err != nil {
		return nil, fmt.Errorf("failed to unmarshal service stats: %v", err)
	}

	return &stats, nil
}
