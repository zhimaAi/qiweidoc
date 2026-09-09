package main

import (
	"context"
	"crypto/md5"
	"encoding/hex"
	"io"
	"time"

	"github.com/aws/aws-sdk-go-v2/aws"
	"github.com/aws/aws-sdk-go-v2/credentials"
	"github.com/aws/aws-sdk-go-v2/feature/s3/manager"
	"github.com/aws/aws-sdk-go-v2/service/s3"
	"github.com/roadrunner-server/errors"
)

type FetchMediaDataRequest struct {
	CorpId         string `json:"corp_id"`
	ChatSecret     string `json:"chat_secret"`
	SdkFileId      string `json:"sdk_file_id"`
	Proxy          string `json:"proxy"`
	Passwd         string `json:"passwd"`
	Timeout        int    `json:"timeout"`
	OverallTimeout int    `json:"overall_timeout"`

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
	defer sdk.Close()

	err = sdk.Init(input.CorpId, input.ChatSecret)
	if err != nil {
		return nil, errors.E(Op, err)
	}

	overallTimeout := input.OverallTimeout
	if overallTimeout <= 0 {
		overallTimeout = 3300
	}
	ctx, cancel := context.WithTimeout(context.Background(), time.Duration(overallTimeout)*time.Second)
	defer cancel()

	streamingReader := NewStreamingReader(sdk, input.SdkFileId, input.Proxy, input.Passwd, input.Timeout)
	hasher := md5.New()

	uploader := manager.NewUploader(client)
	_, err = uploader.Upload(ctx, &s3.PutObjectInput{
		Bucket: aws.String(input.StorageBucketName),
		Key:    aws.String(input.StorageObjectKey),
		Body:   io.TeeReader(streamingReader, hasher),
	})
	if err != nil {
		return nil, errors.E(Op, err)
	}

	fileInfo := &FileInfo{
		Hash: hex.EncodeToString(hasher.Sum(nil)),
	}

	headOutput, err := client.HeadObject(ctx, &s3.HeadObjectInput{
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
