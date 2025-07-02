<?php

/**
 * This file is part of RoadRunner package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\RoadRunner\Console\Repository;

abstract class Asset implements AssetInterface
{
    protected string $name;
    protected string $uri;

    public function __construct(string $name, string $uri)
    {
        $this->name = $name;
        $this->uri = $uri;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getUri(): string
    {
        return $this->uri;
    }
}
