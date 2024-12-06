package wxfinance

import (
	"go.uber.org/zap"
	"session_archive/golang/plugins/minio"
)

type rpc struct {
	pl  *Plugin
	log *zap.Logger
}

// FetchData 拉取数据
func (r *rpc) FetchData(input *FetchDataRequest, output *[]ChatMsg) (err error) {
	r.log.Debug(`收到RPC请求`,
		zap.String("method", "FetchData"),
		zap.String("corp_id", input.CorpId),
		zap.Uint64("chat_seq", input.ChatSeq),
		zap.Uint32("limit", input.Limit),
	)

	*output, err = r.pl.FetchData(input)
	if err != nil {
		r.log.Error("RPC请求失败",
			zap.String("method", "FetchData"),
			zap.String("corp_id", input.CorpId),
			zap.Uint64("chat_seq", input.ChatSeq),
			zap.Uint32("limit", input.Limit),
			zap.Error(err),
		)
	} else {
		r.log.Debug("RPC请求成功",
			zap.String("method", "FetchData"),
			zap.String("corp_id", input.CorpId),
			zap.Uint64("chat_seq", input.ChatSeq),
			zap.Uint32("limit", input.Limit),
		)
	}

	return
}

// FetchMediaData 从企微下载文件，然后上传到minio返回url链接
func (r *rpc) FetchMediaData(input *FetchMediaDataRequest, output *minio.UploadFileResponse) (err error) {
	r.pl.log.Debug("收到RPC请求",
		zap.String("method", "FetchMediaData"),
		zap.Any("input", input),
	)

	result, err := r.pl.FetchMediaData(input)
	if err != nil {
		r.pl.log.Error("RPC请求失败",
			zap.String("method", "FetchMediaData"),
			zap.Any("input", input),
			zap.Any("err", err),
		)
	} else {
		*output = *result
		r.pl.log.Debug("RPC请求成功",
			zap.String("method", "FetchMediaData"),
			zap.Any("input", input),
			zap.Any("output", output),
		)
	}

	return nil
}
