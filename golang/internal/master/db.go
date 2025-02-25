package master

import (
    "context"
    "fmt"
    "github.com/jackc/pgx/v5/pgxpool"
    "os"
    "session_archive/golang/internal/master/define"
)

// InitPostgres 初始化数据库
func InitPostgres() {

    dbHost := os.Getenv(`DB_HOST`)
    dbPort := os.Getenv(`DB_PORT`)
    dbDatabase := os.Getenv(`DB_DATABASE`)
    dbUsername := os.Getenv(`DB_USERNAME`)
    dbPassword := os.Getenv(`DB_PASSWORD`)
    if len(dbHost) == 0 {
        dbHost = "db"
    }
    if len(dbPort) == 0 {
        dbPort = "5432"
    }
    if len(dbDatabase) == 0 {
        dbDatabase = "postgres"
    }
    if len(dbUsername) == 0 {
        dbUsername = "postgres"
    }
    if len(dbPassword) == 0 {
        dbPassword = "postgres"
    }

    ctx := context.Background()

    // 连接数据库
    dsn := fmt.Sprintf("postgres://%s:%s@%s:%s/%s", dbUsername, dbPassword, dbHost, dbPort, dbDatabase)
    config, err := pgxpool.ParseConfig(dsn)
    if err != nil {
        panic(err)
    }

    define.PgPool, err = pgxpool.NewWithConfig(ctx, config)
    if err != nil {
        panic(err)
    }
}

// createModuleSchema 创建指定模块的专属schema
func createModuleSchema(name string) error {
    sql := `
		DO $$
		BEGIN
			-- 检查并创建角色
			IF NOT EXISTS (
                SELECT 1 FROM pg_roles WHERE rolname = '` + name + `'
            ) THEN
				CREATE ROLE ` + name + ` WITH LOGIN PASSWORD '` + name + `';
			END IF;

            -- 创建 schema
            CREATE SCHEMA IF NOT EXISTS ` + name + `;

            -- 授权访问所属schema的所有权限
            GRANT ALL ON SCHEMA ` + name + ` TO ` + name + `;
            GRANT ALL ON ALL TABLES IN SCHEMA ` + name + ` TO ` + name + `;
            GRANT ALL ON ALL SEQUENCES IN SCHEMA ` + name + ` TO ` + name + `;
            ALTER DEFAULT PRIVILEGES IN SCHEMA ` + name + ` GRANT ALL PRIVILEGES ON TABLES TO ` + name + `;
            ALTER DEFAULT PRIVILEGES IN SCHEMA ` + name + ` GRANT ALL PRIVILEGES ON SEQUENCES TO ` + name + `;

            -- 授权访问schema public的使用权
            GRANT USAGE ON SCHEMA public TO ` + name + `;
            GRANT SELECT ON ALL TABLES IN SCHEMA public TO ` + name + `;
            ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT SELECT ON TABLES TO ` + name + `;

            -- 授权访问schema cron
            GRANT USAGE ON SCHEMA cron TO ` + name + `;

            -- 授权schema main的使用权
            IF '` + name + `' <> 'main' THEN
			    GRANT USAGE ON SCHEMA main TO ` + name + `;
			    GRANT SELECT ON ALL TABLES IN SCHEMA main TO ` + name + `;
			    ALTER DEFAULT PRIVILEGES IN SCHEMA main GRANT SELECT ON TABLES TO ` + name + `;
                ALTER ROLE ` + name + ` SET search_path TO ` + name + `, main, public;
            ELSE
                ALTER ROLE ` + name + ` SET search_path TO ` + name + `, public;
		    END IF;
		END $$;
	`

    _, err := define.PgPool.Exec(context.Background(), sql)
    if err != nil {
        return err
    }
    return nil
}
