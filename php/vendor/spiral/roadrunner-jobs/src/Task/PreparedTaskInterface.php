<?php

declare(strict_types=1);

namespace Spiral\RoadRunner\Jobs\Task;

use Spiral\RoadRunner\Jobs\OptionsInterface;

interface PreparedTaskInterface extends
    TaskInterface,
    OptionsInterface,
    WritableHeadersInterface,
    MutatesDelayInterface
{
}
