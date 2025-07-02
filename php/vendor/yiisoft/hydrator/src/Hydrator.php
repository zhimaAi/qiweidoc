<?php

declare(strict_types=1);

namespace Yiisoft\Hydrator;

use ReflectionClass;
use ReflectionProperty;
use Yiisoft\Hydrator\AttributeHandling\ResolverFactory\AttributeResolverFactoryInterface;
use Yiisoft\Hydrator\AttributeHandling\DataAttributesHandler;
use Yiisoft\Hydrator\AttributeHandling\ParameterAttributesHandler;
use Yiisoft\Hydrator\Exception\NonExistClassException;
use Yiisoft\Hydrator\Internal\ConstructorArgumentsExtractor;
use Yiisoft\Hydrator\Internal\ReflectionFilter;
use Yiisoft\Hydrator\ObjectFactory\ObjectFactoryInterface;
use Yiisoft\Hydrator\ObjectFactory\ReflectionObjectFactory;
use Yiisoft\Hydrator\AttributeHandling\ResolverFactory\ReflectionAttributeResolverFactory;
use Yiisoft\Hydrator\TypeCaster\CompositeTypeCaster;
use Yiisoft\Hydrator\TypeCaster\HydratorTypeCaster;
use Yiisoft\Hydrator\TypeCaster\PhpNativeTypeCaster;
use Yiisoft\Hydrator\TypeCaster\TypeCastContext;
use Yiisoft\Hydrator\TypeCaster\TypeCasterInterface;

use function is_array;

/**
 * Creates or hydrate objects from a set of raw data.
 */
final class Hydrator implements HydratorInterface
{
    /**
     * @var TypeCasterInterface Type caster used to cast raw values.
     */
    private TypeCasterInterface $typeCaster;

    private ObjectFactoryInterface $objectFactory;

    private DataAttributesHandler $dataAttributesHandler;

    private ParameterAttributesHandler $parameterAttributesHandler;

    private ConstructorArgumentsExtractor $constructorArgumentsExtractor;

    /**
     * @param TypeCasterInterface|null $typeCaster Type caster used to cast raw values.
     */
    public function __construct(
        ?TypeCasterInterface $typeCaster = null,
        ?AttributeResolverFactoryInterface $attributeResolverFactory = null,
        ?ObjectFactoryInterface $objectFactory = null,
    ) {
        $this->typeCaster = $typeCaster ?? new CompositeTypeCaster(
            new PhpNativeTypeCaster(),
            new HydratorTypeCaster(),
        );

        $attributeResolverFactory ??= new ReflectionAttributeResolverFactory();
        $this->dataAttributesHandler = new DataAttributesHandler($attributeResolverFactory);
        $this->parameterAttributesHandler = new ParameterAttributesHandler($attributeResolverFactory, $this);

        $this->objectFactory = $objectFactory ?? new ReflectionObjectFactory();

        $this->constructorArgumentsExtractor = new ConstructorArgumentsExtractor(
            $this,
            $this->parameterAttributesHandler,
            $this->typeCaster,
        );
    }

    public function hydrate(object $object, array|DataInterface $data = []): void
    {
        if (is_array($data)) {
            $data = new ArrayData($data);
        }

        $reflectionClass = new ReflectionClass($object);

        $data = $this->dataAttributesHandler->handle($reflectionClass, $data);

        $this->hydrateInternal(
            $object,
            $reflectionClass,
            ReflectionFilter::filterProperties($object, $reflectionClass),
            $data
        );
    }

    public function create(string $class, array|DataInterface $data = []): object
    {
        if (!class_exists($class)) {
            throw new NonExistClassException($class);
        }

        if (is_array($data)) {
            $data = new ArrayData($data);
        }

        $reflectionClass = new ReflectionClass($class);
        $constructor = $reflectionClass->getConstructor();

        $data = $this->dataAttributesHandler->handle($reflectionClass, $data);

        [$excludeProperties, $constructorArguments] = $this->constructorArgumentsExtractor->extract(
            $constructor,
            $data,
        );

        $object = $this->objectFactory->create($reflectionClass, $constructorArguments);

        $this->hydrateInternal(
            $object,
            $reflectionClass,
            ReflectionFilter::filterProperties($object, $reflectionClass, $excludeProperties),
            $data
        );

        return $object;
    }

    /**
     * @param array<string, ReflectionProperty> $reflectionProperties
     */
    private function hydrateInternal(
        object $object,
        ReflectionClass $reflectionClass,
        array $reflectionProperties,
        DataInterface $data,
    ): void {
        foreach ($reflectionProperties as $propertyName => $property) {
            $resolveResult = $data->getValue($propertyName);

            $attributesHandleResult = $this->parameterAttributesHandler->handle(
                $property,
                $resolveResult,
                $data,
            );
            if ($attributesHandleResult->isResolved()) {
                $resolveResult = $attributesHandleResult;
            }

            if ($resolveResult->isResolved()) {
                $result = $this->typeCaster->cast(
                    $resolveResult->getValue(),
                    new TypeCastContext($this, $property),
                );
                if ($result->isResolved()) {
                    $this
                        ->preparePropertyToSetValue($reflectionClass, $property, $propertyName)
                        ->setValue($object, $result->getValue());
                }
            }
        }
    }

    private function preparePropertyToSetValue(
        ReflectionClass $class,
        ReflectionProperty $property,
        string $propertyName,
    ): ReflectionProperty {
        if ($property->isReadOnly()) {
            $declaringClass = $property->getDeclaringClass();
            if ($declaringClass !== $class) {
                return $declaringClass->getProperty($propertyName);
            }
        }
        return $property;
    }
}
