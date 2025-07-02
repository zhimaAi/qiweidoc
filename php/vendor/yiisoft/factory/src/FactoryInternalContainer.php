<?php

declare(strict_types=1);

namespace Yiisoft\Factory;

use LogicException;
use Psr\Container\ContainerInterface;
use Yiisoft\Definitions\ArrayDefinition;
use Yiisoft\Definitions\Contract\DefinitionInterface;
use Yiisoft\Definitions\Contract\ReferenceInterface;
use Yiisoft\Definitions\Exception\CircularReferenceException;
use Yiisoft\Definitions\Exception\InvalidConfigException;
use Yiisoft\Definitions\Exception\NotInstantiableClassException;
use Yiisoft\Definitions\Exception\NotInstantiableException;
use Yiisoft\Definitions\Helpers\Normalizer;

use function array_key_exists;
use function is_object;
use function is_string;

/**
 * Factory's primary container.
 *
 * @internal
 */
final class FactoryInternalContainer implements ContainerInterface
{
    /**
     * @var array<string, DefinitionInterface> Object created from definitions indexed by their types.
     */
    private array $definitionInstances = [];

    /**
     * @var array<string,1> Used to collect IDs instantiated during build to detect circular references.
     */
    private array $creatingIds = [];

    /**
     * @param ContainerInterface|null $container Container to use for resolving dependencies.
     * @param array<string, mixed> $definitions Definitions to create objects with.
     */
    public function __construct(
        private ?ContainerInterface $container,
        private array $definitions
    ) {
    }

    /**
     * @param array<string, mixed> $definitions Definitions to create objects with.
     */
    public function withDefinitions(array $definitions): self
    {
        $new = clone $this;
        $new->definitions = $definitions;
        $new->definitionInstances = [];
        $new->creatingIds = [];
        return $new;
    }

    /**
     * @inheritDoc
     *
     * @param string $id
     */
    public function get($id): mixed
    {
        if ($this->hasDefinition($id)) {
            return $this->build($id);
        }

        if ($this->container?->has($id)) {
            return $this->container->get($id);
        }

        throw new NotInstantiableClassException($id);
    }

    public function has($id): bool
    {
        return $this->hasDefinition($id) || $this->container?->has($id);
    }

    public function create(DefinitionInterface $definition): mixed
    {
        if ($definition instanceof ArrayDefinition) {
            $this->creatingIds[$definition->getClass()] = 1;
        }

        try {
            $result = $definition->resolve($this);
            return is_object($result) ? clone $result : $result;
        } finally {
            if ($definition instanceof ArrayDefinition) {
                unset($this->creatingIds[$definition->getClass()]);
            }
        }
    }

    /**
     * Get definition by identifier provided.
     *
     * @throws InvalidConfigException
     */
    public function getDefinition(string $id): DefinitionInterface
    {
        if (!isset($this->definitionInstances[$id])) {
            if (isset($this->definitions[$id])) {
                if (is_object($this->definitions[$id]) && !($this->definitions[$id] instanceof ReferenceInterface)) {
                    return Normalizer::normalize($this->definitions[$id], $id);
                }

                if (
                    is_string($this->definitions[$id])
                    && $this->hasDefinition($this->definitions[$id])
                    && $this->definitions[$id] !== $this->definitions[$this->definitions[$id]]
                ) {
                    $this->definitionInstances[$id] = $this->getDefinition($this->definitions[$id]);
                } else {
                    $this->definitionInstances[$id] =
                        (is_string($this->definitions[$id]) && class_exists($this->definitions[$id]))
                            ? ArrayDefinition::fromPreparedData($this->definitions[$id])
                            : Normalizer::normalize($this->definitions[$id], $id);
                }
            } else {
                throw new LogicException(
                    sprintf('No definition found for "%s".', $id)
                );
            }
        }

        return $this->definitionInstances[$id];
    }

    /**
     * Check if there is a definition with a given identifier.
     *
     * @param string $id Identifier to look for.
     *
     * @return bool If there is a definition with a given identifier.
     */
    public function hasDefinition(string $id): bool
    {
        return array_key_exists($id, $this->definitions);
    }

    /**
     * @throws CircularReferenceException
     * @throws InvalidConfigException
     * @throws NotFoundException
     * @throws NotInstantiableException
     */
    private function build(string $id): mixed
    {
        if (isset($this->creatingIds[$id])) {
            throw new CircularReferenceException(
                sprintf(
                    'Circular reference to "%s" detected while creating: %s.',
                    $id,
                    implode(', ', array_keys($this->creatingIds))
                )
            );
        }

        $definition = $this->getDefinition($id);

        $this->creatingIds[$id] = 1;
        try {
            return $definition->resolve($this);
        } finally {
            unset($this->creatingIds[$id]);
        }
    }
}
