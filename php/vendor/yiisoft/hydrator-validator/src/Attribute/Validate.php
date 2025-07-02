<?php

declare(strict_types=1);

namespace Yiisoft\Hydrator\Validator\Attribute;

use Attribute;
use Yiisoft\Hydrator\Attribute\Parameter\ParameterAttributeInterface;
use Yiisoft\Validator\RuleInterface;

/**
 * Added to either property or parameter to indicate that raw values should be validated. Validation rules are passed
 * as arguments to the attribute.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER | Attribute::IS_REPEATABLE)]
final class Validate implements ParameterAttributeInterface
{
    /**
     * @var RuleInterface[] $rules Validation rules.
     * @psalm-var list<RuleInterface> $rules
     */
    private array $rules;

    /**
     * @param RuleInterface ...$rules Validation rules.
     */
    public function __construct(RuleInterface ...$rules)
    {
        $this->rules = array_values($rules);
    }

    /**
     * @return RuleInterface[] Validation rules.
     * @psalm-return list<RuleInterface>
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    public function getResolver(): string
    {
        return ValidateResolver::class;
    }
}
