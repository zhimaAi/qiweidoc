<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# NO CHECKED-IN PROTOBUF GENCODE
# source: temporal/api/batch/v1/message.proto

namespace GPBMetadata\Temporal\Api\Batch\V1;

class Message
{
    public static $is_initialized = false;

    public static function initOnce() {
        $pool = \Google\Protobuf\Internal\DescriptorPool::getGeneratedPool();

        if (static::$is_initialized == true) {
          return;
        }
        \GPBMetadata\Google\Protobuf\Duration::initOnce();
        \GPBMetadata\Google\Protobuf\FieldMask::initOnce();
        \GPBMetadata\Google\Protobuf\Timestamp::initOnce();
        \GPBMetadata\Temporal\Api\Common\V1\Message::initOnce();
        \GPBMetadata\Temporal\Api\Enums\V1\BatchOperation::initOnce();
        \GPBMetadata\Temporal\Api\Enums\V1\Reset::initOnce();
        \GPBMetadata\Temporal\Api\Rules\V1\Message::initOnce();
        \GPBMetadata\Temporal\Api\Workflow\V1\Message::initOnce();
        $pool->internalAddGeneratedFile(
            "\x0A\xDF\x0D\x0A#temporal/api/batch/v1/message.proto\x12\x15temporal.api.batch.v1\x1A google/protobuf/field_mask.proto\x1A\x1Fgoogle/protobuf/timestamp.proto\x1A\$temporal/api/common/v1/message.proto\x1A+temporal/api/enums/v1/batch_operation.proto\x1A!temporal/api/enums/v1/reset.proto\x1A#temporal/api/rules/v1/message.proto\x1A&temporal/api/workflow/v1/message.proto\"\xBF\x01\x0A\x12BatchOperationInfo\x12\x0E\x0A\x06job_id\x18\x01 \x01(\x09\x129\x0A\x05state\x18\x02 \x01(\x0E2*.temporal.api.enums.v1.BatchOperationState\x12.\x0A\x0Astart_time\x18\x03 \x01(\x0B2\x1A.google.protobuf.Timestamp\x12.\x0A\x0Aclose_time\x18\x04 \x01(\x0B2\x1A.google.protobuf.Timestamp\"`\x0A\x19BatchOperationTermination\x121\x0A\x07details\x18\x01 \x01(\x0B2 .temporal.api.common.v1.Payloads\x12\x10\x0A\x08identity\x18\x02 \x01(\x09\"\x99\x01\x0A\x14BatchOperationSignal\x12\x0E\x0A\x06signal\x18\x01 \x01(\x09\x12/\x0A\x05input\x18\x02 \x01(\x0B2 .temporal.api.common.v1.Payloads\x12.\x0A\x06header\x18\x03 \x01(\x0B2\x1E.temporal.api.common.v1.Header\x12\x10\x0A\x08identity\x18\x04 \x01(\x09\".\x0A\x1ABatchOperationCancellation\x12\x10\x0A\x08identity\x18\x01 \x01(\x09\"*\x0A\x16BatchOperationDeletion\x12\x10\x0A\x08identity\x18\x01 \x01(\x09\"\xD9\x01\x0A\x13BatchOperationReset\x12\x10\x0A\x08identity\x18\x03 \x01(\x09\x125\x0A\x07options\x18\x04 \x01(\x0B2\$.temporal.api.common.v1.ResetOptions\x124\x0A\x0Areset_type\x18\x01 \x01(\x0E2 .temporal.api.enums.v1.ResetType\x12C\x0A\x12reset_reapply_type\x18\x02 \x01(\x0E2'.temporal.api.enums.v1.ResetReapplyType\"\xC9\x01\x0A,BatchOperationUpdateWorkflowExecutionOptions\x12\x10\x0A\x08identity\x18\x01 \x01(\x09\x12V\x0A\x1Aworkflow_execution_options\x18\x02 \x01(\x0B22.temporal.api.workflow.v1.WorkflowExecutionOptions\x12/\x0A\x0Bupdate_mask\x18\x03 \x01(\x0B2\x1A.google.protobuf.FieldMask\"\xC0\x01\x0A\x1FBatchOperationUnpauseActivities\x12\x10\x0A\x08identity\x18\x01 \x01(\x09\x12\x0E\x0A\x04type\x18\x02 \x01(\x09H\x00\x12\x13\x0A\x09match_all\x18\x03 \x01(\x08H\x00\x12\x16\x0A\x0Ereset_attempts\x18\x04 \x01(\x08\x12\x17\x0A\x0Freset_heartbeat\x18\x05 \x01(\x08\x12)\x0A\x06jitter\x18\x06 \x01(\x0B2\x19.google.protobuf.DurationB\x0A\x0A\x08activity\"\x84\x01\x0A!BatchOperationTriggerWorkflowRule\x12\x10\x0A\x08identity\x18\x01 \x01(\x09\x12\x0C\x0A\x02id\x18\x02 \x01(\x09H\x00\x127\x0A\x04spec\x18\x03 \x01(\x0B2'.temporal.api.rules.v1.WorkflowRuleSpecH\x00B\x06\x0A\x04ruleB\x84\x01\x0A\x18io.temporal.api.batch.v1B\x0CMessageProtoP\x01Z!go.temporal.io/api/batch/v1;batch\xAA\x02\x17Temporalio.Api.Batch.V1\xEA\x02\x1ATemporalio::Api::Batch::V1b\x06proto3"
        , true);

        static::$is_initialized = true;
    }
}

