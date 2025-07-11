<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# NO CHECKED-IN PROTOBUF GENCODE
# source: temporal/api/workflow/v1/message.proto

namespace Temporal\Api\Workflow\V1;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Holds information about ongoing transition of a workflow execution from one worker
 * deployment version to another.
 * Experimental. Might change in the future.
 *
 * Generated from protobuf message <code>temporal.api.workflow.v1.DeploymentVersionTransition</code>
 */
class DeploymentVersionTransition extends \Google\Protobuf\Internal\Message
{
    /**
     * Required. The target Version of the transition. May be `__unversioned__` which means a
     * so-far-versioned workflow is transitioning to unversioned workers.
     *
     * Generated from protobuf field <code>string version = 1;</code>
     */
    protected $version = '';

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $version
     *           Required. The target Version of the transition. May be `__unversioned__` which means a
     *           so-far-versioned workflow is transitioning to unversioned workers.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Temporal\Api\Workflow\V1\Message::initOnce();
        parent::__construct($data);
    }

    /**
     * Required. The target Version of the transition. May be `__unversioned__` which means a
     * so-far-versioned workflow is transitioning to unversioned workers.
     *
     * Generated from protobuf field <code>string version = 1;</code>
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Required. The target Version of the transition. May be `__unversioned__` which means a
     * so-far-versioned workflow is transitioning to unversioned workers.
     *
     * Generated from protobuf field <code>string version = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setVersion($var)
    {
        GPBUtil::checkString($var, True);
        $this->version = $var;

        return $this;
    }

}

