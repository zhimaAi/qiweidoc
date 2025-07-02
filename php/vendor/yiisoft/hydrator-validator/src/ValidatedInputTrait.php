<?php

declare(strict_types=1);

namespace Yiisoft\Hydrator\Validator;

use LogicException;
use Yiisoft\Hydrator\Attribute\SkipHydration;
use Yiisoft\Validator\Result;

/**
 * Implementation of {@see ValidatedInputInterface}.
 */
trait ValidatedInputTrait
{
    /**
     * @var Result|null Validation result.
     */
    #[SkipHydration]
    private ?Result $validationResult = null;

    public function processValidationResult(Result $result): void
    {
        $this->validationResult = $result;
    }

    public function getValidationResult(): Result
    {
        if (empty($this->validationResult)) {
            throw new LogicException('Validation result is not set.');
        }

        return $this->validationResult;
    }
}
