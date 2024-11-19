package minio

import (
	"go.uber.org/zap"
)

type rpc struct {
	pl  *Plugin
	log *zap.Logger
}

// GetFileByMD5 根据md5查找文件
func (r *rpc) GetFileByMD5(input GetFileByMD5Request, output *string) (err error) {
	r.pl.log.Debug("接收到RPC请求",
		zap.String("method", "GetFileByMD5"),
		zap.Any("input", input),
	)

	*output, err = r.pl.GetFileByMD5(input)
	if err != nil {
		r.pl.log.Error("RPC请求失败",
			zap.String("method", "GetFileByMD5"),
			zap.Any("input", input),
			zap.Any("err", err),
		)
	} else {
		r.pl.log.Debug("RPC请求成功",
			zap.String("method", "GetFileByMD5"),
			zap.Any("input", input),
			zap.Any("output", output),
		)
	}

	return
}

// UploadFile 上传文件到minio服务器
func (r *rpc) UploadFile(input UploadFileRequest, output *UploadFileResponse) (err error) {
	r.pl.log.Info("接收到RPC请求",
		zap.String("method", "UploadFile"),
		zap.Any("input", input),
	)

	*output, err = r.pl.UploadFile(input)
	if err != nil {
		r.pl.log.Error("RPC请求失败",
			zap.String("method", "UploadFile"),
			zap.Any("input", input),
			zap.Any("err", err),
		)
	} else {
		r.pl.log.Info("RPC请求成功",
			zap.String("method", "UploadFile"),
			zap.Any("input", input),
			zap.Any("output", output),
		)
	}

	return
}

// DeleteFile 从minio服务器删除文件
func (r *rpc) DeleteFile(url string, output *string) (err error) {
	r.pl.log.Info("接收到RPC请求",
		zap.String("method", "DeleteFile"),
		zap.Any("input", url))

	*output = ""
	err = r.pl.DeleteFile(url)
	if err != nil {
		r.pl.log.Error("RPC请求失败",
			zap.String("method", "DeleteFile"),
			zap.String("input", url),
			zap.Any("err", err))
	} else {
		r.pl.log.Error("RPC请求成功",
			zap.String("method", "DeleteFile"),
			zap.String("input", url),
			zap.Any("output", output),
		)
	}

	return
}
