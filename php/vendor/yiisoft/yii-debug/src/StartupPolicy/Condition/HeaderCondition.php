<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\StartupPolicy\Condition;

use Yiisoft\Yii\Http\Event\BeforeRequest;

use function in_array;
use function strtolower;

final class HeaderCondition implements ConditionInterface
{
    private const TRUE_VALUES = ['1', 'true', 'on'];

    public function __construct(
        /**
         * @psalm-var non-empty-string
         */
        private readonly string $headerName,
    ) {
    }

    public function match(object $event): bool
    {
        if (!$event instanceof BeforeRequest) {
            return false;
        }

        $request = $event->getRequest();

        return $request->hasHeader($this->headerName)
            && in_array(strtolower($request->getHeaderLine($this->headerName)), self::TRUE_VALUES, true);
    }
}
