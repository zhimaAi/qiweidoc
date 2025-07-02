<?php

/**
 * This file is part of RoadRunner package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\RoadRunner\Console\Repository;

use JetBrains\PhpStorm\ExpectedValues;
use Spiral\RoadRunner\Console\Environment\Stability;

/**
 * @psalm-import-type StabilityType from Stability
 */
interface ReleaseInterface
{
    /**
     * Returns Composer's compatible "pretty" release version.
     *
     */
    public function getName(): string;

    /**
     * Returns internal release tag version.
     * Please note that this version may not be compatible with Composer's
     * comparators.
     *
     */
    public function getVersion(): string;

    public function getRepositoryName(): string;

    /**
     * @return StabilityType
     */
    #[ExpectedValues(valuesFromClass: Stability::class)]
    public function getStability(): string;

    /**
     * @return AssetsCollection|iterable<AssetInterface>
     */
    public function getAssets(): AssetsCollection;

    public function satisfies(string $constraint): bool;

    public function getConfig(): string;
}
