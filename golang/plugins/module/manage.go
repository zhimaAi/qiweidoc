package module

import (
	"context"
	serviceV1 "github.com/roadrunner-server/api/v4/build/service/v1"
	"github.com/roadrunner-server/errors"
	"github.com/spf13/cast"
	"session_archive/golang/define"
	"strings"
)

// Enable 启动app进程
func (p *Plugin) Enable(info PhpModuleInfo, rpcPort, httpPort, grpcPort uint) error {
	const op = errors.Op("module_plugin_start")

	env := make(map[string]string)

	// 版本信息
	env[`MODULE_NAME`] = info.Name

	// rpc
	env[`MODULE_RPC_PORT`] = cast.ToString(rpcPort)

	// http
	env[`MODULE_HTTP_PORT`] = cast.ToString(httpPort)
	env[`MODULE_STATIC_DIR`] = info.PublicDir

	// grpc
	env[`MODULE_GRPC_PORT`] = cast.ToString(grpcPort)
	env[`MODULE_PROTO_FILES`] = strings.Join(info.ProtoFileList, ",")

	// 消费者
	var routeNameList []string
	for _, route := range info.ConsumerRouteList {
		routeNameList = append(routeNameList, route.Name)

		key := "CONSUME_" + strings.ToUpper(route.Name) + "_COUNT"
		env[key] = cast.ToString(route.Count)

		key = "CONSUME_" + strings.ToUpper(route.Name) + "_DELETE"
		env[key] = cast.ToString(route.DeleteOnStop)
	}
	env["CONSUME_LIST"] = strings.Join(routeNameList, ",")

	// 创建进程
	req := &serviceV1.Create{
		Name:              info.Name,
		Command:           "golang/build/app " + info.Name,
		ProcessNum:        1,
		RemainAfterExit:   true,
		RestartSec:        3,
		ServiceNameInLogs: true,
		Env:               env,
	}
	resp := &serviceV1.Response{}

	err := p.rpcClient.Call("service.Create", req, resp)
	if err != nil {
		return errors.E(op, err)
	}

	return nil
}

// Disable 禁用指定模块进程
func (p *Plugin) Disable(info PhpModuleInfo) error {
	const op = errors.Op("module_plugin_Pause")

	req := &serviceV1.Service{
		Name: info.Name,
	}
	resp := &serviceV1.Response{}

	err := p.rpcClient.Call("service.Terminate", req, resp)
	if err != nil {
		return errors.E(op, err)
	}

	return nil
}

func (p *Plugin) Install() error {
	return nil
}

func (p *Plugin) Upgrade() error {
	return nil
}

func (p *Plugin) Remove() error {
	return nil
}

func (p *Plugin) createPgSchema(moduleMeta PhpModuleInfo) error {
	const op = errors.Op("module_plugin_create_pg_schema")

	// 构造 SQL 语句
	sql := `
		DO $$
		BEGIN
			-- 检查并创建角色
			IF NOT EXISTS (
                SELECT 1 FROM pg_roles WHERE rolname = '` + moduleMeta.Name + `'
            ) THEN
				CREATE ROLE ` + moduleMeta.Name + ` WITH LOGIN PASSWORD '` + moduleMeta.Name + `';
			END IF;

            -- 创建 schema
            CREATE SCHEMA IF NOT EXISTS ` + moduleMeta.Name + `;

            -- 授权访问所属schema的所有权限
            GRANT ALL ON SCHEMA ` + moduleMeta.Name + ` TO ` + moduleMeta.Name + `;
            GRANT ALL ON ALL TABLES IN SCHEMA ` + moduleMeta.Name + ` TO ` + moduleMeta.Name + `;
            GRANT ALL ON ALL SEQUENCES IN SCHEMA ` + moduleMeta.Name + ` TO ` + moduleMeta.Name + `;
            ALTER DEFAULT PRIVILEGES IN SCHEMA ` + moduleMeta.Name + ` GRANT ALL PRIVILEGES ON TABLES TO ` + moduleMeta.Name + `;
            ALTER DEFAULT PRIVILEGES IN SCHEMA ` + moduleMeta.Name + ` GRANT ALL PRIVILEGES ON SEQUENCES TO ` + moduleMeta.Name + `;

            -- 授权访问schema public的使用权
            GRANT USAGE ON SCHEMA public TO ` + moduleMeta.Name + `;
            GRANT SELECT ON ALL TABLES IN SCHEMA public TO ` + moduleMeta.Name + `;
            ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT SELECT ON TABLES TO ` + moduleMeta.Name + `;

            -- 授权schema main的使用权
            IF '` + moduleMeta.Name + `' <> 'main' THEN
			    GRANT USAGE ON SCHEMA main TO ` + moduleMeta.Name + `;
			    GRANT SELECT ON ALL TABLES IN SCHEMA main TO ` + moduleMeta.Name + `;
			    ALTER DEFAULT PRIVILEGES IN SCHEMA main GRANT SELECT ON TABLES TO ` + moduleMeta.Name + `;
                ALTER ROLE ` + moduleMeta.Name + ` SET search_path TO ` + moduleMeta.Name + `, main, public;
            ELSE
                ALTER ROLE ` + moduleMeta.Name + ` SET search_path TO ` + moduleMeta.Name + `, public;
		    END IF;
		END $$;
	`
	_, err := define.PgPool.Exec(context.Background(), sql)
	if err != nil {
		return errors.E(op, err)
	}

	return nil
}

// ensureTable 创建一个 modules 表，主键是 key，值是 value，用 jsonb 存储
func (p *Plugin) ensureTable() error {
	const op = errors.Op("module_plugin_ensure_table")

	// 创建表的 SQL 语句
	sql := `
	CREATE TABLE IF NOT EXISTS public.modules (
		key TEXT PRIMARY KEY,
		value JSONB NOT NULL
	);
	`

	_, err := define.PgPool.Exec(context.Background(), sql)
	if err != nil {
		return errors.E(op, err)
	}

	return nil
}
