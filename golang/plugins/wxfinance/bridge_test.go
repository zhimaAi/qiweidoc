package wxfinance

import (
	"crypto/rsa"
	"crypto/x509"
	"encoding/base64"
	"encoding/pem"
	"testing"
)

func getSDK() (*SDK, error) {
	corpID := `ww5f432b3a24a9b9f1`
	secret := `BMmvW1Pz79erXEk07fctTWO13BYMLoGBtcMacZshm0A`

	sdk, err := NewSDK()
	if err != nil {
		return nil, err
	}

	err = sdk.Init(corpID, secret)
	if err != nil {
		return nil, err
	}

	return sdk, nil
}

func TestInit(t *testing.T) {
	_, err := getSDK()
	if err != nil {
		panic(err)
	}
}
func TestGetChatData(t *testing.T) {
	sdk, err := getSDK()
	if err != nil {
		t.Fatal(err) // 使用 t.Fatal 代替 panic，这样更符合测试规范
	}

	privateKeyPEM := `-----BEGIN PRIVATE KEY-----
MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCsNCUE9/q5KyW/
qqbJnA5x90g90C2fgDz1dZm0n/cR4eEWLlJI/7mGvXDY2PRh8ffAC8iIpCP91Jtv
URxnwwTY2dBuoV1yBubpMzmONMmHOsZ5LripPEpAtdDc5eH7Rbhwhru7eQnzr7Yp
Q6BeJWDmYR4QoiBEEr7TRIJaG7pUfaVLJ8zr0GSSp8LlvsZqGCstDaTw1fwujhW0
AS0rrAqgBMCSwUUXmh5W6nEVnc9mmxvH4PFWVEROjSbe1KJNRgKbWpxvME5D2g5L
j4p3WAa5qsVBX0+0YK5f1wuFAWZRPXC5d4l838d3W/6GCjVRvk1FxP9ibIGrKlWr
cOMgTb+jAgMBAAECggEAP/v0EV+OsoS8tvXSmTC6EVB/cDBM82nzvISgwwN5CxTx
zIsTmfoi1lTHfFtImqgMlTgLn/HVSbhYtMjflBJbV7O/BYxBq1+zJs0YwqUMiImn
O1nH1WIIePjVjW6BRBsIyA7X7HWx4/DS74woJzMeSRumzylecczBSMC46oBJAKE+
UMfY2ZMf/mHqJmjLP5nC4LKp5FI/MeFOL1fqk3GB2crr+FYpFG/F0XvikmZPhIrs
pJO0J9HsQqbTIuE/VIsOlUx+X9CAX3CgysDTzVqG6yJr1uWDlNI48KbWjM36mUcH
UBH1rTLT3PHhw8YTwhMI/wfSqH2yHRRYOcfm11j+SQKBgQDk2EwTmB/kDpl38XqD
1WqOzTh0/D32OKfBLIi4Cd1kfdnxUytV/sIajrJCjFg8nykpXwPW9+ghh6zzcsEp
AEyst0L46RyCUx3zEKs+dfGKoI8coss/O62UEnVaN5on+0QHpDtmRa86IkjnDtrn
Bq7PkzNwIYFv4P+j1f1ZyQxi1wKBgQDAozytjEqQFIPHj3LIiD3E+GwKnR1KQvmk
SKpSNwLKOJ1cXEuKSv3xVElBEUdRwhxDw4iv5idhatLkzJdTGVSYx+q7JJ0Hv7vs
2oNs0HrzVtC2XfOnO9JGqwhyQpnZMuroLtzPIbIqSDLthcylpLeB9w/oNehRu0v0
Tj5NlTL8FQKBgFgQdJZFwF/+zkg+ASuigTMa/lLmjuGlw8iu3p4UDcKSO8CGhPK+
6utVZnv9jicmCK0HB28+T3I3x/KXgoXIu7jrfTZUXsg4PBpBm+SZdq4mQ2x9qUFY
Md/9inOBWi1woClgpgshXeE9OFjSeqLVC4iN2QmZmzn2l+nwe2KF3+JZAoGAFRoM
jIpApLXKlbKuBS9/bLM1ypfZXIgnpopbNfRWtuYqvrJRiA2c8bGk8J6+7ELSK84g
i/wvNimofm226eBtdur3WJazKOUk5dYt7V6NCVjqNf/mr9wKtbkIjNOk3LkKKHLx
tzLevGj6QzCT8VUw0OjKckptb36P3GwxwPfmBN0CgYEAs0Tl8PkejdtClWwd/s6e
VT+t1bkyhjdVb3bYJLD8C1B0Ap2GFsjSAGGXC00RQDr2Srh0WduVHf0L78NdXKhn
f9F1ieAB2RU4+82awYhpE7Lb72FxZdLWzDAWLuEmrSGXzv+AZDgU4dL6GQVD9AQJ
TyLXLylZLhzONExRiTF4J8c=
-----END PRIVATE KEY-----`

	// 解析私钥
	block, _ := pem.Decode([]byte(privateKeyPEM))
	if block == nil {
		t.Fatal(`解析私钥失败`)
	}

	// 使用 ParsePKCS1PrivateKey
	//privateKey, err := x509.ParsePKCS1PrivateKey(block.Bytes)
	//if err != nil {
	//	t.Fatal("解析PKCS1私钥失败:", err)
	//}

	//转换私钥
	parsedKey, err := x509.ParsePKCS8PrivateKey(block.Bytes)
	if err != nil {
		t.Fatal("解析PKCS8私钥失败:", err)
	}
	privateKey, ok := parsedKey.(*rsa.PrivateKey)
	if !ok {
		t.Fatal("无法转换为RSA私钥")
	}

	// 打印私钥信息，确认密钥大小
	t.Logf("私钥大小: %d bits", privateKey.Size()*8)

	// 获取消息
	seq := 0
	limit := 1000
	res, err := sdk.GetChatData(uint64(seq), uint32(limit), "", "", 0)
	if err != nil {
		t.Fatal(err)
	}
	if res.ErrorCode != 0 {
		t.Fatalf("获取消息失败：%s", res.ErrorMsg)
	}
	if len(res.ChatDataList) <= 0 {
		t.Log(`没有消息数据`)
		return
	}

	t.Logf("获取到 %d 条消息", len(res.ChatDataList))

	//循环获取消息
	for i, msg := range res.ChatDataList {
		t.Logf("处理第 %d 条消息", i+1)
		t.Logf("PublicKeyVer: %d", msg.PublicKeyVer)
		if msg.PublicKeyVer != 10 {
			t.Logf(`版本号不是10，过滤掉`)
			continue
		}

		// base64 decode
		str1, err := base64.StdEncoding.DecodeString(msg.EncryptRandomKey)
		if err != nil {
			t.Fatal("Base64解码失败:", err)
		}
		t.Logf("解码后数据长度: %d bytes", len(str1))

		// rsa解密
		decryptedKey, err := rsa.DecryptPKCS1v15(nil, privateKey, str1)
		if err != nil {
			t.Fatalf("RSA解密失败: %v\n密钥版本: %d", err, msg.PublicKeyVer)
		}

		// 使用解密后的密钥解密消息内容
		decryptedData, err := sdk.DecryptData(string(decryptedKey), msg.EncryptChatMsg)
		if err != nil {
			t.Fatal("消息解密失败:", err)
		}
		t.Log("解密后的消息:", decryptedData)
	}
}

func TestGetMediaData(t *testing.T) {

}
