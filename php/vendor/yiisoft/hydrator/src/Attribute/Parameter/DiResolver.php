<?php

declare(strict_types=1);

namespace Yiisoft\Hydrator\Attribute\Parameter;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionNamedType;
use ReflectionUnionType;
use Yiisoft\Hydrator\AttributeHandling\ParameterAttributeResolveContext;
use Yiisoft\Hydrator\Result;
use Yiisoft\Hydrator\AttributeHandling\Exception\UnexpectedAttributeException;

/**
 * Resolver for {@see Di} attribute. Obtains dependency from container by ID specified or auto-resolved ID by PHP type.
 */
final class DiResolver implements ParameterAttributeResolverInterface
{
    /**
     * @param ContainerInterface $container Container to obtain dependency from.
     */
    public function __construct(
        private ContainerInterface $container,
    ) {
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws DiNotFoundException When an object is not found in a container or object ID auto-resolving fails.
     */
    public function getParameterValue(ParameterAttributeInterface $attribute, ParameterAttributeResolveContext $context): Result
    {
        if (!$attribute instanceof Di) {
            throw new UnexpectedAttributeException(Di::class, $attribute);
        }

        $parameter = $context->getParameter();

        $id = $attribute->getId();
        if ($id !== null) {
            try {
                return Result::success(
                    $this->container->get($id)
                );
            } catch (NotFoundExceptionInterface $e) {
                throw new DiNotFoundException($parameter, $e);
            }
        }

        $type = $parameter->getType();
        if ($type instanceof ReflectionNamedType) {
            if (!$type->isBuiltin()) {
                try {
                    return Result::success(
                        $this->container->get($type->getName())
                    );
                } catch (NotFoundExceptionInterface $e) {
                    throw new DiNotFoundException($parameter, $e);
                }
            }
        } elseif ($type instanceof ReflectionUnionType) {
            foreach ($type->getTypes() as $type) {
                /** @psalm-suppress RedundantConditionGivenDocblockType Needed for PHP less than 8.2 */
                if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                    try {
                        return Result::success(
                            $this->container->get($type->getName())
                        );
                    } catch (NotFoundExceptionInterface) {
                    }
                }
            }
        }

        throw new DiNotFoundException($parameter);
    }
}
