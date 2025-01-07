package main

/*
#cgo LDFLAGS: -L${SRCDIR}/linux -lWeWorkFinanceSdk_C
#cgo CFLAGS: -I${SRCDIR}/linux
#include <stdlib.h>
#include "WeWorkFinanceSdk_C.h"
*/
import "C"
import (
	"encoding/json"
	"fmt"
	"github.com/roadrunner-server/errors"
	"unsafe"
)

// ChatDataItem 会话数据结构
type ChatDataItem struct {
	Seq              int64  `json:"seq"`
	MsgID            string `json:"msgid"`
	PublicKeyVer     int    `json:"publickey_ver"`
	EncryptRandomKey string `json:"encrypt_random_key"`
	EncryptChatMsg   string `json:"encrypt_chat_msg"`
}

type ChatData struct {
	ErrorCode    int            `json:"errcode"`
	ErrorMsg     string         `json:"errmsg"`
	ChatDataList []ChatDataItem `json:"chatdata"`
}

// SDK 封装企业微信会话存档SDK
type SDK struct {
	sdk *C.WeWorkFinanceSdk_t
}

// NewSDK 创建SDK实例
func NewSDK() (*SDK, error) {
	sdk := C.NewSdk()
	if sdk == nil {
		return nil, fmt.Errorf(`初始化SDK失败`)
	}
	return &SDK{sdk: sdk}, nil
}

// Init 初始化SDK
func (s *SDK) Init(corpID, secret string) error {
	cCorpID := C.CString(corpID)
	defer C.free(unsafe.Pointer(cCorpID))

	cSecret := C.CString(secret)
	defer C.free(unsafe.Pointer(cSecret))

	result := C.Init(s.sdk, cCorpID, cSecret)
	if result != 0 {
		return fmt.Errorf("初始化SDK失败，错误码：%d", result)
	}
	return nil
}

// GetChatData 拉取会话存档
func (s *SDK) GetChatData(seq uint64, limit uint32, proxy, passwd string, timeout int) (*ChatData, error) {
	var cProxy, cPasswd *C.char
	if proxy != "" {
		cProxy = C.CString(proxy)
		defer C.free(unsafe.Pointer(cProxy))
	}
	if passwd != "" {
		cPasswd = C.CString(passwd)
		defer C.free(unsafe.Pointer(cPasswd))
	}

	slice := C.NewSlice()
	defer C.FreeSlice(slice)

	result := C.GetChatData(s.sdk, C.ulonglong(seq), C.uint(limit), cProxy, cPasswd, C.int(timeout), slice)
	if result != 0 {
		return nil, fmt.Errorf("获取会话数据失败，错误码：%d", result)
	}

	content := C.GetContentFromSlice(slice)
	if content == nil {
		return nil, fmt.Errorf("获取会话内容失败")
	}

	chatData := &ChatData{}
	err := json.Unmarshal([]byte(C.GoString(content)), chatData)
	if err != nil {
		return nil, fmt.Errorf("解析会话数据失败：%v", err)
	}

	return chatData, nil
}

// DecryptData 解密会话数据
func (s *SDK) DecryptData(encryptKey, encryptMsg string) (string, error) {
	cKey := C.CString(encryptKey)
	defer C.free(unsafe.Pointer(cKey))

	cMsg := C.CString(encryptMsg)
	defer C.free(unsafe.Pointer(cMsg))

	slice := C.NewSlice()
	defer C.FreeSlice(slice)

	result := C.DecryptData(cKey, cMsg, slice)
	if result != 0 {
		return "", fmt.Errorf("解密数据失败，错误码：%d", result)
	}

	content := C.GetContentFromSlice(slice)
	if content == nil {
		return "", fmt.Errorf("获取解密内容失败")
	}

	return C.GoString(content), nil
}

type MediaData struct {
	OutIndexBuf string `json:"outindexbuf,omitempty"`
	IsFinish    bool   `json:"is_finish,omitempty"`
	Data        []byte `json:"data,omitempty"`
}

// GetMediaData 获取媒体文件数据
func (s *SDK) GetMediaData(indexBuf, sdkFileID, proxy, passwd string, timeout int) (*MediaData, error) {
	var cIndexBuf, cProxy, cPasswd *C.char
	if indexBuf != "" {
		cIndexBuf = C.CString(indexBuf)
		defer C.free(unsafe.Pointer(cIndexBuf))
	}

	cSdkFileID := C.CString(sdkFileID)
	defer C.free(unsafe.Pointer(cSdkFileID))

	if proxy != "" {
		cProxy = C.CString(proxy)
		defer C.free(unsafe.Pointer(cProxy))
	}
	if passwd != "" {
		cPasswd = C.CString(passwd)
		defer C.free(unsafe.Pointer(cPasswd))
	}

	mediaData := C.NewMediaData()
	defer C.FreeMediaData(mediaData)

	result := C.GetMediaData(s.sdk, cIndexBuf, cSdkFileID, cProxy, cPasswd, C.int(timeout), mediaData)
	if result != 0 {
		return nil, errors.E(errors.Op(fmt.Sprintf("获取媒体数据失败，错误码：%d", result)))
	}

	return &MediaData{
		IsFinish:    C.IsMediaDataFinish(mediaData) == 1,
		OutIndexBuf: C.GoString(C.GetOutIndexBuf(mediaData)),
		Data:        C.GoBytes(unsafe.Pointer(C.GetData(mediaData)), C.GetDataLen(mediaData)),
	}, nil
}

// Close 释放SDK资源
func (s *SDK) Close() {
	if s.sdk != nil {
		C.DestroySdk(s.sdk)
		s.sdk = nil
	}
}
