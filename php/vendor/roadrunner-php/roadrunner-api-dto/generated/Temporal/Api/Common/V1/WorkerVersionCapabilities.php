<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# NO CHECKED-IN PROTOBUF GENCODE
# source: temporal/api/common/v1/message.proto

namespace Temporal\Api\Common\V1;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Identifies the version that a worker is compatible with when polling or identifying itself,
 * and whether or not this worker is opting into the build-id based versioning feature. This is
 * used by matching to determine which workers ought to receive what tasks.
 * Deprecated. Use WorkerDeploymentOptions instead.
 *
 * Generated from protobuf message <code>temporal.api.common.v1.WorkerVersionCapabilities</code>
 */
class WorkerVersionCapabilities extends \Google\Protobuf\Internal\Message
{
    /**
     * An opaque whole-worker identifier
     *
     * Generated from protobuf field <code>string build_id = 1;</code>
     */
    protected $build_id = '';
    /**
     * If set, the worker is opting in to worker versioning, and wishes to only receive appropriate
     * tasks.
     *
     * Generated from protobuf field <code>bool use_versioning = 2;</code>
     */
    protected $use_versioning = false;
    /**
     * Must be sent if user has set a deployment series name (versioning-3).
     *
     * Generated from protobuf field <code>string deployment_series_name = 4;</code>
     */
    protected $deployment_series_name = '';

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $build_id
     *           An opaque whole-worker identifier
     *     @type bool $use_versioning
     *           If set, the worker is opting in to worker versioning, and wishes to only receive appropriate
     *           tasks.
     *     @type string $deployment_series_name
     *           Must be sent if user has set a deployment series name (versioning-3).
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Temporal\Api\Common\V1\Message::initOnce();
        parent::__construct($data);
    }

    /**
     * An opaque whole-worker identifier
     *
     * Generated from protobuf field <code>string build_id = 1;</code>
     * @return string
     */
    public function getBuildId()
    {
        return $this->build_id;
    }

    /**
     * An opaque whole-worker identifier
     *
     * Generated from protobuf field <code>string build_id = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setBuildId($var)
    {
        GPBUtil::checkString($var, True);
        $this->build_id = $var;

        return $this;
    }

    /**
     * If set, the worker is opting in to worker versioning, and wishes to only receive appropriate
     * tasks.
     *
     * Generated from protobuf field <code>bool use_versioning = 2;</code>
     * @return bool
     */
    public function getUseVersioning()
    {
        return $this->use_versioning;
    }

    /**
     * If set, the worker is opting in to worker versioning, and wishes to only receive appropriate
     * tasks.
     *
     * Generated from protobuf field <code>bool use_versioning = 2;</code>
     * @param bool $var
     * @return $this
     */
    public function setUseVersioning($var)
    {
        GPBUtil::checkBool($var);
        $this->use_versioning = $var;

        return $this;
    }

    /**
     * Must be sent if user has set a deployment series name (versioning-3).
     *
     * Generated from protobuf field <code>string deployment_series_name = 4;</code>
     * @return string
     */
    public function getDeploymentSeriesName()
    {
        return $this->deployment_series_name;
    }

    /**
     * Must be sent if user has set a deployment series name (versioning-3).
     *
     * Generated from protobuf field <code>string deployment_series_name = 4;</code>
     * @param string $var
     * @return $this
     */
    public function setDeploymentSeriesName($var)
    {
        GPBUtil::checkString($var, True);
        $this->deployment_series_name = $var;

        return $this;
    }

}

