<?php
// GENERATED CODE -- DO NOT EDIT!

namespace GRPC\Pinger;

/**
 */
class PingerClient extends \Grpc\BaseStub {

    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * @param \GRPC\Pinger\PingRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ping(\GRPC\Pinger\PingRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/pinger.Pinger/ping',
        $argument,
        ['\GRPC\Pinger\PingResponse', 'decode'],
        $metadata, $options);
    }

}
