<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# NO CHECKED-IN PROTOBUF GENCODE
# source: temporal/api/common/v1/message.proto

namespace Temporal\Api\Common\V1\Link\WorkflowEvent;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * RequestIdReference is a indirect reference to a history event through the request ID.
 *
 * Generated from protobuf message <code>temporal.api.common.v1.Link.WorkflowEvent.RequestIdReference</code>
 */
class RequestIdReference extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>string request_id = 1;</code>
     */
    protected $request_id = '';
    /**
     * Generated from protobuf field <code>.temporal.api.enums.v1.EventType event_type = 2;</code>
     */
    protected $event_type = 0;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $request_id
     *     @type int $event_type
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Temporal\Api\Common\V1\Message::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>string request_id = 1;</code>
     * @return string
     */
    public function getRequestId()
    {
        return $this->request_id;
    }

    /**
     * Generated from protobuf field <code>string request_id = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setRequestId($var)
    {
        GPBUtil::checkString($var, True);
        $this->request_id = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>.temporal.api.enums.v1.EventType event_type = 2;</code>
     * @return int
     */
    public function getEventType()
    {
        return $this->event_type;
    }

    /**
     * Generated from protobuf field <code>.temporal.api.enums.v1.EventType event_type = 2;</code>
     * @param int $var
     * @return $this
     */
    public function setEventType($var)
    {
        GPBUtil::checkEnum($var, \Temporal\Api\Enums\V1\EventType::class);
        $this->event_type = $var;

        return $this;
    }

}

