<?php

declare(strict_types=1);

namespace Yiisoft\Hydrator\Validator;

use Yiisoft\Hydrator\DataInterface;
use Yiisoft\Hydrator\HydratorInterface;
use Yiisoft\Hydrator\Validator\Attribute\ValidateResolver;
use Yiisoft\Validator\Result;
use Yiisoft\Validator\ValidatorInterface;

/**
 * `ValidatingHydrator` is a decorator for {@see HydratorInterface} that validates data before hydration.
 */
final class ValidatingHydrator implements HydratorInterface
{
    /**
     * @param HydratorInterface $hydrator Hydrator to decorate.
     * @param ValidatorInterface $validator Validator to use.
     * @param ValidateResolver $validateResolver Resolver for {@see Validate} attribute.
     */
    public function __construct(
        private HydratorInterface $hydrator,
        private ValidatorInterface $validator,
        private ValidateResolver $validateResolver,
    ) {
    }

    public function hydrate(object $object, array|DataInterface $data = []): void
    {
        $result = $this->beforeAction();
        $this->hydrator->hydrate($object, $data);
        $this->afterAction($object, $result);
    }

    public function create(string $class, array|DataInterface $data = []): object
    {
        $result = $this->beforeAction();
        $object = $this->hydrator->create($class, $data);
        $this->afterAction($object, $result);
        return $object;
    }

    private function beforeAction(): Result
    {
        $result = new Result();
        $this->validateResolver->setResult($result);
        return $result;
    }

    private function afterAction(object $object, Result $result): void
    {
        if (!$object instanceof ValidatedInputInterface) {
            return;
        }

        $result->isValid()
            ? $this->validator->validate($object)
            : $object->processValidationResult($result);
    }
}
