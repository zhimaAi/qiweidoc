package minio

import (
	"context"
	"fmt"
	"github.com/minio/minio-go/v7"
	"github.com/roadrunner-server/errors"
	"io"
	"strings"
)

const (
	hashBucketName = "hash"
)

// initHashBucket 初始化hash存储桶
func (p *Plugin) initHashBucket() error {
	ctx := context.Background()
	exists, err := p.minioClient.BucketExists(ctx, hashBucketName)
	if err != nil {
		return errors.E(errors.Op("连接到minio失败"))
	}

	if !exists {
		if err = p.minioClient.MakeBucket(ctx, hashBucketName, minio.MakeBucketOptions{}); err != nil {
			return errors.E(errors.Op(fmt.Sprintf("创建hash存储桶%s失败", hashBucketName)), err)
		}
		p.log.Info(fmt.Sprintf("创建hash存储桶%s成功", hashBucketName))
	}

	return nil
}

// getHashObjectName 生成hash对象的路径
func getHashObjectName(bucketName, md5 string) string {
	return fmt.Sprintf("%s/%s", strings.TrimPrefix(bucketName, "minio-"), md5)
}

// checkFileExistsByHash 通过MD5检查文件是否在指定bucket中已存在
func (p *Plugin) checkFileExistsByHash(bucketName, md5 string) (string, error) {
	if err := p.initHashBucket(); err != nil {
		return "", err
	}

	ctx := context.Background()
	objectName := getHashObjectName(bucketName, md5)

	// 尝试获取hash对象
	obj, err := p.minioClient.GetObject(ctx, hashBucketName, objectName, minio.GetObjectOptions{})
	if err != nil {
		return "", errors.E(errors.Op("获取hash对象失败"), err)
	}
	defer func(obj *minio.Object) {
		err := obj.Close()
		if err != nil {
			p.log.Error(err.Error())
		}
	}(obj)

	// 检查hash对象是否存在
	_, err = obj.Stat()
	if err != nil {
		if strings.Contains(err.Error(), "The specified key does not exist") {
			return "", nil // hash记录不存在
		}
		return "", errors.E(errors.Op("获取hash对象状态失败"), err)
	}

	// 读取存储的文件路径
	objectPath, err := io.ReadAll(obj)
	if err != nil {
		return "", errors.E(errors.Op("读取文件路径失败"), err)
	}

	// 验证目标文件是否真实存在
	targetObj, err := p.minioClient.GetObject(ctx, bucketName, string(objectPath), minio.GetObjectOptions{})
	if err != nil {
		return "", errors.E(errors.Op("获取目标文件失败"), err)
	}
	defer func(targetObj *minio.Object) {
		err := targetObj.Close()
		if err != nil {
			p.log.Error(err.Error())
		}
	}(targetObj)

	// 检查目标文件状态
	_, err = targetObj.Stat()
	if err != nil {
		if strings.Contains(err.Error(), "The specified key does not exist") {
			// 目标文件不存在，删除hash记录
			_ = p.deleteHash(bucketName, md5)
			return "", nil
		}
		return "", errors.E(errors.Op("获取目标文件状态失败"), err)
	}

	return string(objectPath), nil
}

// saveHash 保存文件hash信息
func (p *Plugin) saveHash(bucketName, md5, objectPath string) error {
	ctx := context.Background()
	objectName := getHashObjectName(bucketName, md5)

	// 直接上传文件路径
	_, err := p.minioClient.PutObject(
		ctx,
		hashBucketName,
		objectName,
		strings.NewReader(objectPath),
		int64(len(objectPath)),
		minio.PutObjectOptions{ContentType: "text/plain"},
	)
	if err != nil {
		return errors.E(errors.Op("保存hash信息失败"), err)
	}

	return nil
}

// deleteHash 删除文件hash信息
func (p *Plugin) deleteHash(bucketName, md5 string) error {
	ctx := context.Background()
	objectName := getHashObjectName(bucketName, md5)

	err := p.minioClient.RemoveObject(ctx, hashBucketName, objectName, minio.RemoveObjectOptions{})
	if err != nil {
		return errors.E(errors.Op("删除hash信息失败"), err)
	}

	return nil
}
