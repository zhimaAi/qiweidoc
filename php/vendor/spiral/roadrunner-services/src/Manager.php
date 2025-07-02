<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Services;

use Google\Protobuf\Any;
use RoadRunner\Service\DTO\V1\Create;
use RoadRunner\Service\DTO\V1\PBList;
use RoadRunner\Service\DTO\V1\Response;
use RoadRunner\Service\DTO\V1\Service;
use RoadRunner\Service\DTO\V1\Status;
use RoadRunner\Service\DTO\V1\Statuses;
use Spiral\Goridge\RPC\Codec\ProtobufCodec;
use Spiral\Goridge\RPC\Exception\ServiceException;
use Spiral\Goridge\RPC\RPCInterface;

final class Manager
{
    private readonly RPCInterface $rpc;

    public function __construct(RPCInterface $rpc)
    {
        $this->rpc = $rpc->withCodec(new ProtobufCodec());
    }

    /**
     * Get list of all services.
     *
     * @return string[] Services name.
     * @throws Exception\ServiceException
     */
    public function list(): array
    {
        $services = [];

        try {
            /** @var PBList $response */
            $response = $this->rpc->call('service.List', new Service(), PBList::class);

            /** @var string $service */
            foreach ($response->getServices() as $service) {
                $services[] = $service;
            }
        } catch (ServiceException $e) {
            $this->handleError($e);
        }

        return $services;
    }

    /**
     * Create a new service.
     *
     * @param non-empty-string $name Service name.
     * @param non-empty-string $command Command to execute. There are no limitations on commands here. Here could be
     *     binary, PHP file, script, etc.
     * @param int<1, max> $processNum Number of processes for the command to fire.
     * @param int<0, max> $execTimeout Maximum allowed time to run for the process.
     * @param bool $remainAfterExit Remain process after exit.
     * @param array<non-empty-string, string> $env Environment variables to pass to the underlying process from the
     *     config.
     * @param int<1, max> $restartSec Delay between process stop and restart.
     * @param bool $serviceNameInLogs Show the name of the service in logs (e.g. service.some_service_1).
     * @param int<0, max> $stopTimeout Timeout for the process stop operation.
     * @throws Exception\ServiceException
     * @see https://docs.roadrunner.dev/plugins/service
     */
    public function create(
        string $name,
        string $command,
        int $processNum = 1,
        int $execTimeout = 0,
        bool $remainAfterExit = false,
        array $env = [],
        int $restartSec = 30,
        bool $serviceNameInLogs = false,
        int $stopTimeout = 5
    ): bool {
        \assert($processNum > 0, 'Process number must be greater than 0.');
        \assert($execTimeout >= 0, 'Execution timeout must be greater or equal to 0.');
        \assert($restartSec > 0, 'Restart delay must be greater than 0.');
        \assert($stopTimeout >= 0, 'Timeout for the process stop operation must be greater or equal to 0.');

        $create = (new Create())
            ->setName($name)
            ->setCommand($command)
            ->setProcessNum($processNum)
            ->setExecTimeout($execTimeout)
            ->setRemainAfterExit($remainAfterExit)
            ->setEnv($env)
            ->setRestartSec($restartSec)
            ->setServiceNameInLogs($serviceNameInLogs)
            ->setTimeoutStopSec($stopTimeout);

        try {
            /** @var Response $response */
            $response = $this->rpc->call('service.Create', $create, Response::class);

            return $response->getOk();
        } catch (ServiceException $e) {
            $this->handleError($e);
        }

        return false;
    }

    /**
     * Remove a service.
     *
     * @param non-empty-string $name
     * @throws Exception\ServiceException
     */
    public function restart(string $name): bool
    {
        try {
            /** @var Response $response */
            $response = $this->rpc->call('service.Restart', new Service(['name' => $name]), Response::class);

            return $response->getOk();
        } catch (ServiceException $e) {
            $this->handleError($e);
        }

        return false;
    }

    /**
     * Terminate service.
     *
     * @param non-empty-string $name Service name.
     * @throws Exception\ServiceException
     */
    public function terminate(string $name): bool
    {
        try {
            /** @var Response $response */
            $response = $this->rpc->call('service.Terminate', new Service(['name' => $name]), Response::class);

            return $response->getOk();
        } catch (ServiceException $e) {
            $this->handleError($e);
        }

        return false;
    }

    /**
     * Get service statuses.
     *
     * @param non-empty-string $name Service name.
     * @return list<array{
     *     command: non-empty-string,
     *     cpu_percent: float,
     *     memory_usage: positive-int,
     *     pid: positive-int,
     *     error?: array{
     *        code: int,
     *        message: non-empty-string,
     *        details: array{message: string, type_url: string}[]
     *    }
     * }>
     * @throws Exception\ServiceException
     * @psalm-suppress MoreSpecificReturnType
     * @psalm-suppress LessSpecificReturnStatement
     */
    public function statuses(string $name): array
    {
        $result = [];
        try {
            $response = $this->rpc->call('service.Statuses', new Service(['name' => $name]), Statuses::class);
            \assert($response instanceof Statuses);

            foreach ($response->getStatus() as $status) {
                \assert($status instanceof Status);

                $error = null;
                $statusError = $status->getStatus();
                /** @psalm-suppress RedundantConditionGivenDocblockType */
                if ($statusError !== null) {
                    $error = [
                        'code' => $statusError->getCode(),
                        'message' => $statusError->getMessage(),
                        'details' => \array_map(static fn (Any $any) => [
                            'message' => $any->getValue(),
                            'type_url' => $any->getTypeUrl(),
                        ], \iterator_to_array($statusError->getDetails()->getIterator())),
                    ];
                }

                $result[] = [
                    'cpu_percent' => $status->getCpuPercent(),
                    'pid' => $status->getPid(),
                    'memory_usage' => $status->getMemoryUsage(),
                    'command' => $status->getCommand(),
                    'error' => $error,
                ];
            }
        } catch (ServiceException $e) {
            $this->handleError($e);
        }

        return $result;
    }

    /**
     * @throws Exception\ServiceException
     */
    private function handleError(ServiceException $e): never
    {
        $message = \str_replace(["\t", "\n"], ' ', $e->getMessage());

        throw new Exception\ServiceException($message, $e->getCode(), $e);
    }
}
