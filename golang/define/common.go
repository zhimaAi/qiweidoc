package define

import (
	"github.com/jackc/pgx/v5/pgxpool"
	"github.com/nats-io/nats.go"
)

var PgPool *pgxpool.Pool

var NatsAddr string
var NatsConn *nats.Conn

var ModuleName string
var HttpPort uint
var RpcPort uint
