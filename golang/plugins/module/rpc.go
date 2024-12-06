package module

import (
	"github.com/roadrunner-server/errors"
	"go.uber.org/zap"
)

type rpc struct {
	pl  *Plugin
	log *zap.Logger
}

func (r *rpc) Hello(input string, output *string) error {
	*output = "hello, " + input
	return nil
}

// Enable 启用指定模块
func (r *rpc) Enable(input string, output *string) error {
	var phpModuleInfo *PhpModuleInfo
	for _, info := range r.pl.phpModuleInfoList {
		if input == info.Name {
			phpModuleInfo = &info
		}
	}
	if phpModuleInfo == nil {
		return errors.Errorf("模块%s不存在", input)
	}

	runningInfo, err := GetRunningPhpModuleInfo(input)
	if err != nil {
		return errors.Errorf("模块%s的运行信息没找到", input)
	}
	if !runningInfo.Paused {
		return nil
	}

	// 查找可用端口号
	rpcPortList, err := findAvailableRpcPorts(1)
	if err != nil {
		return err
	}
	httpPortList, err := findAvailableHttpPorts(1)
	if err != nil {
		return err
	}
	grpcPortList, err := findAvailableGrpcPorts(1)
	if err != nil {
		return err
	}

	// 启动进程
	err = r.pl.Enable(*phpModuleInfo, rpcPortList[0], httpPortList[0], grpcPortList[0])
	if err != nil {
		return err
	}

	// 保存运行时信息
	runningInfo.Paused = false
	runningInfo.RpcPort = rpcPortList[0]
	runningInfo.HttpPort = httpPortList[0]
	runningInfo.GrpcPort = grpcPortList[0]
	if err := UpdateRunningPhpModuleInfo(input, runningInfo); err != nil {
		return err
	}

	return nil
}

// Disable 禁用指定模块
func (r *rpc) Disable(input string, output *string) error {
	var phpModuleInfo *PhpModuleInfo
	for _, info := range r.pl.phpModuleInfoList {
		if input == info.Name {
			phpModuleInfo = &info
			break
		}
	}
	if phpModuleInfo == nil {
		return errors.Errorf("模块%s不存在", input)
	}
	runningInfo, err := GetRunningPhpModuleInfo(input)
	if err != nil {
		return errors.Errorf("模块%s的运行信息没找到", input)
	}
	if runningInfo.Paused {
		return nil
	}

	err = r.pl.Disable(*phpModuleInfo)
	if err != nil {
		return err
	}

	// 保存运行时信息
	runningPhpModuleInfo := &RunningPhpModuleInfo{
		PhpModuleInfo: phpModuleInfo,
		Paused:        true,
	}
	if err = UpdateRunningPhpModuleInfo(input, runningPhpModuleInfo); err != nil {
		return err
	}

	return nil
}

func (r *rpc) List(input string, output *[]RunningPhpModuleInfo) (err error) {
	for _, info := range r.pl.phpModuleInfoList {
		t, err := GetRunningPhpModuleInfo(info.Name)
		if err != nil || t == nil {
			*output = append(*output, RunningPhpModuleInfo{
				PhpModuleInfo: &info,
				Paused:        true,
			})
		} else {
			*output = append(*output, *t)
		}
	}
	return nil
}

// Info 获取指定模块信息
func (r *rpc) Info(input string, output *RunningPhpModuleInfo) (err error) {
	info, err := GetRunningPhpModuleInfo(input)
	if err != nil {
		r.log.Error("error", zap.Error(err))
		return err
	}
	if info == nil {
		r.log.Info("no module info found", zap.String("key", input))
		return nil // 或者你可以返回一个特定的错误
	}
	*output = *info
	return nil
}
