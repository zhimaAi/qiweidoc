<?php

declare(strict_types=1);

namespace Yiisoft\Hydrator\AttributeHandling;

use LogicException;
use ReflectionAttribute;
use ReflectionParameter;
use ReflectionProperty;
use RuntimeException;
use Yiisoft\Hydrator\AttributeHandling\ResolverFactory\AttributeResolverFactoryInterface;
use Yiisoft\Hydrator\Attribute\Parameter\ParameterAttributeInterface;
use Yiisoft\Hydrator\Attribute\Parameter\ParameterAttributeResolverInterface;
use Yiisoft\Hydrator\ArrayData;
use Yiisoft\Hydrator\DataInterface;
use Yiisoft\Hydrator\HydratorInterface;
use Yiisoft\Hydrator\Result;

/**
 * Handles parameters' attributes that implement {@see ParameterAttributeInterface}.
 */
final class ParameterAttributesHandler
{
    public function __construct(
        private AttributeResolverFactoryInterface $attributeResolverFactory,
        private ?HydratorInterface $hydrator = null,
    ) {
    }

    /**
     * Handle parameters' attributes of passed parameter.
     *
     * @param ReflectionParameter|ReflectionProperty $parameter Parameter or property reflection to handle attributes
     * from.
     * @param Result|null $resolveResult The resolved value object to pass to attribute resolver via {@see ParameterAttributeResolveContext}.
     * @param DataInterface|null $data Raw data and map to pass to attribute resolver via {@see ParameterAttributeResolveContext}.
     *
     * @return Result The resolved from attributes' value object.
     */
    public function handle(
        ReflectionParameter|ReflectionProperty $parameter,
        ?Result $resolveResult = null,
        ?DataInterface $data = null
    ): Result {
        if ($this->hydrator === null) {
            throw new LogicException('Hydrator is not set in parameter attributes handler.');
        }

        $resolveResult ??= Result::fail();
        $data ??= new ArrayData();

        $reflectionAttributes = $parameter
            ->getAttributes(ParameterAttributeInterface::class, ReflectionAttribute::IS_INSTANCEOF);

        foreach ($reflectionAttributes as $reflectionAttribute) {
            $attribute = $reflectionAttribute->newInstance();

            $resolver = $this->attributeResolverFactory->create($attribute);
            if (!$resolver instanceof ParameterAttributeResolverInterface) {
                throw new RuntimeException(
                    sprintf(
                        'Parameter attribute resolver "%s" must implement "%s".',
                        get_debug_type($resolver),
                        ParameterAttributeResolverInterface::class,
                    ),
                );
            }

            $context = new ParameterAttributeResolveContext($parameter, $resolveResult, $data, $this->hydrator);

            $tryResolveResult = $resolver->getParameterValue($attribute, $context);
            if ($tryResolveResult->isResolved()) {
                $resolveResult = $tryResolveResult;
            }
        }

        return $resolveResult;
    }

    public function withHydrator(HydratorInterface $hydrator): self
    {
        $new = clone $this;
        $new->hydrator = $hydrator;
        return $new;
    }
}
