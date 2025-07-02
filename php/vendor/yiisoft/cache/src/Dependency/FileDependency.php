<?php

declare(strict_types=1);

namespace Yiisoft\Cache\Dependency;

use Yiisoft\Cache\CacheInterface;

use function clearstatcache;
use function filemtime;

/**
 * FileDependency represents a dependency based on a file's last modification time.
 *
 * If the last modification time of the file specified via {@see FileDependency::$fileName} is changed,
 * the dependency is considered as changed.
 */
final class FileDependency extends Dependency
{
    /**
     * @param string $fileName The file path whose last modification time is used to
     * check if the dependency has been changed.
     */
    public function __construct(private readonly string $fileName)
    {
    }

    protected function generateDependencyData(CacheInterface $cache): false|int
    {
        clearstatcache(false, $this->fileName);
        return @filemtime($this->fileName);
    }
}
