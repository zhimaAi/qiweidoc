<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Collector\Stream;

use Yiisoft\Yii\Debug\Collector\CollectorTrait;
use Yiisoft\Yii\Debug\Collector\SummaryCollectorInterface;

use function count;

final class FilesystemStreamCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    public function __construct(
        /**
         * Collection of regexps to ignore files sources to sniff.
         * Examples:
         * - '/' . preg_quote('yii-debug/src/Dumper', '/') . '/'
         * - '/ClosureExporter/'
         *
         * @var string[]
         */
        private readonly array $ignoredPathPatterns = [],
        /**
         * @var string[]
         */
        private readonly array $ignoredClasses = [],
    ) {
    }

    /**
     * @psalm-var array<string, list<array{path: string, args: array}>>
     */
    private array $operations = [];

    public function getCollected(): array
    {
        return $this->isActive() ? $this->operations : [];
    }

    public function startup(): void
    {
        $this->isActive = true;
        FilesystemStreamProxy::register();
        FilesystemStreamProxy::$collector = $this;
        FilesystemStreamProxy::$ignoredPathPatterns = $this->ignoredPathPatterns;
        FilesystemStreamProxy::$ignoredClasses = $this->ignoredClasses;
    }

    public function shutdown(): void
    {
        FilesystemStreamProxy::unregister();
        FilesystemStreamProxy::$collector = null;
        FilesystemStreamProxy::$ignoredPathPatterns = [];
        FilesystemStreamProxy::$ignoredClasses = [];

        $this->reset();
        $this->isActive = false;
    }

    public function collect(string $operation, string $path, array $args): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->operations[$operation][] = [
            'path' => $path,
            'args' => $args,
        ];
    }

    public function getSummary(): array
    {
        if (!$this->isActive()) {
            return [];
        }
        return [
            'streams' => array_merge(
                ...array_map(
                    fn (string $operation) => [$operation => count($this->operations[$operation])],
                    array_keys($this->operations)
                )
            ),
        ];
    }

    private function reset(): void
    {
        $this->operations = [];
    }
}
