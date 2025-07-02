<?php

declare(strict_types=1);

namespace Yiisoft\Profiler;

interface ProfilerAwareInterface
{
    /**
     * Sets the profiler instance.
     *
     * @param ProfilerInterface $profiler The profiler instance.
     */
    public function setProfiler(ProfilerInterface $profiler): void;
}
