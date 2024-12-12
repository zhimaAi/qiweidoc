package initialize

import (
	"context"
	"fmt"
	"log"
	"os"
	"session_archive/golang/define"
	"time"

	"github.com/jackc/pgx/v5/pgxpool"
)

func InitDb() error {
	// 构建 DSN 如果环境变量中没有提供
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

	dsn := fmt.Sprintf("postgres://%s:%s@%s:%s/%s", dbUsername, dbPassword, dbHost, dbPort, dbDatabase)
	config, err := pgxpool.ParseConfig(dsn)
	if err != nil {
		log.Fatalf("Unable to parse pool config: %v", err)
		return err
	}

	// 设置连接池的配置参数
	config.MaxConns = 5                        // 最大连接数
	config.MinConns = 1                        // 最小空闲连接数
	config.MaxConnLifetime = 30 * time.Minute  // 连接的最大生命周期
	config.MaxConnIdleTime = 15 * time.Minute  // 连接的最大空闲时间
	config.HealthCheckPeriod = 1 * time.Minute // 健康检查周期

	define.PgPool, err = pgxpool.NewWithConfig(context.Background(), config)
	if err != nil {
		log.Fatalf("Unable to create connection pool: %v", err)
		return err
	}
	log.Println("Database connection pool initialized.")
	return nil
}

func CloseDb() {
	fmt.Println("db closing...")
	define.PgPool.Close()
	fmt.Println("db closed")
}
