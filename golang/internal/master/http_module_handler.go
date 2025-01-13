package master

import (
	"fmt"
	"github.com/gofiber/fiber/v2"
	"github.com/gofiber/fiber/v2/log"
	"session_archive/golang/internal/master/define"
)

// startModule 启动模块
func startModule(ctx *fiber.Ctx) error {
	// 参数验证
	type body struct {
		Name string `json:"name"`
	}
	var data body
	if err := ctx.BodyParser(&data); err != nil {
		return ctx.Status(fiber.StatusBadRequest).JSON(fiber.Map{
			"message": "解析请求体失败",
		})
	}
	if len(data.Name) == 0 {
		return ctx.Status(fiber.StatusUnprocessableEntity).JSON(fiber.Map{
			"message": "模块名称不能为空",
		})
	}

	moduleInfo, err := readModuleManifest(data.Name)
	if err != nil {
		return ctx.Status(fiber.StatusBadRequest).JSON(fiber.Map{
			"message": "读取模块配置失败: " + err.Error(),
		})
	}

	// 获取可用的rpc端口
	rpcPortList, err := findAvailableRpcPorts(1)
	if err != nil {
		log.Errorf("获取rpc端口失败%v", err)
		return ctx.Status(fiber.StatusBadRequest).JSON(fiber.Map{
			"message": "获取rpc端口失败",
		})
	}

	// 获取可用的http端口
	httpPortList, err := findAvailableHttpPorts(1)
	if err != nil {
		log.Errorf("获取http端口失败%v", err)
		return ctx.Status(fiber.StatusBadRequest).JSON(fiber.Map{
			"message": "获取http端口失败",
		})
	}

	// 启动模块
	err = StartModule(data.Name, rpcPortList[0], httpPortList[0], moduleInfo.Plugins)
	if err != nil {
		log.Errorf("启动模块失败%v", err)
		return ctx.Status(fiber.StatusBadRequest).JSON(fiber.Map{
			"message": "启动模块失败",
		})
	}

	return ctx.Status(fiber.StatusOK).JSON(fiber.Map{
		"message": "ok",
	})
}

// stopModule 停止模块
func stopModule(ctx *fiber.Ctx) error {
	type body struct {
		Name string `json:"name"`
	}
	var data body
	if err := ctx.BodyParser(&data); err != nil {
		return ctx.Status(fiber.StatusBadRequest).JSON(fiber.Map{
			"message": "解析请求体失败",
		})
	}
	if len(data.Name) == 0 {
		return ctx.Status(fiber.StatusUnprocessableEntity).JSON(fiber.Map{
			"message": "模块名称不能为空",
		})
	}

	go func() {
		if err := StopModule(data.Name); err != nil {
			log.Errorf("停止模块失败%v", err)
		}
		// 删除模块运行状态
		if err := deleteModuleOpen(data.Name); err != nil {
			log.Errorf(fmt.Sprintf("更新运行状态失败: %v", err))
		}
	}()

	return ctx.Status(fiber.StatusOK).JSON(fiber.Map{
		"message": "ok",
	})
}

// getModuleList 获取所有模块信息
func getModuleList(ctx *fiber.Ctx) error {
	_ = scanModules()

	var list []define.ModuleRespInfo
	for _, moduleInfo := range define.ModuleList {
		var item define.ModuleRespInfo
		item.Name = moduleInfo.Name
		item.Version = moduleInfo.Version
		item.StartedAt = moduleInfo.StartedAt
		item.Paused = moduleInfo.RR == nil
		list = append(list, item)
	}

	return ctx.Status(fiber.StatusOK).JSON(list)
}

// getModuleInfo 获取指定模块信息
func getModuleInfo(ctx *fiber.Ctx) error {
	type body struct {
		Name string `json:"name"`
	}
	var data body
	if err := ctx.BodyParser(&data); err != nil {
		return ctx.Status(fiber.StatusBadRequest).JSON(fiber.Map{
			"message": "解析请求体失败",
		})
	}
	if len(data.Name) == 0 {
		return ctx.Status(fiber.StatusUnprocessableEntity).JSON(fiber.Map{
			"message": "模块名称不能为空",
		})
	}

	if _, err := readModuleManifest(data.Name); err != nil {
		return ctx.Status(fiber.StatusBadRequest).JSON(fiber.Map{
			"message": err.Error(),
		})
	}

	_ = scanModules()

	var item define.ModuleRespInfo
	for _, moduleInfo := range define.ModuleList {
		if moduleInfo.Name == data.Name {
			item.Name = moduleInfo.Name
			item.Version = moduleInfo.Version
			item.StartedAt = moduleInfo.StartedAt
			item.Paused = moduleInfo.RR == nil
		}
	}
	if len(item.Name) == 0 {
		return ctx.Status(fiber.StatusBadRequest).JSON(fiber.Map{
			"message": "没有找到模块信息",
		})
	}

	return ctx.Status(fiber.StatusOK).JSON(item)
}
