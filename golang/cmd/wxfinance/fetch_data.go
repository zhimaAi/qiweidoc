package main

import (
	"crypto/rsa"
	"crypto/x509"
	"encoding/base64"
	"encoding/pem"
	"fmt"
	"github.com/roadrunner-server/errors"
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

type ChatMsg struct {
	Seq                 int64  `json:"seq"`
	MsgID               string `json:"msgid"`
	EncryptedData       string `json:"encrypted_data"`
	DecryptFailedReason string `json:"decrypt_failed_reason"`
	DecryptedData       string `json:"decrypted_data"`
}

// FetchData 拉取数据
func FetchData(input *FetchDataRequest) ([]ChatMsg, error) {
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
			errors.Op(`拉取消息出错`),
			errors.E(fmt.Sprintf("错误码：%d, 错误消息: %s", res.ErrorCode, res.ErrorMsg)),
		)
	}

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
