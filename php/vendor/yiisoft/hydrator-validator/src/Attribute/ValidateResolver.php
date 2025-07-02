<?php

declare(strict_types=1);

namespace Yiisoft\Hydrator\Validator\Attribute;

use Yiisoft\Hydrator\Attribute\Parameter\ParameterAttributeInterface;
use Yiisoft\Hydrator\Attribute\Parameter\ParameterAttributeResolverInterface;
use Yiisoft\Hydrator\AttributeHandling\Exception\UnexpectedAttributeException;
use Yiisoft\Hydrator\AttributeHandling\ParameterAttributeResolveContext;
use Yiisoft\Hydrator\Result;
use Yiisoft\Validator\Result as ValidationResult;
use Yiisoft\Validator\ValidatorInterface;

/**
 * Resolver for {@see Validate} attribute.
 */
final class ValidateResolver implements ParameterAttributeResolverInterface
{
    /**
     * @var ValidationResult|null Validation result.
     */
    private ?ValidationResult $result = null;

    /**
     * @param ValidatorInterface $validator Validator to use.
     */
    public function __construct(
        private ValidatorInterface $validator,
    ) {
    }

    /**
     * Sets validation result.
     *
     * @param ValidationResult|null $result Validation result.
     */
    public function setResult(?ValidationResult $result): void
    {
        $this->result = $result;
    }

    public function getParameterValue(
        ParameterAttributeInterface $attribute,
        ParameterAttributeResolveContext $context,
    ): Result {
        if (!$attribute instanceof Validate) {
            throw new UnexpectedAttributeException(Validate::class, $attribute);
        }

        if ($this->result !== null) {
            $parameterName = $context->getParameter()->getName();
            $result = $this->validator->validate(
                $context->isResolved() ? [$parameterName => $context->getResolvedValue()] : [],
                [$parameterName => $attribute->getRules()],
            );

            foreach ($result->getErrors() as $error) {
                $this->result->addError(
                    $error->getMessage(),
                    $error->getParameters(),
                    $error->getValuePath(),
                );
            }
        }

        return Result::fail();
    }
}
