<?php

declare(strict_types=1);

namespace Yiisoft\Hydrator\AttributeHandling;

use LogicException;
use ReflectionParameter;
use ReflectionProperty;
use Yiisoft\Hydrator\DataInterface;
use Yiisoft\Hydrator\HydratorInterface;
use Yiisoft\Hydrator\Result;

/**
 * Holds attribute resolving context data.
 */
final class ParameterAttributeResolveContext
{
    /**
     * @param ReflectionParameter|ReflectionProperty $parameter Resolved parameter or property reflection.
     * @param Result $resolveResult The resolved value object.
     * @param DataInterface $data Data to be used for resolving.
     * @param ?HydratorInterface Hydrator instance.
     */
    public function __construct(
        private ReflectionParameter|ReflectionProperty $parameter,
        private Result $resolveResult,
        private DataInterface $data,
        private ?HydratorInterface $hydrator = null,
    ) {
    }

    /**
     * Get resolved parameter or property reflection.
     *
     * @return ReflectionParameter|ReflectionProperty Resolved parameter or property reflection.
     */
    public function getParameter(): ReflectionParameter|ReflectionProperty
    {
        return $this->parameter;
    }

    /**
     * Get whether the value for object property is resolved already.
     *
     * @return bool Whether the value for object property is resolved.
     */
    public function isResolved(): bool
    {
        return $this->resolveResult->isResolved();
    }

    /**
     * Get the resolved value.
     *
     * When value is not resolved returns `null`. But `null` can be is resolved value, use {@see isResolved()} for check
     * the value is resolved or not.
     *
     * @return mixed The resolved value.
     */
    public function getResolvedValue(): mixed
    {
        return $this->resolveResult->getValue();
    }

    /**
     * @return DataInterface Data to be used for resolving.
     */
    public function getData(): DataInterface
    {
        return $this->data;
    }

    public function getHydrator(): HydratorInterface
    {
        if ($this->hydrator === null) {
            throw new LogicException('Hydrator is not set in parameter attribute resolve context.');
        }

        return $this->hydrator;
    }
}
