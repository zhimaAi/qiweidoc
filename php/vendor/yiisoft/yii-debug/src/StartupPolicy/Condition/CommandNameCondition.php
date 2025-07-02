<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\StartupPolicy\Condition;

use Yiisoft\Strings\WildcardPattern;
use Yiisoft\Yii\Console\Event\ApplicationStartup;

final class CommandNameCondition implements ConditionInterface
{
    public function __construct(
        /**
         * @var string[]
         * @psalm-var list<non-empty-string>
         */
        private readonly array $names,
    ) {
    }

    public function match(object $event): bool
    {
        if (!$event instanceof ApplicationStartup) {
            return false;
        }

        $name = (string) $event->commandName;

        foreach ($this->names as $pattern) {
            if ((new WildcardPattern($pattern, [':']))->match($name)) {
                return true;
            }
        }

        return false;
    }
}
