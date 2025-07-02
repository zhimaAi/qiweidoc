<?php

declare(strict_types=1);

namespace Yiisoft\Profiler\Target;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * LogTarget saves profiling messages as a log messages.
 */
final class LogTarget extends AbstractTarget
{
    public function __construct(
        /**
         * @var LoggerInterface Logger to be used for message export.
         */
        private LoggerInterface $logger,
        /**
         * @var string Log level to be used for messages export.
         */
        private string $logLevel = LogLevel::DEBUG
    ) {
    }

    public function export(array $messages): void
    {
        foreach ($messages as $message) {
            $this->logger->log($this->logLevel, $message->token(), $message->context());
        }
    }
}
