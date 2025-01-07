package common

import (
	"bytes"
	"fmt"
	"github.com/roadrunner-server/errors"
	"go.uber.org/zap"
	"io"
	"net/http"
	"time"
)

type rpc struct {
	pl  *Plugin
	log *zap.Logger
}

func (r *rpc) Hello(input string, output *string) error {
	*output = "hello, " + input
	r.log.Info(`调用了Hello方法`)
	return nil
}

type CronCollectModuleRequest struct {
	Url     string `json:"url"`
	Name    string `json:"name"`
	Version string `json:"version"`
	CorpId  string `json:"corp_id"`
}

func (r *rpc) sendPostRequest(input CronCollectModuleRequest) error {
	// 准备POST请求的数据
	data := fmt.Sprintf(`{
        "name": "%s",
        "version": "%s",
        "corp_id": "%s"
    }`, input.Name, input.Version, input.CorpId)
	postData := []byte(data)

	// 创建请求
	req, err := http.NewRequest("POST", input.Url, bytes.NewBuffer(postData))
	if err != nil {
		return err
	}

	// 设置请求头
	req.Header.Set("Content-Type", "application/json")

	// 发送请求
	client := &http.Client{}
	resp, err := client.Do(req)
	if err != nil {
		return err
	}
	defer resp.Body.Close()

	// 检查响应状态
	if resp.StatusCode != http.StatusOK {
		return fmt.Errorf("unexpected status code: %d", resp.StatusCode)
	}

	body, err := io.ReadAll(resp.Body)
	if err != nil {
		return fmt.Errorf("read body error: %v", err)
	}
	r.log.Debug(fmt.Sprintf("collect module info return %s", string(body)))

	return nil
}

func (r *rpc) CronCollectModule(input CronCollectModuleRequest, output *string) error {
	const op = errors.Op("plugin_common:CronCollectModule")
	if len(input.CorpId) == 0 || len(input.Url) == 0 || len(input.Name) == 0 || len(input.Version) == 0 {
		return nil
	}

	r.pl.cronCollectModuleMu.Lock()
	defer r.pl.cronCollectModuleMu.Unlock()

	if r.pl.cronCollectModuleTicker != nil {
		r.pl.cronCollectModuleTicker.Stop()
	}
	r.pl.cronCollectModuleTicker = time.NewTicker(5 * time.Second)

	go func() {
		defer r.pl.cronCollectModuleTicker.Stop()
		for {
			select {
			case <-r.pl.cronCollectModuleDone:
				return
			case <-r.pl.cronCollectModuleTicker.C:
				err := r.sendPostRequest(input)
				if err != nil {
					r.log.Error(errors.E(op, err).Error())
				}
			}
		}
	}()

	return nil
}
