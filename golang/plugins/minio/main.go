package minio

import (
	"bufio"
	"context"
	"crypto/md5"
	"encoding/hex"
	"fmt"
	"github.com/minio/minio-go/v7"
	"github.com/roadrunner-server/errors"
	"go.uber.org/zap"
	"io"
	"os"
	"path/filepath"
	"strings"
	"time"
)

const (
	//默认bucket名称
	defaultBucketName = `minio-default`
	// MD5标签key
	md5TagKey = "file-md5"
)

type GetFileByMD5Request struct {
	BucketName string `json:"bucket_name,omitempty"`
	MD5        string `json:"md5"`
}

// GetFileByMD5 通过md5检查文件是否已经上传过了，如果上传过了且文件已存在则直接返回文件链接
func (p *Plugin) GetFileByMD5(request GetFileByMD5Request) (string, error) {
	if len(request.BucketName) == 0 {
		request.BucketName = defaultBucketName
	}

	existsObjectName, err := p.checkFileExistsByHash(request.BucketName, request.MD5)
	if err != nil {
		return "", errors.E(errors.Op(`通过md5检查文件失败`), err)
	}
	if len(existsObjectName) > 0 {
		return fmt.Sprintf("/%s/%s", request.BucketName, existsObjectName), nil
	} else {
		return "", nil
	}
}

type UploadFileRequest struct {
	FilePath       string   `json:"file_path"` //仅该字段是必须的
	BucketName     string   `json:"bucket_name,omitempty"`
	OriginFileName string   `json:"origin_file_name,omitempty"`
	TagList        []string `json:"tag_list,omitempty"`
}

type UploadFileResponse struct {
	Url string `json:"url"`
	MD5 string `json:"md5"`
}

func (p *Plugin) UploadFile(request UploadFileRequest) (UploadFileResponse, error) {
	result := UploadFileResponse{}

	if len(request.FilePath) == 0 {
		return result, errors.E(errors.Op("文件路径不能为空"))
	}

	if len(request.BucketName) == 0 {
		request.BucketName = defaultBucketName
	}
	if !strings.HasPrefix(request.BucketName, `minio-`) {
		return result, errors.E(errors.Op(`bucket name必须以minio-开头`))
	}

	// 检查文件是否存在
	// 检查文件是否存在并一次性获取所需信息
	file, md5sum, size, err := processFile(request.FilePath)
	if err != nil {
		return result, errors.E(errors.Op("文件处理失败"), err)
	}
	defer func(file *os.File) {
		err := file.Close()
		if err != nil {
			p.log.Error(err.Error())
		}
	}(file)

	// 给文件名赋默认值
	if len(request.OriginFileName) == 0 {
		request.OriginFileName = filepath.Base(file.Name())
	}

	// 从hash桶中检查文件是否存在，如果存在就不用重复上传了
	existsObjectName, err := p.checkFileExistsByHash(request.BucketName, md5sum)
	if err != nil {
		return result, errors.E(errors.Op(`从hash桶中检查文件出错`), err)
	}
	if len(existsObjectName) > 0 {
		p.log.Info("文件已存在直接返回", zap.String("origin_file_name", request.OriginFileName))
		result.Url = fmt.Sprintf("/%s/%s", request.BucketName, existsObjectName)
		result.MD5 = md5sum
		return result, nil
	}

	// 检查和初始化bucket
	if err := p.initBucket(request.BucketName); err != nil {
		return result, errors.E(errors.Op(`初始化bucket失败`), err)
	}

	// 准备标签
	tags := convertTagsToMinioTags(request.TagList)
	tags[md5TagKey] = md5sum

	// 上传选项
	opts := minio.PutObjectOptions{
		UserTags: tags,
	}

	// 上传
	objectName := generateObjectName(request.OriginFileName, md5sum)
	p.log.Info("开始上传文件", zap.String("origin_file_name", request.OriginFileName))
	_, err = p.minioClient.PutObject(
		context.Background(),
		request.BucketName,
		objectName,
		file,
		size,
		opts,
	)
	if err != nil {
		return result, errors.E(errors.Op(`文件上传失败`), err.Error())
	}
	p.log.Info("文件上传成功",
		zap.String("origin_file_name", request.OriginFileName),
		zap.String("object_name", objectName),
	)

	if err = p.saveHash(request.BucketName, md5sum, objectName); err != nil {
		p.log.Error(`文件名写入hash关联失败`)
	}

	result.Url = fmt.Sprintf("/%s/%s", request.BucketName, objectName)
	result.MD5 = md5sum
	return result, nil
}

// DeleteFile
// 根据url删除文件
func (p *Plugin) DeleteFile(url string) error {

	// 移除开头的斜杠
	url = strings.TrimPrefix(url, "/")

	// 分割URL获取bucket和对象路径
	parts := strings.SplitN(url, "/", 2)
	if len(parts) != 2 {
		return errors.E(errors.Op("无效的URL格式"))
	}

	bucketName := parts[0]
	objectName := parts[1]

	// 验证bucket名称
	if !strings.HasPrefix(bucketName, "minio-") {
		return errors.E(errors.Op("bucket name必须以minio-开头"))
	}

	// 检查bucket是否存在
	exists, err := p.minioClient.BucketExists(context.Background(), bucketName)
	if err != nil {
		return errors.E(errors.Op("检查bucket存在失败"), err)
	}
	if !exists {
		return errors.E(errors.Op(fmt.Sprintf("bucket %s 不存在", bucketName)))
	}

	// 获取文件标签
	tags, err := p.minioClient.GetObjectTagging(context.Background(), bucketName, objectName, minio.GetObjectTaggingOptions{})
	if err != nil {
		if strings.Contains(err.Error(), "The specified key does not exist") {
			return errors.E(errors.Op("文件不存在"))
		}
		return errors.E(errors.Op("获取文件标签失败"), err)
	}

	// 获取MD5值
	var md5sum string
	if val, ok := tags.ToMap()[md5TagKey]; ok {
		md5sum = val
	}

	// 删除文件
	err = p.minioClient.RemoveObject(context.Background(), bucketName, objectName, minio.RemoveObjectOptions{})
	if err != nil {
		return errors.E(errors.Op("删除文件失败"), err)
	}
	p.log.Info("文件上传成功", zap.String("object_name", objectName))

	// 如果有MD5值，删除对应的hash记录
	if md5sum != "" {
		if err = p.deleteHash(bucketName, md5sum); err != nil {
			p.log.Error("删除hash记录失败", zap.Any("err", err))
		}
	} else {
		p.log.Warn("文件没有MD5标签", zap.String("object_name", objectName))
	}

	return nil
}

func (p *Plugin) initBucket(bucketName string) error {
	ctx := context.Background()
	exists, err := p.minioClient.BucketExists(ctx, bucketName)
	if err != nil {
		return errors.E(errors.Op("连接到minio失败"))
	}

	if !exists {
		if err = p.minioClient.MakeBucket(ctx, bucketName, minio.MakeBucketOptions{ObjectLocking: true}); err != nil {
			return errors.E(errors.Op(fmt.Sprintf("创建minio存储桶%s失败", bucketName)), err)
		}
		p.log.Info("创建桶成功", zap.String("bucket_name", bucketName))
	}

	// 设置桶为公开访问策略
	policy := fmt.Sprintf(`{
        "Version": "2012-10-17",
        "Statement": [
            {
                "Effect": "Allow",
                "Principal": {"AWS": "*"},
                "Action": ["s3:GetObject"],
                "Resource": ["arn:aws:s3:::%s/*"]
            }
        ]
    }`, bucketName)
	if err = p.minioClient.SetBucketPolicy(ctx, bucketName, policy); err != nil {
		return errors.E(errors.Op("设置存储桶失败"), err)
	}

	return nil
}

// processFile 处理文件，返回文件句柄、MD5值和文件大小
func processFile(filePath string) (*os.File, string, int64, error) {
	file, err := os.Open(filePath)
	if err != nil {
		return nil, "", 0, errors.E(errors.Op("打开文件失败"), err)
	}
	reader := bufio.NewReader(file)
	hash := md5.New()

	// 使用多写入器同时计算MD5
	if _, err := io.Copy(hash, reader); err != nil {
		err := file.Close()
		if err != nil {
			return nil, "", 0, err
		}
		return nil, "", 0, errors.E(errors.Op("计算MD5失败"), err)
	}

	// 获取文件大小
	stat, err := file.Stat()
	if err != nil {
		err := file.Close()
		if err != nil {
			return nil, "", 0, err
		}
		return nil, "", 0, errors.E(errors.Op("获取文件信息失败"), err)
	}

	// 重置文件指针到开始位置
	if _, err := file.Seek(0, 0); err != nil {
		err := file.Close()
		if err != nil {
			return nil, "", 0, err
		}
		return nil, "", 0, errors.E(errors.Op("重置文件指针失败"), err)
	}

	md5sum := hex.EncodeToString(hash.Sum(nil))
	return file, md5sum, stat.Size(), nil
}

// 生成对象存储路径
// 生成格式如: 2024/02/15/md5/filename.ext
func generateObjectName(fileName string, md5sum string) string {
	now := time.Now()
	return fmt.Sprintf(
		"%d/%02d/%02d/%s/%s",
		now.Year(),
		now.Month(),
		now.Day(),
		md5sum,
		fileName,
	)
}

// 将标签列表转换为 Minio 的标签格式
func convertTagsToMinioTags(tagList []string) map[string]string {
	tags := make(map[string]string)
	for i, tag := range tagList {
		key := fmt.Sprintf("tag%d", i+1)
		tags[key] = tag
	}
	return tags
}
