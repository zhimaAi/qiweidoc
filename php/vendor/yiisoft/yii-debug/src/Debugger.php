<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug;

use LogicException;
use Yiisoft\Yii\Debug\Collector\CollectorInterface;
use Yiisoft\Yii\Debug\Collector\SummaryCollectorInterface;
use Yiisoft\Yii\Debug\StartupPolicy\Collector\AllowAllCollectorPolicy;
use Yiisoft\Yii\Debug\StartupPolicy\Collector\CollectorStartupPolicyInterface;
use Yiisoft\Yii\Debug\StartupPolicy\Debugger\AlwaysOnDebuggerPolicy;
use Yiisoft\Yii\Debug\StartupPolicy\Debugger\DebuggerStartupPolicyInterface;
use Yiisoft\Yii\Debug\Storage\StorageInterface;

/**
 * Debugger collects data from collectors and stores it in a storage.
 *
 * @psalm-type TSummary = array{
 *     id: non-empty-string,
 *     collectors: list<non-empty-string>,
 *     summary: array<non-empty-string, array>,
 * }
 */
final class Debugger
{
    /**
     * @var CollectorInterface[] Collectors, indexed by their names.
     *
     * @psalm-var array<non-empty-string, CollectorInterface>
     */
    private readonly array $collectors;

    /**
     * @var DataNormalizer Data normalizer that prepares data for storage.
     */
    private readonly DataNormalizer $dataNormalizer;

    /**
     * @var non-empty-string|null ID of the current request. `null` if debugger is not active.
     */
    private ?string $id = null;

    /**
     * @var bool Whether debugger startup is allowed.
     */
    private bool $allowStart = true;

    /**
     * @param StorageInterface $storage The storage to store collected data.
     * @param CollectorInterface[] $collectors Collectors to be used.
     * @param DebuggerStartupPolicyInterface $debuggerStartupPolicy Policy to decide whether debugger should be started.
     * Default {@see AlwaysOnDebuggerPolicy} that always allows to startup debugger.
     * @param CollectorStartupPolicyInterface $collectorStartupPolicy Policy to decide whether collector should be
     * started. Default {@see AllowAllCollectorPolicy} that always allows to use all collectors.
     * @param array $excludedClasses List of classes to be excluded from collected data before storing.
     */
    public function __construct(
        private readonly StorageInterface $storage,
        array $collectors,
        private readonly DebuggerStartupPolicyInterface $debuggerStartupPolicy = new AlwaysOnDebuggerPolicy(),
        private readonly CollectorStartupPolicyInterface $collectorStartupPolicy = new AllowAllCollectorPolicy(),
        array $excludedClasses = [],
    ) {
        $preparedCollectors = [];
        foreach ($collectors as $collector) {
            $preparedCollectors[$collector->getName()] = $collector;
        }
        $this->collectors = $preparedCollectors;

        $this->dataNormalizer = new DataNormalizer($excludedClasses);

        register_shutdown_function([$this, 'stop']);
    }

    /**
     * Returns whether debugger is active.
     *
     * @return bool Whether debugger is active.
     */
    public function isActive(): bool
    {
        return $this->id !== null;
    }

    /**
     * Returns ID of the current request.
     *
     * Throws `LogicException` if debugger is not started. Use {@see isActive()} to check if debugger is active.
     *
     * @return string ID of the current request.
     *
     * @psalm-return non-empty-string
     */
    public function getId(): string
    {
        return $this->id ?? throw new LogicException('Debugger is not started.');
    }

    /**
     * Starts debugger and collectors.
     *
     * @param object $event Event that triggered debugger startup.
     */
    public function start(object $event): void
    {
        if (!$this->allowStart) {
            return;
        }

        if (!$this->debuggerStartupPolicy->satisfies($event)) {
            $this->allowStart = false;
            $this->kill();
            return;
        }

        if ($this->isActive()) {
            return;
        }

        /** @var non-empty-string */
        $this->id = str_replace('.', '', uniqid('', true));

        foreach ($this->collectors as $collector) {
            if ($this->collectorStartupPolicy->satisfies($collector, $event)) {
                $collector->startup();
            }
        }
    }

    /**
     * Stops the debugger for listening. Collected data will be flushed to storage.
     */
    public function stop(): void
    {
        if (!$this->isActive()) {
            return;
        }

        try {
            $this->flush();
        } finally {
            $this->deactivate();
        }
    }

    /**
     * Stops the debugger from listening. Collected data will not be flushed to storage.
     */
    public function kill(): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->deactivate();
    }

    /**
     * Collects data from collectors and stores it in a storage.
     */
    private function flush(): void
    {
        $collectedData = array_map(
            static fn (CollectorInterface $collector) => $collector->getCollected(),
            $this->collectors
        );

        /** @var array[] $data */
        [$data, $objectsMap] = $this->dataNormalizer->prepareDataAndObjectsMap($collectedData, 30);

        /** @var array $summary */
        $summary = $this->dataNormalizer->prepareData($this->collectSummaryData(), 30);

        $this->storage->write($this->getId(), $data, $objectsMap, $summary);
    }

    /**
     * Stops debugger and collectors.
     */
    private function deactivate(): void
    {
        foreach ($this->collectors as $collector) {
            $collector->shutdown();
        }
        $this->id = null;
    }

    /**
     * Collects summary data of current request. Structure of the summary data is:
     *
     * - `id` - ID of the current request,
     * - `collectors` - list of collector names used in the current request,
     * - `summary` - summary data collected by collectors indexed by collector names.
     *
     * @psalm-return TSummary
     */
    private function collectSummaryData(): array
    {
        $summaryData = [
            'id' => $this->getId(),
            'collectors' => array_keys($this->collectors),
            'summary' => [],
        ];

        foreach ($this->collectors as $collector) {
            if ($collector instanceof SummaryCollectorInterface) {
                $summaryData['summary'][$collector->getName()] = $collector->getSummary();
            }
        }

        return $summaryData;
    }
}
