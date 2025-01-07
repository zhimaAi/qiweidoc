package main

import (
	"io"
	"sync"
)

type StreamingReader struct {
	sdk       *SDK
	indexBuf  string
	sdkFileID string
	proxy     string
	passwd    string
	timeout   int
	buffer    []byte
	offset    int
	finished  bool
	mu        sync.Mutex
}

func NewStreamingReader(sdk *SDK, sdkFileID, proxy, passwd string, timeout int) *StreamingReader {
	return &StreamingReader{
		sdk:       sdk,
		sdkFileID: sdkFileID,
		proxy:     proxy,
		passwd:    passwd,
		timeout:   timeout,
	}
}
func (sr *StreamingReader) Read(p []byte) (n int, err error) {
	sr.mu.Lock()
	defer sr.mu.Unlock()

	if sr.finished && sr.offset >= len(sr.buffer) {
		return 0, io.EOF
	}

	if sr.offset >= len(sr.buffer) {
		mediaData, err := sr.sdk.GetMediaData(sr.indexBuf, sr.sdkFileID, sr.proxy, sr.passwd, sr.timeout)
		if err != nil {
			return 0, err
		}
		sr.buffer = mediaData.Data
		sr.offset = 0
		sr.indexBuf = mediaData.OutIndexBuf
		sr.finished = mediaData.IsFinish
	}

	n = copy(p, sr.buffer[sr.offset:])
	sr.offset += n
	return n, nil
}
