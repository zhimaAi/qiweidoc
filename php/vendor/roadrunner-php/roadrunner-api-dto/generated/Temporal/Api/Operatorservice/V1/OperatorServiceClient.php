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
namespace Temporal\Api\Operatorservice\V1;

/**
 * OperatorService API defines how Temporal SDKs and other clients interact with the Temporal server
 * to perform administrative functions like registering a search attribute or a namespace.
 * APIs in this file could be not compatible with Temporal Cloud, hence it's usage in SDKs should be limited by
 * designated APIs that clearly state that they shouldn't be used by the main Application (Workflows & Activities) framework.
 */
class OperatorServiceClient extends \Grpc\BaseStub {

    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * AddSearchAttributes add custom search attributes.
     *
     * Returns ALREADY_EXISTS status code if a Search Attribute with any of the specified names already exists
     * Returns INTERNAL status code with temporal.api.errordetails.v1.SystemWorkflowFailure in Error Details if registration process fails,
     * @param \Temporal\Api\Operatorservice\V1\AddSearchAttributesRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function AddSearchAttributes(\Temporal\Api\Operatorservice\V1\AddSearchAttributesRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.operatorservice.v1.OperatorService/AddSearchAttributes',
        $argument,
        ['\Temporal\Api\Operatorservice\V1\AddSearchAttributesResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * RemoveSearchAttributes removes custom search attributes.
     *
     * Returns NOT_FOUND status code if a Search Attribute with any of the specified names is not registered
     * @param \Temporal\Api\Operatorservice\V1\RemoveSearchAttributesRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function RemoveSearchAttributes(\Temporal\Api\Operatorservice\V1\RemoveSearchAttributesRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.operatorservice.v1.OperatorService/RemoveSearchAttributes',
        $argument,
        ['\Temporal\Api\Operatorservice\V1\RemoveSearchAttributesResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * ListSearchAttributes returns comprehensive information about search attributes.
     * @param \Temporal\Api\Operatorservice\V1\ListSearchAttributesRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ListSearchAttributes(\Temporal\Api\Operatorservice\V1\ListSearchAttributesRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.operatorservice.v1.OperatorService/ListSearchAttributes',
        $argument,
        ['\Temporal\Api\Operatorservice\V1\ListSearchAttributesResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * DeleteNamespace synchronously deletes a namespace and asynchronously reclaims all namespace resources.
     * @param \Temporal\Api\Operatorservice\V1\DeleteNamespaceRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function DeleteNamespace(\Temporal\Api\Operatorservice\V1\DeleteNamespaceRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.operatorservice.v1.OperatorService/DeleteNamespace',
        $argument,
        ['\Temporal\Api\Operatorservice\V1\DeleteNamespaceResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * AddOrUpdateRemoteCluster adds or updates remote cluster.
     * @param \Temporal\Api\Operatorservice\V1\AddOrUpdateRemoteClusterRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function AddOrUpdateRemoteCluster(\Temporal\Api\Operatorservice\V1\AddOrUpdateRemoteClusterRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.operatorservice.v1.OperatorService/AddOrUpdateRemoteCluster',
        $argument,
        ['\Temporal\Api\Operatorservice\V1\AddOrUpdateRemoteClusterResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * RemoveRemoteCluster removes remote cluster.
     * @param \Temporal\Api\Operatorservice\V1\RemoveRemoteClusterRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function RemoveRemoteCluster(\Temporal\Api\Operatorservice\V1\RemoveRemoteClusterRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.operatorservice.v1.OperatorService/RemoveRemoteCluster',
        $argument,
        ['\Temporal\Api\Operatorservice\V1\RemoveRemoteClusterResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * ListClusters returns information about Temporal clusters.
     * @param \Temporal\Api\Operatorservice\V1\ListClustersRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ListClusters(\Temporal\Api\Operatorservice\V1\ListClustersRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.operatorservice.v1.OperatorService/ListClusters',
        $argument,
        ['\Temporal\Api\Operatorservice\V1\ListClustersResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Get a registered Nexus endpoint by ID. The returned version can be used for optimistic updates.
     * @param \Temporal\Api\Operatorservice\V1\GetNexusEndpointRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function GetNexusEndpoint(\Temporal\Api\Operatorservice\V1\GetNexusEndpointRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.operatorservice.v1.OperatorService/GetNexusEndpoint',
        $argument,
        ['\Temporal\Api\Operatorservice\V1\GetNexusEndpointResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Create a Nexus endpoint. This will fail if an endpoint with the same name is already registered with a status of
     * ALREADY_EXISTS.
     * Returns the created endpoint with its initial version. You may use this version for subsequent updates.
     * @param \Temporal\Api\Operatorservice\V1\CreateNexusEndpointRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function CreateNexusEndpoint(\Temporal\Api\Operatorservice\V1\CreateNexusEndpointRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.operatorservice.v1.OperatorService/CreateNexusEndpoint',
        $argument,
        ['\Temporal\Api\Operatorservice\V1\CreateNexusEndpointResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Optimistically update a Nexus endpoint based on provided version as obtained via the `GetNexusEndpoint` or
     * `ListNexusEndpointResponse` APIs. This will fail with a status of FAILED_PRECONDITION if the version does not
     * match.
     * Returns the updated endpoint with its updated version. You may use this version for subsequent updates. You don't
     * need to increment the version yourself. The server will increment the version for you after each update.
     * @param \Temporal\Api\Operatorservice\V1\UpdateNexusEndpointRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function UpdateNexusEndpoint(\Temporal\Api\Operatorservice\V1\UpdateNexusEndpointRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.operatorservice.v1.OperatorService/UpdateNexusEndpoint',
        $argument,
        ['\Temporal\Api\Operatorservice\V1\UpdateNexusEndpointResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Delete an incoming Nexus service by ID.
     * @param \Temporal\Api\Operatorservice\V1\DeleteNexusEndpointRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function DeleteNexusEndpoint(\Temporal\Api\Operatorservice\V1\DeleteNexusEndpointRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.operatorservice.v1.OperatorService/DeleteNexusEndpoint',
        $argument,
        ['\Temporal\Api\Operatorservice\V1\DeleteNexusEndpointResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * List all Nexus endpoints for the cluster, sorted by ID in ascending order. Set page_token in the request to the
     * next_page_token field of the previous response to get the next page of results. An empty next_page_token
     * indicates that there are no more results. During pagination, a newly added service with an ID lexicographically
     * earlier than the previous page's last endpoint's ID may be missed.
     * @param \Temporal\Api\Operatorservice\V1\ListNexusEndpointsRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ListNexusEndpoints(\Temporal\Api\Operatorservice\V1\ListNexusEndpointsRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/temporal.api.operatorservice.v1.OperatorService/ListNexusEndpoints',
        $argument,
        ['\Temporal\Api\Operatorservice\V1\ListNexusEndpointsResponse', 'decode'],
        $metadata, $options);
    }

}
