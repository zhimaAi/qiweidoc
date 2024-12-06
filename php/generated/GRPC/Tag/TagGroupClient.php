<?php
// GENERATED CODE -- DO NOT EDIT!

namespace GRPC\Tag;

/**
 */
class TagGroupClient extends \Grpc\BaseStub {

    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * @param \GRPC\Tag\SyncRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function Sync(\GRPC\Tag\SyncRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/customer.TagGroup/Sync',
        $argument,
        ['\GRPC\Tag\CommonResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \GRPC\Tag\UpdateTagGroupRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function UpdateOrCreate(\GRPC\Tag\UpdateTagGroupRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/customer.TagGroup/UpdateOrCreate',
        $argument,
        ['\GRPC\Tag\CommonResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \GRPC\Tag\DeleteTagGroupRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function Delete(\GRPC\Tag\DeleteTagGroupRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/customer.TagGroup/Delete',
        $argument,
        ['\GRPC\Tag\CommonResponse', 'decode'],
        $metadata, $options);
    }

}
