<?php

/**
 * This file is part of RoadRunner package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\RoadRunner\Console\Repository;

interface AssetInterface
{
    public function getName(): string;

    public function getUri(): string;

    /**
     * @return iterable<mixed, string>
     */
    public function download(?\Closure $progress = null): iterable;
}
