<?php

declare(strict_types=1);

namespace Yiisoft\Log;

use Psr\Log\LoggerInterface;

/**
 * PsrTarget is a log target which simply passes messages to another PSR-3 compatible logger.
 */
final class PsrTarget extends Target
{
    /**
     * Sets the PSR-3 logger used to save messages of this target.
     *
     * @param LoggerInterface $logger The logger instance to be used for messages processing.
     */
    public function __construct(private LoggerInterface $logger)
    {
        parent::__construct();
    }

    /**
     * @return LoggerInterface The logger instance.
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    protected function export(): void
    {
        foreach ($this->getMessages() as $message) {
            /** @var array $context */
            $context = $message->context();
            $this->logger->log($message->level(), $message->message(), $context);
        }
    }
}
