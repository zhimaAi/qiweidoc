<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# NO CHECKED-IN PROTOBUF GENCODE
# source: temporal/api/enums/v1/common.proto

namespace GPBMetadata\Temporal\Api\Enums\V1;

class Common
{
    public static $is_initialized = false;

    public static function initOnce() {
        $pool = \Google\Protobuf\Internal\DescriptorPool::getGeneratedPool();

        if (static::$is_initialized == true) {
          return;
        }
        $pool->internalAddGeneratedFile(
            "\x0A\x89\x0E\x0A\"temporal/api/enums/v1/common.proto\x12\x15temporal.api.enums.v1*_\x0A\x0CEncodingType\x12\x1D\x0A\x19ENCODING_TYPE_UNSPECIFIED\x10\x00\x12\x18\x0A\x14ENCODING_TYPE_PROTO3\x10\x01\x12\x16\x0A\x12ENCODING_TYPE_JSON\x10\x02*\x91\x02\x0A\x10IndexedValueType\x12\"\x0A\x1EINDEXED_VALUE_TYPE_UNSPECIFIED\x10\x00\x12\x1B\x0A\x17INDEXED_VALUE_TYPE_TEXT\x10\x01\x12\x1E\x0A\x1AINDEXED_VALUE_TYPE_KEYWORD\x10\x02\x12\x1A\x0A\x16INDEXED_VALUE_TYPE_INT\x10\x03\x12\x1D\x0A\x19INDEXED_VALUE_TYPE_DOUBLE\x10\x04\x12\x1B\x0A\x17INDEXED_VALUE_TYPE_BOOL\x10\x05\x12\x1F\x0A\x1BINDEXED_VALUE_TYPE_DATETIME\x10\x06\x12#\x0A\x1FINDEXED_VALUE_TYPE_KEYWORD_LIST\x10\x07*^\x0A\x08Severity\x12\x18\x0A\x14SEVERITY_UNSPECIFIED\x10\x00\x12\x11\x0A\x0DSEVERITY_HIGH\x10\x01\x12\x13\x0A\x0FSEVERITY_MEDIUM\x10\x02\x12\x10\x0A\x0CSEVERITY_LOW\x10\x03*\xDE\x01\x0A\x0DCallbackState\x12\x1E\x0A\x1ACALLBACK_STATE_UNSPECIFIED\x10\x00\x12\x1A\x0A\x16CALLBACK_STATE_STANDBY\x10\x01\x12\x1C\x0A\x18CALLBACK_STATE_SCHEDULED\x10\x02\x12\x1E\x0A\x1ACALLBACK_STATE_BACKING_OFF\x10\x03\x12\x19\x0A\x15CALLBACK_STATE_FAILED\x10\x04\x12\x1C\x0A\x18CALLBACK_STATE_SUCCEEDED\x10\x05\x12\x1A\x0A\x16CALLBACK_STATE_BLOCKED\x10\x06*\xFD\x01\x0A\x1APendingNexusOperationState\x12-\x0A)PENDING_NEXUS_OPERATION_STATE_UNSPECIFIED\x10\x00\x12+\x0A'PENDING_NEXUS_OPERATION_STATE_SCHEDULED\x10\x01\x12-\x0A)PENDING_NEXUS_OPERATION_STATE_BACKING_OFF\x10\x02\x12)\x0A%PENDING_NEXUS_OPERATION_STATE_STARTED\x10\x03\x12)\x0A%PENDING_NEXUS_OPERATION_STATE_BLOCKED\x10\x04*\xFE\x02\x0A\x1FNexusOperationCancellationState\x122\x0A.NEXUS_OPERATION_CANCELLATION_STATE_UNSPECIFIED\x10\x00\x120\x0A,NEXUS_OPERATION_CANCELLATION_STATE_SCHEDULED\x10\x01\x122\x0A.NEXUS_OPERATION_CANCELLATION_STATE_BACKING_OFF\x10\x02\x120\x0A,NEXUS_OPERATION_CANCELLATION_STATE_SUCCEEDED\x10\x03\x12-\x0A)NEXUS_OPERATION_CANCELLATION_STATE_FAILED\x10\x04\x120\x0A,NEXUS_OPERATION_CANCELLATION_STATE_TIMED_OUT\x10\x05\x12.\x0A*NEXUS_OPERATION_CANCELLATION_STATE_BLOCKED\x10\x06*\x97\x01\x0A\x17WorkflowRuleActionScope\x12*\x0A&WORKFLOW_RULE_ACTION_SCOPE_UNSPECIFIED\x10\x00\x12'\x0A#WORKFLOW_RULE_ACTION_SCOPE_WORKFLOW\x10\x01\x12'\x0A#WORKFLOW_RULE_ACTION_SCOPE_ACTIVITY\x10\x02*m\x0A\x18ApplicationErrorCategory\x12*\x0A&APPLICATION_ERROR_CATEGORY_UNSPECIFIED\x10\x00\x12%\x0A!APPLICATION_ERROR_CATEGORY_BENIGN\x10\x01B\x83\x01\x0A\x18io.temporal.api.enums.v1B\x0BCommonProtoP\x01Z!go.temporal.io/api/enums/v1;enums\xAA\x02\x17Temporalio.Api.Enums.V1\xEA\x02\x1ATemporalio::Api::Enums::V1b\x06proto3"
        , true);

        static::$is_initialized = true;
    }
}

