<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Storage;

use Yiisoft\Files\FileHelper;

use function array_slice;
use function count;
use function dirname;
use function filemtime;
use function glob;
use function json_decode;
use function json_encode;
use function sprintf;
use function strlen;
use function substr;

final class FileStorage implements StorageInterface
{
    private int $historySize = 50;

    public function __construct(
        private readonly string $path,
    ) {
    }

    public function setHistorySize(int $historySize): void
    {
        $this->historySize = $historySize;
    }

    public function read(string $type, ?string $id = null): array
    {
        clearstatcache();

        $dataFiles = $this->findFilesOrderedByModifiedTime(
            sprintf(
                '%s/**/%s/%s.json',
                $this->path,
                $id ?? '**',
                $type,
            )
        );

        $data = [];
        foreach ($dataFiles as $file) {
            $dir = dirname($file);
            $id = substr($dir, strlen(dirname($file, 2)) + 1);
            $content = file_get_contents($file);
            $data[$id] = $content === '' ? '' : json_decode($content, true, flags: JSON_THROW_ON_ERROR);
        }

        return $data;
    }

    public function write(string $id, array $data, array $objectsMap, array $summary): void
    {
        $basePath = $this->path . '/' . date('Y-m-d') . '/' . $id . '/';

        try {
            FileHelper::ensureDirectory($basePath);
            file_put_contents($basePath . self::TYPE_DATA . '.json', $this->encode($data));
            file_put_contents($basePath . self::TYPE_OBJECTS . '.json', $this->encode($objectsMap));
            file_put_contents($basePath . self::TYPE_SUMMARY . '.json', $this->encode($summary));
        } finally {
            $this->gc();
        }
    }

    public function clear(): void
    {
        FileHelper::removeDirectory($this->path);
    }

    /**
     * Removes obsolete data files
     */
    private function gc(): void
    {
        $summaryFiles = $this->findFilesOrderedByModifiedTime($this->path . '/**/**/summary.json');
        if (empty($summaryFiles) || count($summaryFiles) <= $this->historySize) {
            return;
        }

        $excessFiles = array_slice($summaryFiles, $this->historySize);
        foreach ($excessFiles as $file) {
            $path1 = dirname($file);
            $path2 = dirname($file, 2);
            $path3 = dirname($file, 3);
            $resource = substr($path1, strlen($path3));

            FileHelper::removeDirectory($this->path . $resource);

            // Clean empty group directories
            $group = substr($path2, strlen($path3));
            if (FileHelper::isEmptyDirectory($this->path . $group)) {
                FileHelper::removeDirectory($this->path . $group);
            }
        }
    }

    /**
     * @return string[]
     */
    private function findFilesOrderedByModifiedTime(string $pattern): array
    {
        $files = glob($pattern, GLOB_NOSORT);
        if ($files === false) {
            return [];
        }

        usort(
            $files,
            static fn (string $a, string $b) => filemtime($b) <=> filemtime($a)
        );
        return $files;
    }

    private function encode(mixed $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    }
}
