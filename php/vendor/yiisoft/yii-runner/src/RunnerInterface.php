<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Runner;

/**
 * Runs an application hiding initialization details.
 */
interface RunnerInterface
{
    /**
     * Runs an application.
     */
    public function run(): void;
}
