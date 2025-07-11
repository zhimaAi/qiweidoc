<?php
// GENERATED CODE -- DO NOT EDIT!

// Original file comments:
// The MIT License
//
// Copyright (c) 2020 Temporal Technologies Inc.  All rights reserved.
//
// Permission is hereby granted, free of charge, to any person obtaining a copy
// of this software and associated documentation files (the "Software"), to deal
// in the Software without restriction, including without limitation the rights
// to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
// copies of the Software, and to permit persons to whom the Software is
// furnished to do so, subject to the following conditions:
//
// The above copyright notice and this permission notice shall be included in
// all copies or substantial portions of the Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
// AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
// THE SOFTWARE.
//
namespace Temporal\Api\Workflowservice\V1;

/**
 * WorkflowService API defines how Temporal SDKs and other clients interact with the Temporal server
 * to create and interact with workflows and activities.
 *
 * Users are expected to call `StartWorkflowExecution` to create a new workflow execution.
 *
 * To drive workflows, a worker using a Temporal SDK must exist which regularly polls for workflow
 * and activity tasks from the service. For each workflow task, the sdk must process the
 * (incremental or complete) event history and respond back with any newly generated commands.
 *
 * For each activity task, the worker is expected to execute the user's code which implements that
 * activity, responding with completion or failure.
 */
class WorkflowServiceClient extends \Grpc\BaseStub {

    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * RegisterNamespace creates a new namespace which can be used as a container for all resources.
     *
     * A Namespace is a top level entity within Temporal, and is used as a container for resources
     * like workflow executions, task queues, etc. A Namespace acts as a sandbox and provides
     * isolation for all resources within the namespace. All resources belongs to exactly one
     * namespace.
     * @param \Temporal\Api\Workflowservice\V1\RegisterNamespaceRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function RegisterNamespace(\Temporal\Api\Workflowservice\V1\RegisterNamespaceRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/RegisterNamespace',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\RegisterNamespaceResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * DescribeNamespace returns the information and configuration for a registered namespace.
     * @param \Temporal\Api\Workflowservice\V1\DescribeNamespaceRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function DescribeNamespace(\Temporal\Api\Workflowservice\V1\DescribeNamespaceRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/DescribeNamespace',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\DescribeNamespaceResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * ListNamespaces returns the information and configuration for all namespaces.
     * @param \Temporal\Api\Workflowservice\V1\ListNamespacesRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ListNamespaces(\Temporal\Api\Workflowservice\V1\ListNamespacesRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/ListNamespaces',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\ListNamespacesResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * UpdateNamespace is used to update the information and configuration of a registered
     * namespace.
     * @param \Temporal\Api\Workflowservice\V1\UpdateNamespaceRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function UpdateNamespace(\Temporal\Api\Workflowservice\V1\UpdateNamespaceRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/UpdateNamespace',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\UpdateNamespaceResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * DeprecateNamespace is used to update the state of a registered namespace to DEPRECATED.
     *
     * Once the namespace is deprecated it cannot be used to start new workflow executions. Existing
     * workflow executions will continue to run on deprecated namespaces.
     * Deprecated.
     *
     * (-- api-linter: core::0127::http-annotation=disabled
     *     aip.dev/not-precedent: Deprecated --)
     * @param \Temporal\Api\Workflowservice\V1\DeprecateNamespaceRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function DeprecateNamespace(\Temporal\Api\Workflowservice\V1\DeprecateNamespaceRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/DeprecateNamespace',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\DeprecateNamespaceResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * StartWorkflowExecution starts a new workflow execution.
     *
     * It will create the execution with a `WORKFLOW_EXECUTION_STARTED` event in its history and
     * also schedule the first workflow task. Returns `WorkflowExecutionAlreadyStarted`, if an
     * instance already exists with same workflow id.
     * @param \Temporal\Api\Workflowservice\V1\StartWorkflowExecutionRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function StartWorkflowExecution(\Temporal\Api\Workflowservice\V1\StartWorkflowExecutionRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/StartWorkflowExecution',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\StartWorkflowExecutionResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * ExecuteMultiOperation executes multiple operations within a single workflow.
     *
     * Operations are started atomically, meaning if *any* operation fails to be started, none are,
     * and the request fails. Upon start, the API returns only when *all* operations have a response.
     *
     * Upon failure, it returns `MultiOperationExecutionFailure` where the status code
     * equals the status code of the *first* operation that failed to be started.
     *
     * NOTE: Experimental API.
     * @param \Temporal\Api\Workflowservice\V1\ExecuteMultiOperationRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ExecuteMultiOperation(\Temporal\Api\Workflowservice\V1\ExecuteMultiOperationRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/ExecuteMultiOperation',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\ExecuteMultiOperationResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * GetWorkflowExecutionHistory returns the history of specified workflow execution. Fails with
     * `NotFound` if the specified workflow execution is unknown to the service.
     * @param \Temporal\Api\Workflowservice\V1\GetWorkflowExecutionHistoryRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function GetWorkflowExecutionHistory(\Temporal\Api\Workflowservice\V1\GetWorkflowExecutionHistoryRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/GetWorkflowExecutionHistory',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\GetWorkflowExecutionHistoryResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * GetWorkflowExecutionHistoryReverse returns the history of specified workflow execution in reverse 
     * order (starting from last event). Fails with`NotFound` if the specified workflow execution is 
     * unknown to the service.
     * @param \Temporal\Api\Workflowservice\V1\GetWorkflowExecutionHistoryReverseRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function GetWorkflowExecutionHistoryReverse(\Temporal\Api\Workflowservice\V1\GetWorkflowExecutionHistoryReverseRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/GetWorkflowExecutionHistoryReverse',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\GetWorkflowExecutionHistoryReverseResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * PollWorkflowTaskQueue is called by workers to make progress on workflows.
     *
     * A WorkflowTask is dispatched to callers for active workflow executions with pending workflow
     * tasks. The worker is expected to call `RespondWorkflowTaskCompleted` when it is done
     * processing the task. The service will create a `WorkflowTaskStarted` event in the history for
     * this task before handing it to the worker.
     *
     * (-- api-linter: core::0127::http-annotation=disabled
     *     aip.dev/not-precedent: We do not expose worker API to HTTP. --)
     * @param \Temporal\Api\Workflowservice\V1\PollWorkflowTaskQueueRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function PollWorkflowTaskQueue(\Temporal\Api\Workflowservice\V1\PollWorkflowTaskQueueRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/PollWorkflowTaskQueue',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\PollWorkflowTaskQueueResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * RespondWorkflowTaskCompleted is called by workers to successfully complete workflow tasks
     * they received from `PollWorkflowTaskQueue`.
     *
     * Completing a WorkflowTask will write a `WORKFLOW_TASK_COMPLETED` event to the workflow's
     * history, along with events corresponding to whatever commands the SDK generated while
     * executing the task (ex timer started, activity task scheduled, etc).
     *
     * (-- api-linter: core::0127::http-annotation=disabled
     *     aip.dev/not-precedent: We do not expose worker API to HTTP. --)
     * @param \Temporal\Api\Workflowservice\V1\RespondWorkflowTaskCompletedRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function RespondWorkflowTaskCompleted(\Temporal\Api\Workflowservice\V1\RespondWorkflowTaskCompletedRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/RespondWorkflowTaskCompleted',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\RespondWorkflowTaskCompletedResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * RespondWorkflowTaskFailed is called by workers to indicate the processing of a workflow task
     * failed.
     *
     * This results in a `WORKFLOW_TASK_FAILED` event written to the history, and a new workflow
     * task will be scheduled. This API can be used to report unhandled failures resulting from
     * applying the workflow task.
     *
     * Temporal will only append first WorkflowTaskFailed event to the history of workflow execution
     * for consecutive failures.
     *
     * (-- api-linter: core::0127::http-annotation=disabled
     *     aip.dev/not-precedent: We do not expose worker API to HTTP. --)
     * @param \Temporal\Api\Workflowservice\V1\RespondWorkflowTaskFailedRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function RespondWorkflowTaskFailed(\Temporal\Api\Workflowservice\V1\RespondWorkflowTaskFailedRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/RespondWorkflowTaskFailed',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\RespondWorkflowTaskFailedResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * PollActivityTaskQueue is called by workers to process activity tasks from a specific task
     * queue.
     *
     * The worker is expected to call one of the `RespondActivityTaskXXX` methods when it is done
     * processing the task.
     *
     * An activity task is dispatched whenever a `SCHEDULE_ACTIVITY_TASK` command is produced during
     * workflow execution. An in memory `ACTIVITY_TASK_STARTED` event is written to mutable state
     * before the task is dispatched to the worker. The started event, and the final event
     * (`ACTIVITY_TASK_COMPLETED` / `ACTIVITY_TASK_FAILED` / `ACTIVITY_TASK_TIMED_OUT`) will both be
     * written permanently to Workflow execution history when Activity is finished. This is done to
     * avoid writing many events in the case of a failure/retry loop.
     *
     * (-- api-linter: core::0127::http-annotation=disabled
     *     aip.dev/not-precedent: We do not expose worker API to HTTP. --)
     * @param \Temporal\Api\Workflowservice\V1\PollActivityTaskQueueRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function PollActivityTaskQueue(\Temporal\Api\Workflowservice\V1\PollActivityTaskQueueRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/PollActivityTaskQueue',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\PollActivityTaskQueueResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * RecordActivityTaskHeartbeat is optionally called by workers while they execute activities.
     *
     * If worker fails to heartbeat within the `heartbeat_timeout` interval for the activity task,
     * then it will be marked as timed out and an `ACTIVITY_TASK_TIMED_OUT` event will be written to
     * the workflow history. Calling `RecordActivityTaskHeartbeat` will fail with `NotFound` in
     * such situations, in that event, the SDK should request cancellation of the activity.
     * @param \Temporal\Api\Workflowservice\V1\RecordActivityTaskHeartbeatRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function RecordActivityTaskHeartbeat(\Temporal\Api\Workflowservice\V1\RecordActivityTaskHeartbeatRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/RecordActivityTaskHeartbeat',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\RecordActivityTaskHeartbeatResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * See `RecordActivityTaskHeartbeat`. This version allows clients to record heartbeats by
     * namespace/workflow id/activity id instead of task token.
     *
     * (-- api-linter: core::0136::prepositions=disabled
     *     aip.dev/not-precedent: "By" is used to indicate request type. --)
     * @param \Temporal\Api\Workflowservice\V1\RecordActivityTaskHeartbeatByIdRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function RecordActivityTaskHeartbeatById(\Temporal\Api\Workflowservice\V1\RecordActivityTaskHeartbeatByIdRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/RecordActivityTaskHeartbeatById',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\RecordActivityTaskHeartbeatByIdResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * RespondActivityTaskCompleted is called by workers when they successfully complete an activity
     * task.
     *
     * This results in a new `ACTIVITY_TASK_COMPLETED` event being written to the workflow history
     * and a new workflow task created for the workflow. Fails with `NotFound` if the task token is
     * no longer valid due to activity timeout, already being completed, or never having existed.
     * @param \Temporal\Api\Workflowservice\V1\RespondActivityTaskCompletedRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function RespondActivityTaskCompleted(\Temporal\Api\Workflowservice\V1\RespondActivityTaskCompletedRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/RespondActivityTaskCompleted',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\RespondActivityTaskCompletedResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * See `RecordActivityTaskCompleted`. This version allows clients to record completions by
     * namespace/workflow id/activity id instead of task token.
     *
     * (-- api-linter: core::0136::prepositions=disabled
     *     aip.dev/not-precedent: "By" is used to indicate request type. --)
     * @param \Temporal\Api\Workflowservice\V1\RespondActivityTaskCompletedByIdRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function RespondActivityTaskCompletedById(\Temporal\Api\Workflowservice\V1\RespondActivityTaskCompletedByIdRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/RespondActivityTaskCompletedById',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\RespondActivityTaskCompletedByIdResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * RespondActivityTaskFailed is called by workers when processing an activity task fails.
     *
     * This results in a new `ACTIVITY_TASK_FAILED` event being written to the workflow history and
     * a new workflow task created for the workflow. Fails with `NotFound` if the task token is no
     * longer valid due to activity timeout, already being completed, or never having existed.
     * @param \Temporal\Api\Workflowservice\V1\RespondActivityTaskFailedRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function RespondActivityTaskFailed(\Temporal\Api\Workflowservice\V1\RespondActivityTaskFailedRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/RespondActivityTaskFailed',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\RespondActivityTaskFailedResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * See `RecordActivityTaskFailed`. This version allows clients to record failures by
     * namespace/workflow id/activity id instead of task token.
     *
     * (-- api-linter: core::0136::prepositions=disabled
     *     aip.dev/not-precedent: "By" is used to indicate request type. --)
     * @param \Temporal\Api\Workflowservice\V1\RespondActivityTaskFailedByIdRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function RespondActivityTaskFailedById(\Temporal\Api\Workflowservice\V1\RespondActivityTaskFailedByIdRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/RespondActivityTaskFailedById',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\RespondActivityTaskFailedByIdResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * RespondActivityTaskFailed is called by workers when processing an activity task fails.
     *
     * This results in a new `ACTIVITY_TASK_CANCELED` event being written to the workflow history
     * and a new workflow task created for the workflow. Fails with `NotFound` if the task token is
     * no longer valid due to activity timeout, already being completed, or never having existed.
     * @param \Temporal\Api\Workflowservice\V1\RespondActivityTaskCanceledRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function RespondActivityTaskCanceled(\Temporal\Api\Workflowservice\V1\RespondActivityTaskCanceledRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/RespondActivityTaskCanceled',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\RespondActivityTaskCanceledResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * See `RecordActivityTaskCanceled`. This version allows clients to record failures by
     * namespace/workflow id/activity id instead of task token.
     *
     * (-- api-linter: core::0136::prepositions=disabled
     *     aip.dev/not-precedent: "By" is used to indicate request type. --)
     * @param \Temporal\Api\Workflowservice\V1\RespondActivityTaskCanceledByIdRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function RespondActivityTaskCanceledById(\Temporal\Api\Workflowservice\V1\RespondActivityTaskCanceledByIdRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/RespondActivityTaskCanceledById',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\RespondActivityTaskCanceledByIdResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * RequestCancelWorkflowExecution is called by workers when they want to request cancellation of
     * a workflow execution.
     *
     * This results in a new `WORKFLOW_EXECUTION_CANCEL_REQUESTED` event being written to the
     * workflow history and a new workflow task created for the workflow. It returns success if the requested
     * workflow is already closed. It fails with 'NotFound' if the requested workflow doesn't exist.
     * @param \Temporal\Api\Workflowservice\V1\RequestCancelWorkflowExecutionRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function RequestCancelWorkflowExecution(\Temporal\Api\Workflowservice\V1\RequestCancelWorkflowExecutionRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/RequestCancelWorkflowExecution',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\RequestCancelWorkflowExecutionResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * SignalWorkflowExecution is used to send a signal to a running workflow execution.
     *
     * This results in a `WORKFLOW_EXECUTION_SIGNALED` event recorded in the history and a workflow
     * task being created for the execution.
     * @param \Temporal\Api\Workflowservice\V1\SignalWorkflowExecutionRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function SignalWorkflowExecution(\Temporal\Api\Workflowservice\V1\SignalWorkflowExecutionRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/SignalWorkflowExecution',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\SignalWorkflowExecutionResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * SignalWithStartWorkflowExecution is used to ensure a signal is sent to a workflow, even if
     * it isn't yet started.
     *
     * If the workflow is running, a `WORKFLOW_EXECUTION_SIGNALED` event is recorded in the history
     * and a workflow task is generated.
     *
     * If the workflow is not running or not found, then the workflow is created with
     * `WORKFLOW_EXECUTION_STARTED` and `WORKFLOW_EXECUTION_SIGNALED` events in its history, and a
     * workflow task is generated.
     *
     * (-- api-linter: core::0136::prepositions=disabled
     *     aip.dev/not-precedent: "With" is used to indicate combined operation. --)
     * @param \Temporal\Api\Workflowservice\V1\SignalWithStartWorkflowExecutionRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function SignalWithStartWorkflowExecution(\Temporal\Api\Workflowservice\V1\SignalWithStartWorkflowExecutionRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/SignalWithStartWorkflowExecution',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\SignalWithStartWorkflowExecutionResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * ResetWorkflowExecution will reset an existing workflow execution to a specified
     * `WORKFLOW_TASK_COMPLETED` event (exclusive). It will immediately terminate the current
     * execution instance.
     * TODO: Does exclusive here mean *just* the completed event, or also WFT started? Otherwise the task is doomed to time out?
     * @param \Temporal\Api\Workflowservice\V1\ResetWorkflowExecutionRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ResetWorkflowExecution(\Temporal\Api\Workflowservice\V1\ResetWorkflowExecutionRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/ResetWorkflowExecution',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\ResetWorkflowExecutionResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * TerminateWorkflowExecution terminates an existing workflow execution by recording a
     * `WORKFLOW_EXECUTION_TERMINATED` event in the history and immediately terminating the
     * execution instance.
     * @param \Temporal\Api\Workflowservice\V1\TerminateWorkflowExecutionRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function TerminateWorkflowExecution(\Temporal\Api\Workflowservice\V1\TerminateWorkflowExecutionRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/TerminateWorkflowExecution',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\TerminateWorkflowExecutionResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * DeleteWorkflowExecution asynchronously deletes a specific Workflow Execution (when
     * WorkflowExecution.run_id is provided) or the latest Workflow Execution (when
     * WorkflowExecution.run_id is not provided). If the Workflow Execution is Running, it will be
     * terminated before deletion.
     *
     * (-- api-linter: core::0127::http-annotation=disabled
     *     aip.dev/not-precedent: Workflow deletion not exposed to HTTP, users should use cancel or terminate. --)
     * @param \Temporal\Api\Workflowservice\V1\DeleteWorkflowExecutionRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function DeleteWorkflowExecution(\Temporal\Api\Workflowservice\V1\DeleteWorkflowExecutionRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/DeleteWorkflowExecution',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\DeleteWorkflowExecutionResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * ListOpenWorkflowExecutions is a visibility API to list the open executions in a specific namespace.
     *
     * (-- api-linter: core::0127::http-annotation=disabled
     *     aip.dev/not-precedent: HTTP users should use ListWorkflowExecutions instead. --)
     * @param \Temporal\Api\Workflowservice\V1\ListOpenWorkflowExecutionsRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ListOpenWorkflowExecutions(\Temporal\Api\Workflowservice\V1\ListOpenWorkflowExecutionsRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/ListOpenWorkflowExecutions',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\ListOpenWorkflowExecutionsResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * ListClosedWorkflowExecutions is a visibility API to list the closed executions in a specific namespace.
     *
     * (-- api-linter: core::0127::http-annotation=disabled
     *     aip.dev/not-precedent: HTTP users should use ListWorkflowExecutions instead. --)
     * @param \Temporal\Api\Workflowservice\V1\ListClosedWorkflowExecutionsRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ListClosedWorkflowExecutions(\Temporal\Api\Workflowservice\V1\ListClosedWorkflowExecutionsRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/ListClosedWorkflowExecutions',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\ListClosedWorkflowExecutionsResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * ListWorkflowExecutions is a visibility API to list workflow executions in a specific namespace.
     * @param \Temporal\Api\Workflowservice\V1\ListWorkflowExecutionsRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ListWorkflowExecutions(\Temporal\Api\Workflowservice\V1\ListWorkflowExecutionsRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/ListWorkflowExecutions',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\ListWorkflowExecutionsResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * ListArchivedWorkflowExecutions is a visibility API to list archived workflow executions in a specific namespace.
     * @param \Temporal\Api\Workflowservice\V1\ListArchivedWorkflowExecutionsRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ListArchivedWorkflowExecutions(\Temporal\Api\Workflowservice\V1\ListArchivedWorkflowExecutionsRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/ListArchivedWorkflowExecutions',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\ListArchivedWorkflowExecutionsResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * ScanWorkflowExecutions is a visibility API to list large amount of workflow executions in a specific namespace without order.
     *
     * Deprecated: Replaced with `ListWorkflowExecutions`.
     * (-- api-linter: core::0127::http-annotation=disabled
     *     aip.dev/not-precedent: HTTP users should use ListWorkflowExecutions instead. --)
     * @param \Temporal\Api\Workflowservice\V1\ScanWorkflowExecutionsRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ScanWorkflowExecutions(\Temporal\Api\Workflowservice\V1\ScanWorkflowExecutionsRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/ScanWorkflowExecutions',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\ScanWorkflowExecutionsResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * CountWorkflowExecutions is a visibility API to count of workflow executions in a specific namespace.
     * @param \Temporal\Api\Workflowservice\V1\CountWorkflowExecutionsRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function CountWorkflowExecutions(\Temporal\Api\Workflowservice\V1\CountWorkflowExecutionsRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/CountWorkflowExecutions',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\CountWorkflowExecutionsResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * GetSearchAttributes is a visibility API to get all legal keys that could be used in list APIs
     *
     * (-- api-linter: core::0127::http-annotation=disabled
     *     aip.dev/not-precedent: We do not expose this search attribute API to HTTP (but may expose on OperatorService). --)
     * @param \Temporal\Api\Workflowservice\V1\GetSearchAttributesRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function GetSearchAttributes(\Temporal\Api\Workflowservice\V1\GetSearchAttributesRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/GetSearchAttributes',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\GetSearchAttributesResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * RespondQueryTaskCompleted is called by workers to complete queries which were delivered on
     * the `query` (not `queries`) field of a `PollWorkflowTaskQueueResponse`.
     *
     * Completing the query will unblock the corresponding client call to `QueryWorkflow` and return
     * the query result a response.
     *
     * (-- api-linter: core::0127::http-annotation=disabled
     *     aip.dev/not-precedent: We do not expose worker API to HTTP. --)
     * @param \Temporal\Api\Workflowservice\V1\RespondQueryTaskCompletedRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function RespondQueryTaskCompleted(\Temporal\Api\Workflowservice\V1\RespondQueryTaskCompletedRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/RespondQueryTaskCompleted',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\RespondQueryTaskCompletedResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * ResetStickyTaskQueue resets the sticky task queue related information in the mutable state of
     * a given workflow. This is prudent for workers to perform if a workflow has been paged out of
     * their cache.
     *
     * Things cleared are:
     * 1. StickyTaskQueue
     * 2. StickyScheduleToStartTimeout
     *
     * When possible, ShutdownWorker should be preferred over
     * ResetStickyTaskQueue (particularly when a worker is shutting down or
     * cycling).
     *
     * (-- api-linter: core::0127::http-annotation=disabled
     *     aip.dev/not-precedent: We do not expose worker API to HTTP. --)
     * @param \Temporal\Api\Workflowservice\V1\ResetStickyTaskQueueRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ResetStickyTaskQueue(\Temporal\Api\Workflowservice\V1\ResetStickyTaskQueueRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/ResetStickyTaskQueue',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\ResetStickyTaskQueueResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * ShutdownWorker is used to indicate that the given sticky task
     * queue is no longer being polled by its worker. Following the completion of
     * ShutdownWorker, newly-added workflow tasks will instead be placed
     * in the normal task queue, eligible for any worker to pick up.
     *
     * ShutdownWorker should be called by workers while shutting down,
     * after they've shut down their pollers. If another sticky poll
     * request is issued, the sticky task queue will be revived.
     *
     * As of Temporal Server v1.25.0, ShutdownWorker hasn't yet been implemented.
     *
     * (-- api-linter: core::0127::http-annotation=disabled
     *     aip.dev/not-precedent: We do not expose worker API to HTTP. --)
     * @param \Temporal\Api\Workflowservice\V1\ShutdownWorkerRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ShutdownWorker(\Temporal\Api\Workflowservice\V1\ShutdownWorkerRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/ShutdownWorker',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\ShutdownWorkerResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * QueryWorkflow requests a query be executed for a specified workflow execution.
     * @param \Temporal\Api\Workflowservice\V1\QueryWorkflowRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function QueryWorkflow(\Temporal\Api\Workflowservice\V1\QueryWorkflowRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/QueryWorkflow',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\QueryWorkflowResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * DescribeWorkflowExecution returns information about the specified workflow execution.
     * @param \Temporal\Api\Workflowservice\V1\DescribeWorkflowExecutionRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function DescribeWorkflowExecution(\Temporal\Api\Workflowservice\V1\DescribeWorkflowExecutionRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/DescribeWorkflowExecution',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\DescribeWorkflowExecutionResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * DescribeTaskQueue returns the following information about the target task queue, broken down by Build ID:
     *   - List of pollers
     *   - Workflow Reachability status
     *   - Backlog info for Workflow and/or Activity tasks
     * @param \Temporal\Api\Workflowservice\V1\DescribeTaskQueueRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function DescribeTaskQueue(\Temporal\Api\Workflowservice\V1\DescribeTaskQueueRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/DescribeTaskQueue',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\DescribeTaskQueueResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * GetClusterInfo returns information about temporal cluster
     * @param \Temporal\Api\Workflowservice\V1\GetClusterInfoRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function GetClusterInfo(\Temporal\Api\Workflowservice\V1\GetClusterInfoRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/GetClusterInfo',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\GetClusterInfoResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * GetSystemInfo returns information about the system.
     * @param \Temporal\Api\Workflowservice\V1\GetSystemInfoRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function GetSystemInfo(\Temporal\Api\Workflowservice\V1\GetSystemInfoRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/GetSystemInfo',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\GetSystemInfoResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * (-- api-linter: core::0127::http-annotation=disabled
     *     aip.dev/not-precedent: We do not expose this low-level API to HTTP. --)
     * @param \Temporal\Api\Workflowservice\V1\ListTaskQueuePartitionsRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ListTaskQueuePartitions(\Temporal\Api\Workflowservice\V1\ListTaskQueuePartitionsRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/ListTaskQueuePartitions',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\ListTaskQueuePartitionsResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Creates a new schedule.
     * @param \Temporal\Api\Workflowservice\V1\CreateScheduleRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function CreateSchedule(\Temporal\Api\Workflowservice\V1\CreateScheduleRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/CreateSchedule',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\CreateScheduleResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Returns the schedule description and current state of an existing schedule.
     * @param \Temporal\Api\Workflowservice\V1\DescribeScheduleRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function DescribeSchedule(\Temporal\Api\Workflowservice\V1\DescribeScheduleRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/DescribeSchedule',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\DescribeScheduleResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Changes the configuration or state of an existing schedule.
     * @param \Temporal\Api\Workflowservice\V1\UpdateScheduleRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function UpdateSchedule(\Temporal\Api\Workflowservice\V1\UpdateScheduleRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/UpdateSchedule',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\UpdateScheduleResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Makes a specific change to a schedule or triggers an immediate action.
     * @param \Temporal\Api\Workflowservice\V1\PatchScheduleRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function PatchSchedule(\Temporal\Api\Workflowservice\V1\PatchScheduleRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/PatchSchedule',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\PatchScheduleResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Lists matching times within a range.
     * @param \Temporal\Api\Workflowservice\V1\ListScheduleMatchingTimesRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ListScheduleMatchingTimes(\Temporal\Api\Workflowservice\V1\ListScheduleMatchingTimesRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/ListScheduleMatchingTimes',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\ListScheduleMatchingTimesResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Deletes a schedule, removing it from the system.
     * @param \Temporal\Api\Workflowservice\V1\DeleteScheduleRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function DeleteSchedule(\Temporal\Api\Workflowservice\V1\DeleteScheduleRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/DeleteSchedule',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\DeleteScheduleResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * List all schedules in a namespace.
     * @param \Temporal\Api\Workflowservice\V1\ListSchedulesRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ListSchedules(\Temporal\Api\Workflowservice\V1\ListSchedulesRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/ListSchedules',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\ListSchedulesResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Deprecated. Use `UpdateWorkerVersioningRules`.
     *
     * Allows users to specify sets of worker build id versions on a per task queue basis. Versions
     * are ordered, and may be either compatible with some extant version, or a new incompatible
     * version, forming sets of ids which are incompatible with each other, but whose contained
     * members are compatible with one another.
     *
     * A single build id may be mapped to multiple task queues using this API for cases where a single process hosts
     * multiple workers. 
     * 
     * To query which workers can be retired, use the `GetWorkerTaskReachability` API.
     *
     * NOTE: The number of task queues mapped to a single build id is limited by the `limit.taskQueuesPerBuildId`
     * (default is 20), if this limit is exceeded this API will error with a FailedPrecondition.
     *
     * (-- api-linter: core::0127::http-annotation=disabled
     *     aip.dev/not-precedent: We do yet expose versioning API to HTTP. --)
     * @param \Temporal\Api\Workflowservice\V1\UpdateWorkerBuildIdCompatibilityRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function UpdateWorkerBuildIdCompatibility(\Temporal\Api\Workflowservice\V1\UpdateWorkerBuildIdCompatibilityRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/UpdateWorkerBuildIdCompatibility',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\UpdateWorkerBuildIdCompatibilityResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Deprecated. Use `GetWorkerVersioningRules`.
     * Fetches the worker build id versioning sets for a task queue.
     * @param \Temporal\Api\Workflowservice\V1\GetWorkerBuildIdCompatibilityRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function GetWorkerBuildIdCompatibility(\Temporal\Api\Workflowservice\V1\GetWorkerBuildIdCompatibilityRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/GetWorkerBuildIdCompatibility',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\GetWorkerBuildIdCompatibilityResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Use this API to manage Worker Versioning Rules for a given Task Queue. There are two types of
     * rules: Build ID Assignment rules and Compatible Build ID Redirect rules.
     *
     * Assignment rules determine how to assign new executions to a Build IDs. Their primary
     * use case is to specify the latest Build ID but they have powerful features for gradual rollout
     * of a new Build ID.
     *
     * Once a workflow execution is assigned to a Build ID and it completes its first Workflow Task,
     * the workflow stays on the assigned Build ID regardless of changes in assignment rules. This
     * eliminates the need for compatibility between versions when you only care about using the new
     * version for new workflows and let existing workflows finish in their own version.
     *
     * Activities, Child Workflows and Continue-as-New executions have the option to inherit the
     * Build ID of their parent/previous workflow or use the latest assignment rules to independently
     * select a Build ID.
     *
     * Redirect rules should only be used when you want to move workflows and activities assigned to
     * one Build ID (source) to another compatible Build ID (target). You are responsible to make sure
     * the target Build ID of a redirect rule is able to process event histories made by the source
     * Build ID by using [Patching](https://docs.temporal.io/workflows#patching) or other means.
     *
     * WARNING: Worker Versioning is not yet stable and the API and behavior may change incompatibly.
     * (-- api-linter: core::0127::http-annotation=disabled
     *     aip.dev/not-precedent: We do yet expose versioning API to HTTP. --)
     * @param \Temporal\Api\Workflowservice\V1\UpdateWorkerVersioningRulesRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function UpdateWorkerVersioningRules(\Temporal\Api\Workflowservice\V1\UpdateWorkerVersioningRulesRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/UpdateWorkerVersioningRules',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\UpdateWorkerVersioningRulesResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Fetches the Build ID assignment and redirect rules for a Task Queue.
     * WARNING: Worker Versioning is not yet stable and the API and behavior may change incompatibly.
     * @param \Temporal\Api\Workflowservice\V1\GetWorkerVersioningRulesRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function GetWorkerVersioningRules(\Temporal\Api\Workflowservice\V1\GetWorkerVersioningRulesRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/GetWorkerVersioningRules',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\GetWorkerVersioningRulesResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Deprecated. Use `DescribeTaskQueue`.
     *
     * Fetches task reachability to determine whether a worker may be retired.
     * The request may specify task queues to query for or let the server fetch all task queues mapped to the given
     * build IDs.
     *
     * When requesting a large number of task queues or all task queues associated with the given build ids in a
     * namespace, all task queues will be listed in the response but some of them may not contain reachability
     * information due to a server enforced limit. When reaching the limit, task queues that reachability information
     * could not be retrieved for will be marked with a single TASK_REACHABILITY_UNSPECIFIED entry. The caller may issue
     * another call to get the reachability for those task queues.
     *
     * Open source users can adjust this limit by setting the server's dynamic config value for
     * `limit.reachabilityTaskQueueScan` with the caveat that this call can strain the visibility store.
     * @param \Temporal\Api\Workflowservice\V1\GetWorkerTaskReachabilityRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function GetWorkerTaskReachability(\Temporal\Api\Workflowservice\V1\GetWorkerTaskReachabilityRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/GetWorkerTaskReachability',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\GetWorkerTaskReachabilityResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Describes a worker deployment.
     * Experimental. This API might significantly change or be removed in a future release.
     * Deprecated. Replaced with `DescribeWorkerDeploymentVersion`.
     * @param \Temporal\Api\Workflowservice\V1\DescribeDeploymentRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function DescribeDeployment(\Temporal\Api\Workflowservice\V1\DescribeDeploymentRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/DescribeDeployment',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\DescribeDeploymentResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Describes a worker deployment version.
     * Experimental. This API might significantly change or be removed in a future release.
     * @param \Temporal\Api\Workflowservice\V1\DescribeWorkerDeploymentVersionRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function DescribeWorkerDeploymentVersion(\Temporal\Api\Workflowservice\V1\DescribeWorkerDeploymentVersionRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/DescribeWorkerDeploymentVersion',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\DescribeWorkerDeploymentVersionResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Lists worker deployments in the namespace. Optionally can filter based on deployment series
     * name.
     * Experimental. This API might significantly change or be removed in a future release.
     * Deprecated. Replaced with `ListWorkerDeployments`.
     * @param \Temporal\Api\Workflowservice\V1\ListDeploymentsRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ListDeployments(\Temporal\Api\Workflowservice\V1\ListDeploymentsRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/ListDeployments',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\ListDeploymentsResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Returns the reachability level of a worker deployment to help users decide when it is time
     * to decommission a deployment. Reachability level is calculated based on the deployment's
     * `status` and existing workflows that depend on the given deployment for their execution.
     * Calculating reachability is relatively expensive. Therefore, server might return a recently
     * cached value. In such a case, the `last_update_time` will inform you about the actual
     * reachability calculation time.
     * Experimental. This API might significantly change or be removed in a future release.
     * Deprecated. Replaced with `DrainageInfo` returned by `DescribeWorkerDeploymentVersion`.
     * @param \Temporal\Api\Workflowservice\V1\GetDeploymentReachabilityRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function GetDeploymentReachability(\Temporal\Api\Workflowservice\V1\GetDeploymentReachabilityRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/GetDeploymentReachability',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\GetDeploymentReachabilityResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Returns the current deployment (and its info) for a given deployment series.
     * Experimental. This API might significantly change or be removed in a future release.
     * Deprecated. Replaced by `current_version` returned by `DescribeWorkerDeployment`.
     * @param \Temporal\Api\Workflowservice\V1\GetCurrentDeploymentRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function GetCurrentDeployment(\Temporal\Api\Workflowservice\V1\GetCurrentDeploymentRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/GetCurrentDeployment',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\GetCurrentDeploymentResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Sets a deployment as the current deployment for its deployment series. Can optionally update
     * the metadata of the deployment as well.
     * Experimental. This API might significantly change or be removed in a future release.
     * Deprecated. Replaced by `SetWorkerDeploymentCurrentVersion`.
     * @param \Temporal\Api\Workflowservice\V1\SetCurrentDeploymentRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function SetCurrentDeployment(\Temporal\Api\Workflowservice\V1\SetCurrentDeploymentRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/SetCurrentDeployment',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\SetCurrentDeploymentResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Set/unset the Current Version of a Worker Deployment. Automatically unsets the Ramping
     * Version if it is the Version being set as Current.
     * Experimental. This API might significantly change or be removed in a future release.
     * @param \Temporal\Api\Workflowservice\V1\SetWorkerDeploymentCurrentVersionRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function SetWorkerDeploymentCurrentVersion(\Temporal\Api\Workflowservice\V1\SetWorkerDeploymentCurrentVersionRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/SetWorkerDeploymentCurrentVersion',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\SetWorkerDeploymentCurrentVersionResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Describes a Worker Deployment.
     * Experimental. This API might significantly change or be removed in a future release.
     * @param \Temporal\Api\Workflowservice\V1\DescribeWorkerDeploymentRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function DescribeWorkerDeployment(\Temporal\Api\Workflowservice\V1\DescribeWorkerDeploymentRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/DescribeWorkerDeployment',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\DescribeWorkerDeploymentResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Deletes records of (an old) Deployment. A deployment can only be deleted if
     * it has no Version in it.
     * Experimental. This API might significantly change or be removed in a future release.
     * @param \Temporal\Api\Workflowservice\V1\DeleteWorkerDeploymentRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function DeleteWorkerDeployment(\Temporal\Api\Workflowservice\V1\DeleteWorkerDeploymentRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/DeleteWorkerDeployment',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\DeleteWorkerDeploymentResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Used for manual deletion of Versions. User can delete a Version only when all the
     * following conditions are met:
     *  - It is not the Current or Ramping Version of its Deployment.
     *  - It has no active pollers (none of the task queues in the Version have pollers)
     *  - It is not draining (see WorkerDeploymentVersionInfo.drainage_info). This condition
     *    can be skipped by passing `skip-drainage=true`.
     * Experimental. This API might significantly change or be removed in a future release.
     * @param \Temporal\Api\Workflowservice\V1\DeleteWorkerDeploymentVersionRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function DeleteWorkerDeploymentVersion(\Temporal\Api\Workflowservice\V1\DeleteWorkerDeploymentVersionRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/DeleteWorkerDeploymentVersion',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\DeleteWorkerDeploymentVersionResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Set/unset the Ramping Version of a Worker Deployment and its ramp percentage. Can be used for
     * gradual ramp to unversioned workers too.
     * Experimental. This API might significantly change or be removed in a future release.
     * @param \Temporal\Api\Workflowservice\V1\SetWorkerDeploymentRampingVersionRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function SetWorkerDeploymentRampingVersion(\Temporal\Api\Workflowservice\V1\SetWorkerDeploymentRampingVersionRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/SetWorkerDeploymentRampingVersion',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\SetWorkerDeploymentRampingVersionResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Lists all Worker Deployments that are tracked in the Namespace.
     * Experimental. This API might significantly change or be removed in a future release.
     * @param \Temporal\Api\Workflowservice\V1\ListWorkerDeploymentsRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ListWorkerDeployments(\Temporal\Api\Workflowservice\V1\ListWorkerDeploymentsRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/ListWorkerDeployments',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\ListWorkerDeploymentsResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Updates the user-given metadata attached to a Worker Deployment Version.
     * Experimental. This API might significantly change or be removed in a future release.
     * @param \Temporal\Api\Workflowservice\V1\UpdateWorkerDeploymentVersionMetadataRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function UpdateWorkerDeploymentVersionMetadata(\Temporal\Api\Workflowservice\V1\UpdateWorkerDeploymentVersionMetadataRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/UpdateWorkerDeploymentVersionMetadata',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\UpdateWorkerDeploymentVersionMetadataResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Invokes the specified Update function on user Workflow code.
     * @param \Temporal\Api\Workflowservice\V1\UpdateWorkflowExecutionRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function UpdateWorkflowExecution(\Temporal\Api\Workflowservice\V1\UpdateWorkflowExecutionRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/UpdateWorkflowExecution',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\UpdateWorkflowExecutionResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Polls a Workflow Execution for the outcome of a Workflow Update
     * previously issued through the UpdateWorkflowExecution RPC. The effective
     * timeout on this call will be shorter of the the caller-supplied gRPC
     * timeout and the server's configured long-poll timeout.
     *
     * (-- api-linter: core::0127::http-annotation=disabled
     *     aip.dev/not-precedent: We don't expose update polling API to HTTP in favor of a potential future non-blocking form. --)
     * @param \Temporal\Api\Workflowservice\V1\PollWorkflowExecutionUpdateRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function PollWorkflowExecutionUpdate(\Temporal\Api\Workflowservice\V1\PollWorkflowExecutionUpdateRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/PollWorkflowExecutionUpdate',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\PollWorkflowExecutionUpdateResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * StartBatchOperation starts a new batch operation
     * @param \Temporal\Api\Workflowservice\V1\StartBatchOperationRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function StartBatchOperation(\Temporal\Api\Workflowservice\V1\StartBatchOperationRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/StartBatchOperation',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\StartBatchOperationResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * StopBatchOperation stops a batch operation
     * @param \Temporal\Api\Workflowservice\V1\StopBatchOperationRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function StopBatchOperation(\Temporal\Api\Workflowservice\V1\StopBatchOperationRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/StopBatchOperation',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\StopBatchOperationResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * DescribeBatchOperation returns the information about a batch operation
     * @param \Temporal\Api\Workflowservice\V1\DescribeBatchOperationRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function DescribeBatchOperation(\Temporal\Api\Workflowservice\V1\DescribeBatchOperationRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/DescribeBatchOperation',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\DescribeBatchOperationResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * ListBatchOperations returns a list of batch operations
     * @param \Temporal\Api\Workflowservice\V1\ListBatchOperationsRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ListBatchOperations(\Temporal\Api\Workflowservice\V1\ListBatchOperationsRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/ListBatchOperations',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\ListBatchOperationsResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * PollNexusTaskQueue is a long poll call used by workers to receive Nexus tasks.
     * (-- api-linter: core::0127::http-annotation=disabled
     *     aip.dev/not-precedent: We do not expose worker API to HTTP. --)
     * @param \Temporal\Api\Workflowservice\V1\PollNexusTaskQueueRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function PollNexusTaskQueue(\Temporal\Api\Workflowservice\V1\PollNexusTaskQueueRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/PollNexusTaskQueue',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\PollNexusTaskQueueResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * RespondNexusTaskCompleted is called by workers to respond to Nexus tasks received via PollNexusTaskQueue.
     * (-- api-linter: core::0127::http-annotation=disabled
     *     aip.dev/not-precedent: We do not expose worker API to HTTP. --)
     * @param \Temporal\Api\Workflowservice\V1\RespondNexusTaskCompletedRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function RespondNexusTaskCompleted(\Temporal\Api\Workflowservice\V1\RespondNexusTaskCompletedRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/RespondNexusTaskCompleted',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\RespondNexusTaskCompletedResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * RespondNexusTaskFailed is called by workers to fail Nexus tasks received via PollNexusTaskQueue.
     * (-- api-linter: core::0127::http-annotation=disabled
     *     aip.dev/not-precedent: We do not expose worker API to HTTP. --)
     * @param \Temporal\Api\Workflowservice\V1\RespondNexusTaskFailedRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function RespondNexusTaskFailed(\Temporal\Api\Workflowservice\V1\RespondNexusTaskFailedRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/RespondNexusTaskFailed',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\RespondNexusTaskFailedResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * UpdateActivityOptions is called by the client to update the options of an activity by its ID or type.
     * If there are multiple pending activities of the provided type - all of them will be updated.
     * @param \Temporal\Api\Workflowservice\V1\UpdateActivityOptionsRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function UpdateActivityOptions(\Temporal\Api\Workflowservice\V1\UpdateActivityOptionsRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/UpdateActivityOptions',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\UpdateActivityOptionsResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * UpdateWorkflowExecutionOptions partially updates the WorkflowExecutionOptions of an existing workflow execution.
     * @param \Temporal\Api\Workflowservice\V1\UpdateWorkflowExecutionOptionsRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function UpdateWorkflowExecutionOptions(\Temporal\Api\Workflowservice\V1\UpdateWorkflowExecutionOptionsRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/UpdateWorkflowExecutionOptions',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\UpdateWorkflowExecutionOptionsResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * PauseActivity pauses the execution of an activity specified by its ID or type.
     * If there are multiple pending activities of the provided type - all of them will be paused
     *
     * Pausing an activity means:
     * - If the activity is currently waiting for a retry or is running and subsequently fails,
     *   it will not be rescheduled until it is unpaused.
     * - If the activity is already paused, calling this method will have no effect.
     * - If the activity is running and finishes successfully, the activity will be completed.
     * - If the activity is running and finishes with failure:
     *   * if there is no retry left - the activity will be completed.
     *   * if there are more retries left - the activity will be paused.
     * For long-running activities:
     * - activities in paused state will send a cancellation with "activity_paused" set to 'true' in response to 'RecordActivityTaskHeartbeat'.
     * - The activity should respond to the cancellation accordingly.
     *
     * Returns a `NotFound` error if there is no pending activity with the provided ID or type
     * @param \Temporal\Api\Workflowservice\V1\PauseActivityRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function PauseActivity(\Temporal\Api\Workflowservice\V1\PauseActivityRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/PauseActivity',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\PauseActivityResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * UnpauseActivity unpauses the execution of an activity specified by its ID or type.
     * If there are multiple pending activities of the provided type - all of them will be unpaused.
     *
     * If activity is not paused, this call will have no effect.
     * If the activity was paused while waiting for retry, it will be scheduled immediately (* see 'jitter' flag).
     * Once the activity is unpaused, all timeout timers will be regenerated.
     *
     * Flags:
     * 'jitter': the activity will be scheduled at a random time within the jitter duration.
     * 'reset_attempts': the number of attempts will be reset.
     * 'reset_heartbeat': the activity heartbeat timer and heartbeats will be reset.
     *
     * Returns a `NotFound` error if there is no pending activity with the provided ID or type
     * @param \Temporal\Api\Workflowservice\V1\UnpauseActivityRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function UnpauseActivity(\Temporal\Api\Workflowservice\V1\UnpauseActivityRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/UnpauseActivity',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\UnpauseActivityResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * ResetActivity resets the execution of an activity specified by its ID or type.
     * If there are multiple pending activities of the provided type - all of them will be reset.
     *
     * Resetting an activity means:
     * * number of attempts will be reset to 0.
     * * activity timeouts will be reset.
     * * if the activity is waiting for retry, and it is not paused or 'keep_paused' is not provided:
     *    it will be scheduled immediately (* see 'jitter' flag),
     *
     * Flags:
     *
     * 'jitter': the activity will be scheduled at a random time within the jitter duration.
     * If the activity currently paused it will be unpaused, unless 'keep_paused' flag is provided.
     * 'reset_heartbeats': the activity heartbeat timer and heartbeats will be reset.
     * 'keep_paused': if the activity is paused, it will remain paused.
     *
     * Returns a `NotFound` error if there is no pending activity with the provided ID or type.
     * @param \Temporal\Api\Workflowservice\V1\ResetActivityRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ResetActivity(\Temporal\Api\Workflowservice\V1\ResetActivityRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/ResetActivity',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\ResetActivityResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Create a new workflow rule. The rules are used to control the workflow execution.
     * The rule will be applied to all running and new workflows in the namespace.
     * If the rule with such ID already exist this call will fail
     * Note: the rules are part of namespace configuration and will be stored in the namespace config.
     * Namespace config is eventually consistent.
     * @param \Temporal\Api\Workflowservice\V1\CreateWorkflowRuleRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function CreateWorkflowRule(\Temporal\Api\Workflowservice\V1\CreateWorkflowRuleRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/CreateWorkflowRule',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\CreateWorkflowRuleResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * DescribeWorkflowRule return the rule specification for existing rule id.
     * If there is no rule with such id - NOT FOUND error will be returned.
     * @param \Temporal\Api\Workflowservice\V1\DescribeWorkflowRuleRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function DescribeWorkflowRule(\Temporal\Api\Workflowservice\V1\DescribeWorkflowRuleRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/DescribeWorkflowRule',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\DescribeWorkflowRuleResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Delete rule by rule id
     * @param \Temporal\Api\Workflowservice\V1\DeleteWorkflowRuleRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function DeleteWorkflowRule(\Temporal\Api\Workflowservice\V1\DeleteWorkflowRuleRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/DeleteWorkflowRule',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\DeleteWorkflowRuleResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Return all namespace workflow rules
     * @param \Temporal\Api\Workflowservice\V1\ListWorkflowRulesRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ListWorkflowRules(\Temporal\Api\Workflowservice\V1\ListWorkflowRulesRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/ListWorkflowRules',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\ListWorkflowRulesResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * TriggerWorkflowRule allows to:
     *  * trigger existing rule for a specific workflow execution;
     *  * trigger rule for a specific workflow execution without creating a rule;
     * This is useful for one-off operations.
     * @param \Temporal\Api\Workflowservice\V1\TriggerWorkflowRuleRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function TriggerWorkflowRule(\Temporal\Api\Workflowservice\V1\TriggerWorkflowRuleRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.workflowservice.v1.WorkflowService/TriggerWorkflowRule',
        $argument,
        ['\Temporal\Api\Workflowservice\V1\TriggerWorkflowRuleResponse', 'decode'],
        $metadata, $options);
    }

}
