package main

import (
	"context"
	"github.com/aws/aws-sdk-go-v2/aws"
	"github.com/aws/aws-sdk-go-v2/credentials"
	"github.com/aws/aws-sdk-go-v2/feature/s3/manager"
	"github.com/aws/aws-sdk-go-v2/service/s3"
	"github.com/roadrunner-server/errors"
	"strings"
)

type FetchMediaDataRequest struct {
	CorpId     string `json:"corp_id"`
	ChatSecret string `json:"chat_secret"`
	SdkFileId  string `json:"sdk_file_id"`
	Proxy      string `json:"proxy"`
	Passwd     string `json:"passwd"`
	Timeout    int    `json:"timeout"`

	StorageEndpoint   string `json:"storage_endpoint"`
	StorageRegion     string `json:"storage_region"`
	StorageAccessKey  string `json:"storage_access_key"`
	StorageSecretKey  string `json:"storage_secret_key"`
	StorageBucketName string `json:"storage_bucket_name"`
	StorageObjectKey  string `json:"storage_object_key"`
}

type FileInfo struct {
	Hash string `json:"hash"`
	Mime string `json:"mime"`
	Size int64  `json:"size"`
}

func FetchAndStreamMediaData(input *FetchMediaDataRequest) (*FileInfo, error) {
	const Op = "plugin_wxfinance: FetchAndStreamMediaData"

	if len(input.CorpId) == 0 {
		return nil, errors.E(Op, "缺少corp_id参数")
	}
	if len(input.ChatSecret) == 0 {
		return nil, errors.E(Op, `缺少chat_secret参数`)
	}
	if len(input.SdkFileId) == 0 {
		return nil, errors.E(Op, `缺少sdk_file_id参数`)
	}
	if len(input.StorageEndpoint) == 0 {
		return nil, errors.E(Op, `缺少storage_endpoint参数`)
	}
	if len(input.StorageAccessKey) == 0 {
		return nil, errors.E(Op, `缺少storage_access_key参数`)
	}
	if len(input.StorageSecretKey) == 0 {
		return nil, errors.E(Op, `缺少storage_secret_key参数`)
	}
	if len(input.StorageBucketName) == 0 {
		return nil, errors.E(Op, `缺少storage_bucket_name参数`)
	}
	if len(input.StorageObjectKey) == 0 {
		return nil, errors.E(Op, `缺少storage_object_key参数`)
	}

	client := s3.New(s3.Options{
		BaseEndpoint: aws.String(input.StorageEndpoint),
		Region:       input.StorageRegion,
		UsePathStyle: true,
		Credentials:  aws.NewCredentialsCache(credentials.NewStaticCredentialsProvider(input.StorageAccessKey, input.StorageSecretKey, "")),
	})

	sdk, err := NewSDK()
	if err != nil {
		return nil, errors.E(Op, err)
	}

	err = sdk.Init(input.CorpId, input.ChatSecret)
	if err != nil {
		return nil, errors.E(Op, err)
	}

	streamingReader := NewStreamingReader(sdk, input.SdkFileId, input.Proxy, input.Passwd, input.Timeout)

	uploader := manager.NewUploader(client)
	fileUploadInfo, err := uploader.Upload(context.TODO(), &s3.PutObjectInput{
		Bucket: aws.String(input.StorageBucketName),
		Key:    aws.String(input.StorageObjectKey),
		Body:   streamingReader,
	})
	if err != nil {
		return nil, errors.E(Op, err)
	}

	fileInfo := &FileInfo{
		Hash: extract32BitETag(*fileUploadInfo.ETag),
	}

	headOutput, err := client.HeadObject(context.TODO(), &s3.HeadObjectInput{
		Bucket: aws.String(input.StorageBucketName),
		Key:    aws.String(input.StorageObjectKey),
	})
	if err != nil {
		return nil, errors.E(Op, err)
	}

	fileInfo.Size = aws.ToInt64(headOutput.ContentLength)
	fileInfo.Mime = aws.ToString(headOutput.ContentType)

	return fileInfo, nil
}

func extract32BitETag(etag string) string {
	// 移除 ETag 开头和结尾的双引号（如果存在）
	etag = strings.Trim(etag, "\"")

	// 检查是否包含连字符（表示分片上传）
	if strings.Contains(etag, "-") {
		// 分片上传的情况，提取连字符前的部分
		parts := strings.Split(etag, "-")
		if len(parts) > 0 {
			return parts[0]
		}
	}

	// 如果 ETag 长度为 32，直接返回（标准 MD5）
	if len(etag) == 32 {
		return etag
	}

	return etag
}
