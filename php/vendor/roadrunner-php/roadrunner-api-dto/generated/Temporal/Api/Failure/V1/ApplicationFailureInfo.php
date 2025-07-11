<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# NO CHECKED-IN PROTOBUF GENCODE
# source: temporal/api/failure/v1/message.proto

namespace Temporal\Api\Failure\V1;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>temporal.api.failure.v1.ApplicationFailureInfo</code>
 */
class ApplicationFailureInfo extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>string type = 1;</code>
     */
    protected $type = '';
    /**
     * Generated from protobuf field <code>bool non_retryable = 2;</code>
     */
    protected $non_retryable = false;
    /**
     * Generated from protobuf field <code>.temporal.api.common.v1.Payloads details = 3;</code>
     */
    protected $details = null;
    /**
     * next_retry_delay can be used by the client to override the activity
     * retry interval calculated by the retry policy. Retry attempts will
     * still be subject to the maximum retries limit and total time limit
     * defined by the policy.
     *
     * Generated from protobuf field <code>.google.protobuf.Duration next_retry_delay = 4;</code>
     */
    protected $next_retry_delay = null;
    /**
     * Generated from protobuf field <code>.temporal.api.enums.v1.ApplicationErrorCategory category = 5;</code>
     */
    protected $category = 0;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $type
     *     @type bool $non_retryable
     *     @type \Temporal\Api\Common\V1\Payloads $details
     *     @type \Google\Protobuf\Duration $next_retry_delay
     *           next_retry_delay can be used by the client to override the activity
     *           retry interval calculated by the retry policy. Retry attempts will
     *           still be subject to the maximum retries limit and total time limit
     *           defined by the policy.
     *     @type int $category
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Temporal\Api\Failure\V1\Message::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>string type = 1;</code>
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Generated from protobuf field <code>string type = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setType($var)
    {
        GPBUtil::checkString($var, True);
        $this->type = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>bool non_retryable = 2;</code>
     * @return bool
     */
    public function getNonRetryable()
    {
        return $this->non_retryable;
    }

    /**
     * Generated from protobuf field <code>bool non_retryable = 2;</code>
     * @param bool $var
     * @return $this
     */
    public function setNonRetryable($var)
    {
        GPBUtil::checkBool($var);
        $this->non_retryable = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>.temporal.api.common.v1.Payloads details = 3;</code>
     * @return \Temporal\Api\Common\V1\Payloads|null
     */
    public function getDetails()
    {
        return $this->details;
    }

    public function hasDetails()
    {
        return isset($this->details);
    }

    public function clearDetails()
    {
        unset($this->details);
    }

    /**
     * Generated from protobuf field <code>.temporal.api.common.v1.Payloads details = 3;</code>
     * @param \Temporal\Api\Common\V1\Payloads $var
     * @return $this
     */
    public function setDetails($var)
    {
        GPBUtil::checkMessage($var, \Temporal\Api\Common\V1\Payloads::class);
        $this->details = $var;

        return $this;
    }

    /**
     * next_retry_delay can be used by the client to override the activity
     * retry interval calculated by the retry policy. Retry attempts will
     * still be subject to the maximum retries limit and total time limit
     * defined by the policy.
     *
     * Generated from protobuf field <code>.google.protobuf.Duration next_retry_delay = 4;</code>
     * @return \Google\Protobuf\Duration|null
     */
    public function getNextRetryDelay()
    {
        return $this->next_retry_delay;
    }

    public function hasNextRetryDelay()
    {
        return isset($this->next_retry_delay);
    }

    public function clearNextRetryDelay()
    {
        unset($this->next_retry_delay);
    }

    /**
     * next_retry_delay can be used by the client to override the activity
     * retry interval calculated by the retry policy. Retry attempts will
     * still be subject to the maximum retries limit and total time limit
     * defined by the policy.
     *
     * Generated from protobuf field <code>.google.protobuf.Duration next_retry_delay = 4;</code>
     * @param \Google\Protobuf\Duration $var
     * @return $this
     */
    public function setNextRetryDelay($var)
    {
        GPBUtil::checkMessage($var, \Google\Protobuf\Duration::class);
        $this->next_retry_delay = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>.temporal.api.enums.v1.ApplicationErrorCategory category = 5;</code>
     * @return int
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Generated from protobuf field <code>.temporal.api.enums.v1.ApplicationErrorCategory category = 5;</code>
     * @param int $var
     * @return $this
     */
    public function setCategory($var)
    {
        GPBUtil::checkEnum($var, \Temporal\Api\Enums\V1\ApplicationErrorCategory::class);
        $this->category = $var;

        return $this;
    }

}

