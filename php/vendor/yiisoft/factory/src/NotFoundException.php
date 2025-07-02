<?php

declare(strict_types=1);

namespace Yiisoft\Factory;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

/**
 * NotFoundException is thrown when no definition or class was found in the factory for a given ID.
 */
final class NotFoundException extends Exception implements NotFoundExceptionInterface
{
    /**
     * @param string $id ID of the definition or name of the class that was not found.
     */
    public function __construct(
        private string $id
    ) {
        parent::__construct(sprintf('No definition or class found or resolvable for %s.', $id));
    }

    public function getId(): string
    {
        return $this->id;
    }
}
