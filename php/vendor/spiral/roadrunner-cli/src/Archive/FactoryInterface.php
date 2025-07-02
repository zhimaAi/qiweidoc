<?php

/**
 * This file is part of RoadRunner package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\RoadRunner\Console\Archive;

use Spiral\RoadRunner\Console\Repository\AssetInterface;

/**
 * @psalm-type ArchiveMatcher = \Closure(\SplFileInfo): ?ArchiveInterface
 */
interface FactoryInterface
{
    /**
     * @param ArchiveMatcher $matcher
     * @return $this
     */
    public function extend(\Closure $matcher): self;

    public function create(\SplFileInfo $file): ArchiveInterface;

    public function fromAsset(AssetInterface $asset, ?\Closure $progress = null, ?string $temp = null): ArchiveInterface;
}
