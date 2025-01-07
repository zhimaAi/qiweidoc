package logger

import (
	"fmt"
	"gopkg.in/natefinch/lumberjack.v2"
	"os"
	"strings"
	"time"

	"go.uber.org/zap"
	"go.uber.org/zap/zapcore"
)

// ChannelConfig configures loggers per channel.
type ChannelConfig struct {
	// Dedicated channels per logger. By default logger allocated via named logger.
	Channels map[string]*Config `mapstructure:"channels"`
}

// FileLoggerConfig structure represents configuration for the file logger
type FileLoggerConfig struct {
	// Filename is the file to write logs to.  Backup log files will be retained
	// in the same directory.  It uses <processname>-lumberjack.log in
	// os.TempDir() if empty.
	LogOutput string `mapstructure:"log_output"`

	// MaxSize is the maximum size in megabytes of the log file before it gets
	// rotated. It defaults to 100 megabytes.
	MaxSize int `mapstructure:"max_size"`

	// MaxAge is the maximum number of days to retain old log files based on the
	// timestamp encoded in their filename.  Note that a day is defined as 24
	// hours and may not exactly correspond to calendar days due to daylight
	// savings, leap seconds, etc. The default is not to remove old log files
	// based on age.
	MaxAge int `mapstructure:"max_age"`

	// MaxBackups is the maximum number of old log files to retain.  The default
	// is to retain all old log files (though MaxAge may still cause them to get
	// deleted.)
	MaxBackups int `mapstructure:"max_backups"`

	// Compress determines if the rotated log files should be compressed
	// using gzip. The default is not to perform compression.
	Compress bool `mapstructure:"compress"`
}

func (fl *FileLoggerConfig) InitDefaults() *FileLoggerConfig {
	if fl.LogOutput == "" {
		fl.LogOutput = os.TempDir()
	}

	if fl.MaxSize == 0 {
		fl.MaxSize = 100
	}

	if fl.MaxAge == 0 {
		fl.MaxAge = 24
	}

	if fl.MaxBackups == 0 {
		fl.MaxBackups = 10
	}

	return fl
}

type Config struct {
	// Mode configures logger based on some default template (development, production, off).
	Mode Mode `mapstructure:"mode"`

	// Level is the minimum enabled logging level. Note that this is a dynamic
	// level, so calling ChannelConfig.Level.SetLevel will atomically change the log
	// level of all loggers descended from this config.
	Level string `mapstructure:"level"`

	// Logger line ending. Default: "\n" for the all modes except production
	LineEnding string `mapstructure:"line_ending"`

	// Encoding sets the logger's encoding. InitDefault values are "json" and
	// "console", as well as any third-party encodings registered via
	// RegisterEncoder.
	Encoding string `mapstructure:"encoding"`

	// Output is a list of URLs or file paths to write logging output to.
	// See Open for details.
	Output []string `mapstructure:"output"`

	// ErrorOutput is a list of URLs to write internal logger errors to.
	// The default is a standard error.
	//
	// Note that this setting only affects internal errors; for sample code that
	// sends error-level logs to a different location from info- and debug-level
	// logs, see the package-level AdvancedConfiguration example.
	ErrorOutput []string `mapstructure:"err_output"`

	// File logger options
	FileLogger *FileLoggerConfig `mapstructure:"file_logger_options"`

	// UseLocalTime is used to set the encoder to use local time instead of UTC.
	UseLocalTime bool `mapstructure:"use_local_time"`

	// ShowCaller is used to set the encoder to show the caller.
	ShowCaller bool `mapstructure:"show_caller"`

	Prefix string `mapstructure:"prefix"`
}

// BuildLogger converts config into Zap configuration.
func (cfg *Config) BuildLogger() (*zap.Logger, error) {
	var zCfg zap.Config
	var callerKey = zapcore.OmitKey
	var encodeCaller zapcore.CallerEncoder = nil
	if cfg.ShowCaller {
		callerKey = "caller"
		encodeCaller = ColoredShortCallerEncoder
	}
	switch Mode(strings.ToLower(string(cfg.Mode))) {
	case off, none:
		return zap.NewNop(), nil
	case production:
		encodeTime := utcEpochTimeEncoder
		if cfg.UseLocalTime {
			encodeTime = localTimeEncoder
		}
		zCfg = zap.Config{
			Level:       zap.NewAtomicLevelAt(zap.InfoLevel),
			Development: false,
			Encoding:    "json",
			EncoderConfig: zapcore.EncoderConfig{
				TimeKey:        "ts",
				LevelKey:       "level",
				NameKey:        "logger",
				CallerKey:      callerKey,
				FunctionKey:    zapcore.OmitKey,
				MessageKey:     "msg",
				StacktraceKey:  zapcore.OmitKey,
				LineEnding:     cfg.LineEnding,
				EncodeLevel:    zapcore.LowercaseLevelEncoder,
				EncodeTime:     encodeTime,
				EncodeDuration: zapcore.SecondsDurationEncoder,
				EncodeCaller:   encodeCaller,
			},
			OutputPaths:      []string{"stderr"},
			ErrorOutputPaths: []string{"stderr"},
		}
	case development:
		encodeTime := utcISO8601TimeEncoder
		if cfg.UseLocalTime {
			encodeTime = localTimeEncoder
		}
		zCfg = zap.Config{
			Level:       zap.NewAtomicLevelAt(zap.DebugLevel),
			Development: true,
			Encoding:    "console",
			EncoderConfig: zapcore.EncoderConfig{
				TimeKey:        "ts",
				LevelKey:       "level",
				NameKey:        "logger",
				CallerKey:      callerKey,
				FunctionKey:    zapcore.OmitKey,
				MessageKey:     "msg",
				StacktraceKey:  zapcore.OmitKey,
				LineEnding:     cfg.LineEnding,
				EncodeLevel:    ColoredLevelEncoder,
				EncodeName:     ColoredNameEncoder,
				EncodeTime:     encodeTime,
				EncodeDuration: zapcore.StringDurationEncoder,
				EncodeCaller:   encodeCaller,
			},
			OutputPaths:      []string{"stderr"},
			ErrorOutputPaths: []string{"stderr"},
		}
	case raw:
		zCfg = zap.Config{
			Level:    zap.NewAtomicLevelAt(zap.InfoLevel),
			Encoding: "console",
			EncoderConfig: zapcore.EncoderConfig{
				MessageKey: "message",
				LineEnding: cfg.LineEnding,
			},
			OutputPaths:      []string{"stderr"},
			ErrorOutputPaths: []string{"stderr"},
		}
	default:
		encodeTime := utcISO8601TimeEncoder
		if cfg.UseLocalTime {
			encodeTime = localTimeEncoder
		}
		zCfg = zap.Config{
			Level:    zap.NewAtomicLevelAt(zap.DebugLevel),
			Encoding: "console",
			EncoderConfig: zapcore.EncoderConfig{
				TimeKey:        "T",
				LevelKey:       "L",
				NameKey:        "N",
				CallerKey:      callerKey,
				FunctionKey:    zapcore.OmitKey,
				MessageKey:     "M",
				StacktraceKey:  zapcore.OmitKey,
				LineEnding:     cfg.LineEnding,
				EncodeLevel:    ColoredLevelEncoder,
				EncodeName:     ColoredNameEncoder,
				EncodeTime:     encodeTime,
				EncodeDuration: zapcore.StringDurationEncoder,
				EncodeCaller:   encodeCaller,
			},
			OutputPaths:      []string{"stderr"},
			ErrorOutputPaths: []string{"stderr"},
		}
	}

	if cfg.Level != "" {
		level := zap.NewAtomicLevel()
		if err := level.UnmarshalText([]byte(cfg.Level)); err == nil {
			zCfg.Level = level
		}
	}

	if cfg.Encoding != "" {
		zCfg.Encoding = cfg.Encoding
	}

	if len(cfg.Output) != 0 {
		zCfg.OutputPaths = cfg.Output
	}

	if len(cfg.ErrorOutput) != 0 {
		zCfg.ErrorOutputPaths = cfg.ErrorOutput
	}

	// if we also have a file logger specified in the config
	// init it
	// otherwise - return standard config
	if cfg.FileLogger != nil {
		fileEncoderConfig := zCfg.EncoderConfig
		if cfg.ShowCaller {
			fileEncoderConfig.EncodeCaller = ShortCallerEncoderWithPadding
		}

		// init absent options
		cfg.FileLogger.InitDefaults()

		w := zapcore.AddSync(
			&lumberjack.Logger{
				Filename:   cfg.FileLogger.LogOutput,
				MaxSize:    cfg.FileLogger.MaxSize,
				MaxAge:     cfg.FileLogger.MaxAge,
				MaxBackups: cfg.FileLogger.MaxBackups,
				Compress:   cfg.FileLogger.Compress,
			},
		)

		core := zapcore.NewCore(
			zapcore.NewJSONEncoder(fileEncoderConfig),
			w,
			zCfg.Level,
		)
		if cfg.ShowCaller {
			return zap.New(core, zap.AddCaller()), nil
		}
		return zap.New(core), nil
	}

	return zCfg.Build()
}

// InitDefault Initialize default logger
func (cfg *Config) InitDefault() {
	if cfg.Mode == "" {
		cfg.Mode = development
	}
	if cfg.Level == "" {
		cfg.Level = "debug"
	}

	if cfg.LineEnding == "" {
		cfg.LineEnding = zapcore.DefaultLineEnding
	}
}

func utcISO8601TimeEncoder(t time.Time, enc zapcore.PrimitiveArrayEncoder) {
	enc.AppendString(t.UTC().Format("2006-01-02T15:04:05-0700"))
}

func utcEpochTimeEncoder(t time.Time, enc zapcore.PrimitiveArrayEncoder) {
	enc.AppendInt64(t.UTC().UnixNano())
}

func localTimeEncoder(t time.Time, enc zapcore.PrimitiveArrayEncoder) {
	enc.AppendString(t.Local().Format("2006-01-02 15:04:05.000 "))
}

func ColoredShortCallerEncoder(caller zapcore.EntryCaller, enc zapcore.PrimitiveArrayEncoder) {
	enc.AppendString(fmt.Sprintf("\x1b[35m%-30s\x1b[0m", caller.TrimmedPath()))
}

func ShortCallerEncoderWithPadding(caller zapcore.EntryCaller, enc zapcore.PrimitiveArrayEncoder) {
	enc.AppendString(fmt.Sprintf("%-30s", caller.TrimmedPath()))
}
