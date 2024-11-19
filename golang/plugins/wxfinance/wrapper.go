package wxfinance

// 对企业微信提供的接口做进一步的封装，方便使用

import (
	"crypto/rsa"
	"crypto/x509"
	"encoding/base64"
	"encoding/pem"
	errors2 "errors"
	"fmt"
	"github.com/roadrunner-server/errors"
	"go.uber.org/zap"
	"os"
	"runtime"
	"session_archive/golang/plugins/minio"
	"sync"
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

	// 使用协程加速
	maxWorkers := runtime.NumCPU()
	guard := make(chan struct{}, maxWorkers)
	var wg sync.WaitGroup

	// 循环获取消息
	for i, msg := range res.ChatDataList {
		wg.Add(1)
		guard <- struct{}{}
		go func(index int, encryptedMsg ChatDataItem) {
			defer wg.Done()
			defer func() {
				<-guard
			}()

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
				return
			}

			// base64 decode
			str1, err := base64.StdEncoding.DecodeString(encryptedMsg.EncryptRandomKey)
			if err != nil {
				chatMsg.DecryptFailedReason = fmt.Sprintf(`对%s decode base64失败 %v`, encryptedMsg.EncryptRandomKey, err)
				output[index] = chatMsg
				return
			}

			// rsa解密
			str2, err := rsa.DecryptPKCS1v15(nil, privateKey, str1)
			if err != nil {
				chatMsg.DecryptFailedReason = fmt.Sprintf(`使用PSA PKCS1算法对%s解密失败: %v`, str1, err)
				output[index] = chatMsg
				return
			}

			// 使用解密后的密钥解密消息内容
			decryptedData, err := sdk.DecryptData(string(str2), chatMsg.EncryptedData)
			if err != nil {
				chatMsg.DecryptFailedReason = fmt.Sprintf(`通过企微sdk解密消息失败: %v`, err)
				output[index] = chatMsg
				return
			}

			chatMsg.DecryptedData = decryptedData
			output[index] = chatMsg
			return
		}(i, msg)
	}
	wg.Wait()

	// 释放SDK资源
	sdk.Close()

	return output, nil
}

type FetchMediaDataRequest struct {
	CorpId         string `json:"corp_id"`
	ChatSecret     string `json:"chat_secret"`
	SdkFileId      string `json:"sdk_file_id"`
	MD5            string `json:"md5"`
	OriginFileName string `json:"origin_file_name"`
	Proxy          string `json:"proxy"`
	Passwd         string `json:"passwd"`
	Timeout        int    `json:"timeout"`
}

// FetchMediaData 从企微下载文件，然后上传到minio返回url链接
func (p *Plugin) FetchMediaData(input *FetchMediaDataRequest) (*minio.UploadFileResponse, error) {
	if len(input.CorpId) == 0 {
		return nil, errors.E(errors.Op(`缺少corp_id参数`))
	}
	if len(input.ChatSecret) == 0 {
		return nil, errors.E(errors.Op(`缺少chat_secret参数`))
	}
	if len(input.SdkFileId) == 0 {
		return nil, errors.E(errors.Op(`缺少sdk_field_id参数`))
	}
	if len(input.MD5) == 0 {
		return nil, errors.E(errors.Op(`缺少md5参数`))
	}
	if len(input.OriginFileName) == 0 {
		return nil, errors.E(errors.Op(`缺少origin_file_name参数`))
	}

	// 先根据md5值从文件服务器查看文件是否存在，如果存在就直接返回不用下载了
	url, err := p.minioPlugin.GetFileByMD5(minio.GetFileByMD5Request{MD5: input.MD5})
	if err != nil {
		return nil, errors.E(errors.Op(`从文件服务器查询文件是否存在失败`), err)
	}
	if len(url) > 0 {
		p.log.Info(`根据md5从文件服务器找到了文件`, zap.String("url", url))
		return &minio.UploadFileResponse{Url: url, MD5: input.MD5}, nil
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

	// 创建临时文件
	prefix := "wxfinance_media_download-"
	tempDir := os.TempDir()
	tempFile, err := os.CreateTemp(tempDir, prefix)
	if err != nil {
		return nil, errors.E(errors.Op(fmt.Sprintf("创建临时文件%s失败", tempFile.Name())), err)

	}

	// 确保临时文件被关闭和删除
	defer func(tempFile *os.File) {
		if err := tempFile.Close(); err != nil {
			p.log.Error(fmt.Sprintf(`关闭临时文件%s失败: %v`, tempFile.Name(), err))
		}
		if err := os.Remove(tempFile.Name()); err != nil {
			p.log.Error(fmt.Sprintf(`删除临时文件%s失败: %v`, tempFile.Name(), err))
		}
	}(tempFile)

	// 从企微拉取媒体资源
	indexBuf := ""
	for {
		mediaData, err := sdk.GetMediaData(indexBuf, input.SdkFileId, input.Passwd, input.Passwd, input.Timeout)
		if err != nil {
			return nil, errors.E(errors.Op(`文件下载失败`), err)
		}
		_, err = tempFile.Write(mediaData.Data)
		if err != nil {
			return nil, errors.E(errors.Op(`媒体数据写入失败`), err)
		}
		if mediaData.IsFinish {
			break
		}
		indexBuf = mediaData.OutIndexBuf
	}

	// 把资源上传到文件服务器
	updateMetaData := minio.UploadFileRequest{
		FilePath: tempFile.Name(),
	}
	if len(input.OriginFileName) > 0 {
		updateMetaData.OriginFileName = input.OriginFileName
	}
	result, err := p.minioPlugin.UploadFile(updateMetaData)
	if err != nil {
		return nil, errors.E(errors.Op(fmt.Sprintf(`上传文件%s到服务器失败`, tempFile.Name())), err)

	}

	return &result, nil
}
