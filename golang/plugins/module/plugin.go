package module

import (
	"context"
	"fmt"
	"github.com/goccy/go-json"
	"github.com/roadrunner-server/errors"
	goridgeRpc "github.com/roadrunner-server/goridge/v3/pkg/rpc"
	"github.com/spf13/cast"
	"go.uber.org/zap"
	"net"
	netRpc "net/rpc"
	"os"
	"os/exec"
)

const pluginName = "module"

type Logger interface {
	NamedLogger(name string) *zap.Logger
}

type Configurer interface {
	UnmarshalKey(name string, out any) error
	Has(name string) bool
}

type Config struct {
	Nats struct {
		Listen string `mapstructure:"listen"`
	} `mapstructure:"nats"`

	Rpc struct {
		Listen string `mapstructure:"listen"`
	} `mapstructure:"rpc"`
}

type PhpModuleInfo struct {
	Name                string   `json:"name"`
	Version             string   `json:"version"`
	RequiredMainVersion int      `json:"required_main_version"`
	Title               string   `json:"title"`
	Description         string   `json:"description"`
	Intro               string   `json:"intro"`
	Features            []string `json:"features"`
	PublicDir           string   `json:"public_dir"`
	ProtoFileList       []string `json:"proto_file_list"`
	ConsumerRouteList   []struct {
		Name         string `json:"name"`
		Count        uint   `json:"count"`
		DeleteOnStop bool   `json:"delete_on_stop"`
	} `json:"consumer_route_list"`
}

type Plugin struct {
	log               *zap.Logger
	cfg               *Config
	phpModuleInfoList []PhpModuleInfo
	rpcConn           net.Conn
	rpcClient         *netRpc.Client
}

func (p *Plugin) Init(cfg Configurer, log Logger) error {
	const op = errors.Op("module_plugin_init")

	p.log = log.NamedLogger(pluginName)
	p.log.Info("module插件初始化")

	c := Config{}
	err := cfg.UnmarshalKey("rpc", &c.Rpc)
	if err != nil {
		return errors.E(op, err)
	}
	p.cfg = &c

	if err = p.ensureTable(); err != nil {
		return errors.E(op, err)
	}

	// 解析执行php自解释脚本获取模块数据
	var mainInfo *PhpModuleInfo
	entries, err := os.ReadDir("php/modules")
	if err != nil {
		return errors.E(op, err)
	}
	for _, entry := range entries {
		if !entry.IsDir() {
			continue
		}

		p.log.Info("获取模块" + entry.Name() + "的信息")
		cmd := exec.Command("php", "php/yii", "get-current-module-info")
		cmd.Env = append(os.Environ(), "MODULE_NAME="+entry.Name())
		output, err := cmd.CombinedOutput()
		if err != nil {
			p.log.Info(fmt.Sprintf("模块"+entry.Name()+"的信息获取失败: %v", err))
			continue
		}

		var info PhpModuleInfo
		err = json.Unmarshal(output, &info)
		if err != nil {
			return err
		}

		if info.Name == "main" {
			mainInfo = &info
		} else {
			p.phpModuleInfoList = append(p.phpModuleInfoList, info)
		}
	}
	// 检查是否有 main 模块
	if mainInfo == nil {
		return errors.E(op, errors.Errorf("缺少main模块"))
	}

	// 将 main 模块插入到列表的第一位
	p.phpModuleInfoList = append([]PhpModuleInfo{*mainInfo}, p.phpModuleInfoList...)

	return nil
}

func (p *Plugin) Serve() chan error {
	const op = errors.Op("module_plugin_serve")

	errCh := make(chan error, 1)
	var err error

	// 连接rpc
	p.rpcConn, err = net.Dial("tcp", p.cfg.Rpc.Listen[6:])
	if err != nil {
		errCh <- errors.E(op, err)
		return errCh
	}

	p.rpcClient = netRpc.NewClientWithCodec(goridgeRpc.NewClientCodec(p.rpcConn))

	// 找到可用端口
	rpcPortList, err := findAvailableRpcPorts(cast.ToUint(len(p.phpModuleInfoList)))
	if err != nil {
		errCh <- errors.E(op, err)
		return errCh
	}
	httpPortList, err := findAvailableHttpPorts(cast.ToUint(len(p.phpModuleInfoList)))
	if err != nil {
		errCh <- errors.E(op, err)
		return errCh
	}
	grpcPortList, err := findAvailableGrpcPorts(cast.ToUint(len(p.phpModuleInfoList)))
	if err != nil {
		errCh <- errors.E(op, err)
		return errCh
	}

	// 遍历模块数据启动模块进程
	for i, info := range p.phpModuleInfoList {
		p.log.Info("启动模块" + info.Name)

		// 从nats中获取模块运行时数据，如果设置了暂停就不再启用
		runningInfo, err := GetRunningPhpModuleInfo(info.Name)
		if err != nil {
			errCh <- errors.E(op, err)
			return errCh
		}

		// 第一次运行默认不启动，只把模块信息存起来
		if runningInfo == nil && info.Name != "main" {
			p.log.Info("模块" + info.Name + "刚安装默认不启动")
			runningPhpModuleInfo := &RunningPhpModuleInfo{PhpModuleInfo: &info, Paused: true}
			if err := UpdateRunningPhpModuleInfo(info.Name, runningPhpModuleInfo); err != nil {
				errCh <- errors.E(op, err)
				return errCh
			}
			continue
		}

		// 如果设置过禁用，也不启用，但是要更新模块最新信息
		if runningInfo != nil && runningInfo.Paused {
			p.log.Info("模块" + info.Name + "已停用")
			runningPhpModuleInfo := &RunningPhpModuleInfo{PhpModuleInfo: &info, Paused: true}
			if err := UpdateRunningPhpModuleInfo(info.Name, runningPhpModuleInfo); err != nil {
				errCh <- errors.E(op, err)
				return errCh
			}
			continue
		}

		// 创建数据库模式
		p.log.Info("创建模块" + info.Name + "的数据库schema")
		if err := p.createPgSchema(info); err != nil {
			errCh <- errors.E(op, err)
			return errCh
		}

		// 启动进程
		err = p.Enable(info, rpcPortList[i], httpPortList[i], grpcPortList[i])
		if err != nil {
			errCh <- errors.E(op, err)
			return errCh
		}

		// 保存运行时信息
		runningPhpModuleInfo := &RunningPhpModuleInfo{
			PhpModuleInfo: &info,
			RpcPort:       rpcPortList[i],
			HttpPort:      httpPortList[i],
			GrpcPort:      grpcPortList[i],
			Paused:        false,
		}
		if err := UpdateRunningPhpModuleInfo(info.Name, runningPhpModuleInfo); err != nil {
			errCh <- errors.E(op, err)
			return errCh
		}
	}

	return nil
}

func (p *Plugin) Stop(ctx context.Context) error {
	const op = errors.Op("module_plugin_stop")

	err := p.rpcConn.Close()
	if err != nil {
		return errors.E(op, err)
	}

	for _, info := range p.phpModuleInfoList {
		_ = p.Disable(info)
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

func findAvailablePorts(start, end, count uint) ([]uint, error) {
	var ports []uint

	for port := start; port <= end && len(ports) < cast.ToInt(count); port++ {
		listener, err := net.Listen("tcp", fmt.Sprintf(":%d", port))
		if err == nil {
			ports = append(ports, port)
			_ = listener.Close() // 忽略关闭错误
		}
	}

	if len(ports) < cast.ToInt(count) {
		return nil, fmt.Errorf("only found %d available ports, need %d", len(ports), count)
	}
	return ports, nil
}

func findAvailableRpcPorts(count uint) ([]uint, error) {
	return findAvailablePorts(6002, 6100, count)
}

func findAvailableHttpPorts(count uint) ([]uint, error) {
	return findAvailablePorts(8081, 8100, count)
}

func findAvailableGrpcPorts(count uint) ([]uint, error) {
	return findAvailablePorts(9001, 9100, count)
}
