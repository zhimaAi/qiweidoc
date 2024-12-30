package wxfinance

// 对企业微信提供的接口做进一步的封装，方便使用

import (
	"context"
	"crypto/rsa"
	"crypto/x509"
	"encoding/base64"
	"encoding/pem"
	errors2 "errors"
	"fmt"
	"github.com/aws/aws-sdk-go-v2/aws"
	"github.com/aws/aws-sdk-go-v2/credentials"
	"github.com/aws/aws-sdk-go-v2/feature/s3/manager"
	"github.com/aws/aws-sdk-go-v2/service/s3"
	"github.com/roadrunner-server/errors"
	"go.uber.org/zap"
	"net/http"
	"os"
	"strings"
)

type FetchDataRequest struct {
	CorpId               string `json:"corp_id"`
	ChatSecret           string `json:"chat_secret"`
	ChatPrivateKey       string `json:"chat_private_key"`
	ChatPublicKeyVersion int    `json:"chat_public_key_version"`
	ChatSeq              uint64 `json:"chat_seq"`
	Limit                uint32 `json:"limit"`
	Proxy                string `json:"proxy"`
	Passwd               string `json:"passwd"`
	Timeout              int    `json:"timeout"`
}

type FetchDataResponse struct {
	Messages []ChatMsg `json:"messages"`
}

type ChatMsg struct {
	Seq                 int64  `json:"seq"`
	MsgID               string `json:"msgid"`
	EncryptedData       string `json:"encrypted_data"`
	DecryptFailedReason string `json:"decrypt_failed_reason"`
	DecryptedData       string `json:"decrypted_data"`
}

// FetchData 拉取数据
func (p *Plugin) FetchData(input *FetchDataRequest) ([]ChatMsg, error) {
	if len(input.CorpId) == 0 {
		return nil, errors.E(errors.Op(`缺少corp_id参数`))
	}
	if len(input.ChatSecret) == 0 {
		return nil, errors.E(errors.Op(`缺少chat_secret参数`))
	}
	if len(input.ChatPrivateKey) == 0 {
		return nil, errors.E(errors.Op(`缺少chat_private_key参数`))
	}
	if input.ChatPublicKeyVersion <= 0 {
		return nil, errors.E(errors.Op(`参数chat_public_key_version不正确`))
	}
	if input.ChatSeq < 0 {
		return nil, errors.E(errors.Op(`参数chat_seq不正确`))
	}
	if input.Limit <= 0 {
		return nil, errors.E(errors.Op(`参数limit不正确`))
	}

	// 创建SDK对象
	sdk, err := NewSDK()
	if err != nil {
		return nil, errors.E(errors.Op(`创建SDK对象失败`), err)
	}

	// 初始化SDK
	err = sdk.Init(input.CorpId, input.ChatSecret)
	if err != nil {
		return nil, errors.E(errors.Op(`初始化失败`), err)
	}

	// 解析私钥
	block, _ := pem.Decode([]byte(input.ChatPrivateKey))
	if block == nil {
		return nil, errors.E(errors.Op(`解析私钥失败`), err)
	}

	// 私钥是PKCS8格式的，需要转换成PKCS1格式的
	parsedKey, err := x509.ParsePKCS8PrivateKey(block.Bytes)
	if err != nil {
		return nil, errors.E(errors.Op(`解析PKCS8私钥失败`), err)
	}
	privateKey, ok := parsedKey.(*rsa.PrivateKey)
	if !ok {
		return nil, errors.E(errors.Op(`无法转换为RSA私钥`), err)
	}

	// 拉取消息
	res, err := sdk.GetChatData(input.ChatSeq, input.Limit, input.Proxy, input.Passwd, input.Timeout)
	if err != nil {
		return nil, errors.E(errors.Op(`拉取消息失败`), err)
	}
	if res.ErrorCode != 0 {
		return nil, errors.E(
			errors.Op(`拉取消息出错,错误码`),
			errors2.New(fmt.Sprintf("错误码：%d, 错误消息: %s", res.ErrorCode, res.ErrorMsg)),
		)
	}
	p.log.Debug("拉取消息成功",
		zap.Int("length", len(res.ChatDataList)),
		zap.String("corp_id", input.CorpId),
		zap.Uint64("chat_seq", input.ChatSeq),
		zap.Uint32("limit", input.Limit),
	)

	output := make([]ChatMsg, len(res.ChatDataList))

	// 循环获取消息
	for index, encryptedMsg := range res.ChatDataList {
		chatMsg := ChatMsg{
			Seq:           encryptedMsg.Seq,
			MsgID:         encryptedMsg.MsgID,
			EncryptedData: encryptedMsg.EncryptChatMsg,
		}

		// 检查密钥版本号是否匹配
		if encryptedMsg.PublicKeyVer != input.ChatPublicKeyVersion {
			chatMsg.DecryptFailedReason = fmt.Sprintf(
				`期待的版本号是%d,提供的版本号是%d`,
				encryptedMsg.PublicKeyVer,
				input.ChatPublicKeyVersion,
			)
			output[index] = chatMsg
			continue
		}

		// base64 decode
		str1, err := base64.StdEncoding.DecodeString(encryptedMsg.EncryptRandomKey)
		if err != nil {
			chatMsg.DecryptFailedReason = fmt.Sprintf(`对%s decode base64失败 %v`, encryptedMsg.EncryptRandomKey, err)
			output[index] = chatMsg
			continue
		}

		// rsa解密
		str2, err := rsa.DecryptPKCS1v15(nil, privateKey, str1)
		if err != nil {
			chatMsg.DecryptFailedReason = fmt.Sprintf(`使用PSA PKCS1算法对%s解密失败: %v`, str1, err)
			output[index] = chatMsg
			continue
		}

		// 使用解密后的密钥解密消息内容
		decryptedData, err := sdk.DecryptData(string(str2), chatMsg.EncryptedData)
		if err != nil {
			chatMsg.DecryptFailedReason = fmt.Sprintf(`通过企微sdk解密消息失败: %v`, err)
			output[index] = chatMsg
			continue
		}

		chatMsg.DecryptedData = decryptedData
		output[index] = chatMsg
	}

	// 释放SDK资源
	sdk.Close()

	return output, nil
}

func getFileMIMEType(file *os.File) (string, error) {
	// Seek to the beginning of the file
	_, err := file.Seek(0, 0)
	if err != nil {
		return "", err
	}

	// Read the first 512 bytes of the file
	buffer := make([]byte, 512)
	_, err = file.Read(buffer)
	if err != nil {
		return "", err
	}

	// Detect the content type
	contentType := http.DetectContentType(buffer)

	return contentType, nil
}

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

// FetchAndDownloadMediaData 从企微下载文件，然后上传到minio返回url链接
func (p *Plugin) FetchAndDownloadMediaData(input *FetchMediaDataRequest) (*FileInfo, error) {
	const Op = "plugin_wxfinance: FetchMediaData"

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
		return nil, errors.E(Op, `缺少minio_endpoint参数`)
	}
	if len(input.StorageAccessKey) == 0 {
		return nil, errors.E(Op, `缺少minio_access_key参数`)
	}
	if len(input.StorageSecretKey) == 0 {
		return nil, errors.E(Op, `缺少minio_secret_key参数`)
	}
	if len(input.StorageBucketName) == 0 {
		return nil, errors.E(Op, `缺少minio_bucket_name参数`)
	}
	if len(input.StorageObjectKey) == 0 {
		return nil, errors.E(Op, `缺少minio_object_key参数`)
	}

	client := s3.New(s3.Options{
		BaseEndpoint: aws.String(input.StorageEndpoint),
		Region:       input.StorageRegion,
		UsePathStyle: true,
		Credentials:  aws.NewCredentialsCache(credentials.NewStaticCredentialsProvider(input.StorageAccessKey, input.StorageSecretKey, "")),
	})

	// 创建SDK对象
	sdk, err := NewSDK()
	if err != nil {
		return nil, errors.E(Op, err)
	}

	// 初始化SDK
	err = sdk.Init(input.CorpId, input.ChatSecret)
	if err != nil {
		return nil, errors.E(Op, err)
	}

	// 创建临时文件
	prefix := "wxfinance_media_download-"
	tempDir := os.TempDir()
	tempFile, err := os.CreateTemp(tempDir, prefix)
	if err != nil {
		return nil, errors.E(Op, err)
	}

	// 确保临时文件被关闭和删除
	defer func(tempFile *os.File) {
		if err := tempFile.Close(); err != nil {
			p.log.Error(errors.E(Op, err).Error())
		}
		if err := os.Remove(tempFile.Name()); err != nil {
			p.log.Error(errors.E(Op, err).Error())
		}
	}(tempFile)

	// 从企微拉取媒体资源
	indexBuf := ""
	for {
		mediaData, err := sdk.GetMediaData(indexBuf, input.SdkFileId, input.Passwd, input.Passwd, input.Timeout)
		if err != nil {
			return nil, errors.E(Op, err)
		}
		_, err = tempFile.Write(mediaData.Data)
		if err != nil {
			return nil, errors.E(Op, err)
		}
		if mediaData.IsFinish {
			break
		}
		indexBuf = mediaData.OutIndexBuf
	}

	fileInfo := &FileInfo{}
	f, err := tempFile.Stat()
	if err != nil {
		return nil, errors.E(Op, err)
	}

	// 获取文件实际大小
	fileInfo.Size = f.Size()

	// 解析文件的mime
	mime, err := getFileMIMEType(tempFile)
	if err != nil {
		return nil, errors.E(Op, err)
	}
	fileInfo.Mime = mime

	if _, err := tempFile.Seek(0, 0); err != nil {
		return nil, errors.E(Op, err)
	}
	uploader := manager.NewUploader(client)
	fileUploadInfo, err := uploader.Upload(context.TODO(), &s3.PutObjectInput{
		Bucket: aws.String(input.StorageBucketName),
		Key:    aws.String(input.StorageObjectKey),
		Body:   tempFile,
	})
	if err != nil {
		return nil, errors.E(Op, err)
	}
	fileInfo.Hash = extract32BitETag(*fileUploadInfo.ETag)

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
