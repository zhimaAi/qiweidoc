<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\StartupPolicy\Condition;

use Yiisoft\Strings\WildcardPattern;
use Yiisoft\Yii\Http\Event\BeforeRequest;

final class UriPathCondition implements ConditionInterface
{
    public function __construct(
        /**
         * @var string[]
         * @psalm-var list<non-empty-string>
         */
        private readonly array $paths,
    ) {
    }

    public function match(object $event): bool
    {
        if (!$event instanceof BeforeRequest) {
            return false;
        }

        $path = $event->getRequest()->getUri()->getPath();

        foreach ($this->paths as $pattern) {
            if ((new WildcardPattern($pattern, ['/']))->match($path)) {
                return true;
            }
        }

        return false;
    }
}
