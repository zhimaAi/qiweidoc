<?php

declare(strict_types=1);

namespace Yiisoft\Definitions\Contract;

use Yiisoft\Definitions\Exception\InvalidConfigException;

/**
 * Reference points to another named definition. Usually it is another service in the container or another object
 * definition in the factory.
 */
interface ReferenceInterface extends DefinitionInterface
{
    /**
     * Create an instance of the reference pointing to ID specified.
     *
     * @param mixed $id ID of the service or object to point to.
     *
     * @throws InvalidConfigException When definition configuration is not valid.
     */
    public static function to(mixed $id): self;
}
