package main

import (
	"bytes"
	"context"
	"encoding/json"
	"fmt"
	"github.com/google/uuid"
	"io"
	"net/http"
	"net/url"
	"sync"
	"time"
)

var httpClient *http.Client

// HTTPRequest 请求参数结构
type HTTPRequest struct {
	URL         string            `json:"url"`
	Method      string            `json:"method"`
	Headers     map[string]string `json:"headers"`
	Params      map[string]string `json:"params"`
	Body        interface{}       `json:"body"`
	ContentType string            `json:"contentType"`
}

// HTTPResponse 响应结构
type HTTPResponse struct {
	RequestID  string            `json:"request_id"`
	URL        string            `json:"url"`
	StatusCode int               `json:"status_code"`
	Headers    map[string]string `json:"headers"`
	Body       string            `json:"body"`
	Error      string            `json:"error,omitempty"`
}

// BatchHTTPInput RPC 输入参数
type BatchHTTPInput struct {
	Requests    []HTTPRequest `json:"requests"`
	Concurrency int           `json:"concurrency"` // 同时并发数
	QPS         int           `json:"qps"`         // QPS限制
	Timeout     int           `json:"timeout"`     // 总体超时时间（秒）
}

// BatchHTTPOutput RPC 输出参数
type BatchHTTPOutput struct {
	Responses []HTTPResponse `json:"responses"`
	BathId    string         `json:"batch_id"`
}

// Request 批量处理 HTTP 请求的 RPC 方法
func Request(input *BatchHTTPInput) (*BatchHTTPOutput, error) {
	if input == nil || len(input.Requests) == 0 {
		return nil, fmt.Errorf("empty request list")
	}

	// 生成批量id
	batchID := uuid.New().String()

	// 初始化 HTTP 客户端（如果未初始化）
	if httpClient == nil {
		httpClient = &http.Client{
			Timeout: 30 * time.Second,
		}
	}

	// 设置默认并发数和QPS
	concurrency := input.Concurrency
	if concurrency <= 0 {
		concurrency = 10
	}
	qps := input.QPS
	if qps <= 0 {
		qps = concurrency * 2 // 默认QPS为并发数的两倍
	}
	timeout := input.Timeout
	if timeout <= 0 {
		timeout = 60 // 默认60秒
	}

	// 创建用于并发控制的通道
	rateLimiter := make(chan struct{}, concurrency)

	// 创建QPS控制器
	qpsLimiter := time.NewTicker(time.Second / time.Duration(qps))
	defer qpsLimiter.Stop()

	// 创建上下文，用于总体超时控制
	ctx, cancel := context.WithTimeout(context.Background(), time.Duration(timeout)*time.Second)
	defer cancel()

	// 初始化响应切片
	responses := make([]HTTPResponse, len(input.Requests))
	var wg sync.WaitGroup

	// 处理每个请求
	for i, req := range input.Requests {
		wg.Add(1)
		go func(index int, request HTTPRequest) {
			defer wg.Done()

			// 生成请求ID
			requestID := fmt.Sprintf("%s-%d", batchID, index)

			// QPS控制
			select {
			case <-qpsLimiter.C:
				// 获取并发令牌
				select {
				case rateLimiter <- struct{}{}:
					defer func() { <-rateLimiter }()
				case <-ctx.Done():
					responses[index] = HTTPResponse{
						RequestID: requestID,
						URL:       request.URL,
						Error:     "context cancelled",
					}
					return
				}
			case <-ctx.Done():
				responses[index] = HTTPResponse{
					RequestID: requestID,
					URL:       request.URL,
					Error:     "context cancelled due to QPS limit",
				}
				return
			}

			responses[index] = doRequest(ctx, request, requestID)
		}(i, req)
	}

	// 等待所有协程结束
	wg.Wait()

	return &BatchHTTPOutput{BathId: batchID, Responses: responses}, nil
}

// doRequest 执行单个 HTTP 请求
func doRequest(ctx context.Context, req HTTPRequest, requestID string) HTTPResponse {
	response := HTTPResponse{
		RequestID: requestID,
		URL:       req.URL,
	}

	// 设置默认请求方法
	if req.Method == "" {
		req.Method = "GET"
	}

	// 处理 URL 和查询参数
	parsedURL, err := url.Parse(req.URL)
	if err != nil {
		response.Error = fmt.Sprintf("parse url error: %v", err)
		return response
	}

	// 处理查询参数
	if len(req.Params) > 0 {
		q := parsedURL.Query()
		for key, value := range req.Params {
			q.Set(key, value)
		}
		parsedURL.RawQuery = q.Encode()
	}

	// 处理请求体
	var bodyReader io.Reader
	if req.Body != nil {
		var bodyBytes []byte

		switch v := req.Body.(type) {
		case string:
			bodyBytes = []byte(v)
		case []byte:
			bodyBytes = v
		default:
			// 如果是其他类型，尝试 JSON 编码
			var err error
			bodyBytes, err = json.Marshal(req.Body)
			if err != nil {
				response.Error = fmt.Sprintf("marshal request body error: %v", err)
				return response
			}
			// 如果没有指定 Content-Type，默认设置为 JSON
			if req.ContentType == "" {
				req.ContentType = "application/json"
			}
		}
		bodyReader = bytes.NewReader(bodyBytes)
	}

	// 创建请求
	httpReq, err := http.NewRequestWithContext(ctx, req.Method, parsedURL.String(), bodyReader)
	if err != nil {
		response.Error = fmt.Sprintf("create request error: %v", err)
		return response
	}

	// 设置请求头
	for key, value := range req.Headers {
		httpReq.Header.Set(key, value)
	}

	// 发送请求
	resp, err := httpClient.Do(httpReq)
	if err != nil {
		response.Error = fmt.Sprintf("request error: %v", err)
		return response
	}
	defer func(Body io.ReadCloser) {
		_ = Body.Close()
	}(resp.Body)

	// 读取响应体
	body, err := io.ReadAll(resp.Body)
	if err != nil {
		response.Error = fmt.Sprintf("read response error: %v", err)
		return response
	}

	// 设置响应信息
	response.StatusCode = resp.StatusCode
	response.Body = string(body)
	response.Headers = make(map[string]string)
	for key, values := range resp.Header {
		response.Headers[key] = values[0]
	}

	return response
}
