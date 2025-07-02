<?php

declare(strict_types=1);

namespace Yiisoft\Config;

use ErrorException;
use Yiisoft\Arrays\ArrayHelper;

use function array_key_exists;
use function array_map;
use function array_merge;
use function implode;
use function is_array;
use function is_int;
use function sprintf;
use function substr_count;
use function usort;

/**
 * @internal
 */
final class Merger
{
    /**
     * @psalm-var array<int, array>
     */
    private array $cacheKeys = [];

    /**
     * @param ConfigPaths $configPaths The config paths instance.
     * @param DataModifiers $dataModifiers The data modifiers that affect merge process.
     */
    public function __construct(
        private readonly ConfigPaths $configPaths,
        private readonly DataModifiers $dataModifiers,
    ) {
    }

    public function reset(): void
    {
        $this->cacheKeys = [];
    }

    /**
     * Merges two or more arrays into one recursively.
     *
     * @param Context $context Context containing the name of the file, package, group and environment.
     * @param array $arrayA First array to merge.
     * @param array $arrayB Second array to merge.
     *
     * @throws ErrorException If an error occurred during the merge.
     *
     * @return array The merged array.
     */
    public function merge(Context $context, array $arrayA, array $arrayB): array
    {
        $recursionDepth = $this->dataModifiers->getRecursionDepth($context->originalGroup());
        $isReverseMerge = $this->dataModifiers->isReverseMergeGroup($context->originalGroup());

        if ($isReverseMerge) {
            $arrayB = $this->prepareArrayForReverse($context, [], $arrayB, $recursionDepth !== false);
        }

        return $this->performMerge(
            $context,
            [],
            $isReverseMerge ? $arrayB : $arrayA,
            $isReverseMerge ? $arrayA : $arrayB,
            $recursionDepth,
            $isReverseMerge,
        );
    }

    /**
     * @param Context $context Context containing the name of the file, package, group and environment.
     * @param string[] $recursiveKeyPath The key path for recursive merging of arrays in configuration files.
     * @param array $arrayA First array to merge.
     * @param array $arrayB Second array to merge.
     *
     * @throws ErrorException If an error occurred during the merge.
     *
     * @return array The merged array.
     */
    private function performMerge(
        Context $context,
        array $recursiveKeyPath,
        array $arrayA,
        array $arrayB,
        int|null|false $recursionDepth,
        bool $isReverseMerge,
        int $depth = 0,
    ): array {
        $result = $arrayA;
        foreach ($arrayB as $k => $v) {
            if (is_int($k)) {
                if (array_key_exists($k, $result) && $result[$k] !== $v) {
                    $result[] = $v;
                } else {
                    $result[$k] = $v;
                }
                continue;
            }

            $fullKeyPath = array_merge($recursiveKeyPath, [$k]);

            if (
                $recursionDepth !== false
                && is_array($v)
                && ($recursionDepth === null || $depth < $recursionDepth)
                && (
                    !array_key_exists($k, $result)
                    || is_array($result[$k])
                )
            ) {
                /** @var array $array */
                $array = $result[$k] ?? [];
                $this->setValue(
                    $context,
                    $fullKeyPath,
                    $result,
                    $k,
                    $this->performMerge(
                        $context,
                        $fullKeyPath,
                        $array,
                        $v,
                        $recursionDepth,
                        $isReverseMerge,
                        $depth + 1,
                    )
                );
                continue;
            }

            $existKey = array_key_exists($k, $result);

            if ($existKey && !$isReverseMerge) {
                /** @var string|null $file */
                $file = ArrayHelper::getValue(
                    $this->cacheKeys,
                    array_merge([$context->layer()], $fullKeyPath)
                );

                if ($file !== null) {
                    $this->throwDuplicateKeyErrorException($context->originalGroup(), $fullKeyPath, [$file, $context->file()]);
                }
            }

            if (!$isReverseMerge || !$existKey) {
                $isSet = $this->setValue($context, $fullKeyPath, $result, $k, $v);

                if ($isSet && !$isReverseMerge && !$context->isVariable()) {
                    /** @psalm-suppress MixedPropertyTypeCoercion */
                    ArrayHelper::setValue(
                        $this->cacheKeys,
                        array_merge([$context->layer()], $fullKeyPath),
                        $context->file()
                    );
                }
            }
        }

        return $result;
    }

    /**
     * @param string[] $recursiveKeyPath
     *
     * @throws ErrorException If an error occurred during prepare.
     */
    private function prepareArrayForReverse(
        Context $context,
        array $recursiveKeyPath,
        array $array,
        bool $isRecursiveMerge
    ): array {
        $result = [];

        foreach ($array as $key => $value) {
            if (is_int($key)) {
                $result[$key] = $value;
                continue;
            }

            if ($this->dataModifiers->shouldRemoveKeyFromVendor($context, array_merge($recursiveKeyPath, [$key]))) {
                continue;
            }

            if ($isRecursiveMerge && is_array($value)) {
                $result[$key] = $this->prepareArrayForReverse(
                    $context,
                    array_merge($recursiveKeyPath, [$key]),
                    $value,
                    true,
                );
                continue;
            }

            $recursiveKeyPath[] = $key;

            /** @var string|null $file */
            $file = ArrayHelper::getValue(
                $this->cacheKeys,
                array_merge([$context->layer()], $recursiveKeyPath)
            );

            if ($file !== null) {
                $this->throwDuplicateKeyErrorException($context->originalGroup(), $recursiveKeyPath, [$file, $context->file()]);
            }

            $result[$key] = $value;

            /** @psalm-suppress MixedPropertyTypeCoercion */
            ArrayHelper::setValue(
                $this->cacheKeys,
                array_merge([$context->layer()], $recursiveKeyPath),
                $context->file()
            );
        }

        return $result;
    }

    /**
     * @psalm-param non-empty-array<array-key, string> $keyPath
     */
    private function setValue(Context $context, array $keyPath, array &$array, string $key, mixed $value): bool
    {
        if ($this->dataModifiers->shouldRemoveKeyFromVendor($context, $keyPath)) {
            return false;
        }

        /** @var mixed */
        $array[$key] = $value;

        return true;
    }

    /**
     * Generates a duplicate key error message and throws an exception.
     *
     * @param string $currentGroupName The name of the group that the error occurred when merging.
     * @param string[] $recursiveKeyPath The key path for recursive merging of arrays in configuration files.
     * @param string[] $absoluteFilePaths The absolute paths to the files in which duplicates are found.
     *
     * @throws ErrorException With a duplicate key error message.
     */
    private function throwDuplicateKeyErrorException(
        string $currentGroupName,
        array $recursiveKeyPath,
        array $absoluteFilePaths
    ): void {
        $filePaths = array_map(
            fn (string $filePath) => ' - ' . $this->configPaths->relative($filePath),
            $absoluteFilePaths,
        );

        usort($filePaths, static function (string $a, string $b) {
            $countDirsA = substr_count($a, '/');
            $countDirsB = substr_count($b, '/');
            return $countDirsA === $countDirsB ? $a <=> $b : $countDirsA <=> $countDirsB;
        });

        $message = sprintf(
            "Duplicate key \"%s\" in the following configs while building \"%s\" group:\n%s",
            implode(' => ', $recursiveKeyPath),
            $currentGroupName,
            implode("\n", $filePaths),
        );

        throw new ErrorException($message, 0, E_USER_ERROR);
    }
}
