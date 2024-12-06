package module

import (
	"context"
	"encoding/json"
	errors2 "errors"
	"github.com/jackc/pgx/v5"
	"github.com/roadrunner-server/errors"
	"session_archive/golang/define"
)

type RunningPhpModuleInfo struct {
	*PhpModuleInfo
	RpcPort  uint `json:"rpc_port"`
	HttpPort uint `json:"http_port"`
	GrpcPort uint `json:"grpc_port"`
	Paused   bool `json:"paused"`
}

// GetRunningPhpModuleInfo 根据 key 从数据库中获取 value，并解析成 RunningPhpModuleInfo 类型
func GetRunningPhpModuleInfo(key string) (*RunningPhpModuleInfo, error) {
	const op = errors.Op("module_plugin_get_running_php_module_info")

	// 查询
	sql := `
	SELECT value
	FROM public.modules
	WHERE key = $1;
	`
	var value string
	err := define.PgPool.QueryRow(context.Background(), sql, key).Scan(&value)
	if err != nil {
		if errors2.Is(pgx.ErrNoRows, err) {
			return nil, nil // 返回 nil 表示未找到
		}
		return nil, errors.E(op, err)
	}

	// 反序列化 JSONB 值为 RunningPhpModuleInfo
	var info RunningPhpModuleInfo
	if err := json.Unmarshal([]byte(value), &info); err != nil {
		return nil, errors.E(op, err)
	}

	return &info, nil
}

// UpdateRunningPhpModuleInfo 将 RunningPhpModuleInfo 序列化并存储到数据库中
func UpdateRunningPhpModuleInfo(key string, info *RunningPhpModuleInfo) error {
	const op = errors.Op("module_plugin_update_running_php_module_info")

	// 将 info 序列化为 JSON
	value, err := json.Marshal(info)
	if err != nil {
		return errors.E(op, err)
	}

	// 插入或更新数据
	sql := `
	INSERT INTO public.modules (key, value)
	VALUES ($1, $2)
	ON CONFLICT (key) DO UPDATE
	SET value = EXCLUDED.value;
	`

	_, err = define.PgPool.Exec(context.Background(), sql, key, string(value))
	if err != nil {
		return errors.E(op, err)
	}

	return nil
}
