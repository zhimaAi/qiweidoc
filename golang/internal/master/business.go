package master

import (
	"encoding/json"
	"errors"
	"fmt"
	"github.com/gofiber/fiber/v2/log"
	"net"
	"os"
	"session_archive/golang/internal/master/define"
	"sync"
	"time"
)

// StartModule 启动模块
func StartModule(name string, rpcPort, httpPort int, plugins []string) error {
	define.ModuleMutex.Lock()
	defer define.ModuleMutex.Unlock()

	// 创建数据库模式
	err := createModuleSchema(name)
	if err != nil {
		return errors.New(fmt.Sprintf("给模块%s创建数据库模式失败: %v", name, err))
	}

	// 检查模块是否已启动
	moduleInfo, ok := define.ModuleList[name]
	if ok && moduleInfo.RR != nil {
		log.Infof("模块%s已经启动了", name)
		return nil
	}

	// 启动模块
	container, err := startRR(name, rpcPort, httpPort, plugins)
	if err != nil {
		return errors.New(fmt.Sprintf("启动模块%s失败: %v", name, err))
	}

	// 把模块运行时信息存起来
	moduleInfo.RR = container
	moduleInfo.StartedAt = time.Now().Format("2006-01-02 15:04:05.000")
	moduleInfo.HttpPort = httpPort
	moduleInfo.RpcPort = rpcPort
	define.ModuleList[name] = moduleInfo

	// 保存模块启用状态
	if err = saveModuleOpen(name); err != nil {
		return errors.New(fmt.Sprintf("更新模块记录失败%v", err))
	}

	return nil
}

// StopModule 停止模块
func StopModule(name string) error {
	define.ModuleMutex.Lock()
	defer define.ModuleMutex.Unlock()

	module, ok := define.ModuleList[name]
	if !ok || module.RR == nil {
		log.Infof("模块%s已停止", name)
		return nil
	}

	// 停止模块进程
	log.Infof("准备停止模块进程%s", name)
	if err := module.RR.Stop(); err != nil {
		log.Infof("停止模块进程%s失败", name)
	}

	// 更新运行时信息
	module.RR = nil
	module.RpcPort = 0
	module.HttpPort = 0
	module.StartedAt = ""
	define.ModuleList[name] = module

	return nil
}

func scanModules() error {
	entries, err := os.ReadDir("php/modules")
	if err != nil {
		return err
	}

	define.ModuleMutex.Lock()
	defer define.ModuleMutex.Unlock()

	// 遍历模块目录
	for _, entry := range entries {
		if !entry.IsDir() {
			continue
		}
		manifest, err := readModuleManifest(entry.Name())
		if err != nil {
			log.Errorf("读取模块%s的manifest.json失败: %v", entry.Name(), err)
			continue
		}

		// 把模块信息存入到map中
		if existsModuleInfo, ok := define.ModuleList[entry.Name()]; ok {
			existsModuleInfo.Name = manifest.Name
			existsModuleInfo.Version = manifest.Version
			existsModuleInfo.Plugins = manifest.Plugins
			define.ModuleList[entry.Name()] = existsModuleInfo
		} else {
			define.ModuleList[entry.Name()] = define.ModuleInfo{
				Name:    manifest.Name,
				Version: manifest.Version,
				Plugins: manifest.Plugins,
			}
		}
	}

	return nil
}

func readModuleManifest(name string) (*define.ModuleManifest, error) {
	// 检查前端文件是否存在
	//if file := fmt.Sprintf("php/modules/%s/public/build/index.html", name); !fileExists(file) {
	//	return nil, errors.New("模块信息不完整: 缺少前端代码")
	//}

	// 读取模块的配置文件
	path := fmt.Sprintf("php/modules/%s/manifest.json", name)
	data, err := os.ReadFile(path)
	if err != nil {
		return nil, err
	}
	var manifest define.ModuleManifest
	err = json.Unmarshal(data, &manifest)
	if err != nil {
		return nil, err
	}

	return &manifest, nil
}

// InitModules 初始化模块
func InitModules() {
	if err := scanModules(); err != nil {
		panic(err)
	}

	// 获取可用的rpc端口
	rpcPortList, err := findAvailableRpcPorts(len(define.ModuleList))
	if err != nil {
		panic(err)
	}

	// 获取可用的http端口
	httpPortList, err := findAvailableHttpPorts(len(define.ModuleList))
	if err != nil {
		panic(err)
	}

	// 先启动main模块
	mainModuleInfo, ok := define.ModuleList["main"]
	if !ok {
		panic("缺少main模块")
	}
	err = StartModule("main", rpcPortList[0], httpPortList[0], mainModuleInfo.Plugins)
	if err != nil {
		panic(fmt.Sprintf("main模块启动失败:%v", err))
	}

	// 启动其它模块
	index := 0
	for name, moduleInfo := range define.ModuleList {
		// 跳过main模块
		if name == "main" {
			continue
		}
		index += 1

		if hasOpened := moduleHasOpen(name); !hasOpened {
			continue
		}

		err = StartModule(name, rpcPortList[index], httpPortList[index], moduleInfo.Plugins)
		if err != nil {
			panic(err)
		}
	}
}

func StopAllModules() {
	var wg sync.WaitGroup
	for _, moduleInfo := range define.ModuleList {
		wg.Add(1)
		go func() {
			defer wg.Done()
			if err := StopModule(moduleInfo.Name); err != nil {
				log.Errorf("停止模块%s失败%v", moduleInfo.Name, err)
			}
		}()
	}
	wg.Wait()
}

func findAvailableRpcPorts(count int) ([]int, error) {
	return findAvailablePorts(6001, 6100, count)
}

func findAvailableHttpPorts(count int) ([]int, error) {
	return findAvailablePorts(8081, 8100, count)
}

func findAvailablePorts(start, end, count int) ([]int, error) {
	var ports []int

	for port := start; port <= end && len(ports) < count; port++ {
		listener, err := net.Listen("tcp", fmt.Sprintf(":%d", port))
		if err == nil {
			ports = append(ports, port)
			_ = listener.Close() // 忽略关闭错误
		}
	}

	if len(ports) < count {
		return nil, fmt.Errorf("only found %d available ports, need %d", len(ports), count)
	}
	return ports, nil
}
