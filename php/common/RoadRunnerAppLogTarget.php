<?php

namespace Common;

use RoadRunner\Logger\Logger;
use Yiisoft\Log\Target;

class RoadRunnerAppLogTarget extends Target
{
    private Logger $rrLogger;

    public function __construct()
    {
        $this->rrLogger = new Logger(Yii::getRpcClient());

        parent::__construct();
    }

    protected function export(): void
    {
        $formattedMessages = $this->getFormattedMessages();

        foreach ($this->getMessages() as $key => $message) {
            $trace = $message->trace()[0] ?? [];
            $file = $trace['file'] ?? '';
            $line = $trace['line'] ?? 0;

            $context = $message->context();
            unset($context['trace']);

            $text = sprintf("file: %s:%d %s", $file, $line, $message->message());

            if ($message->level() == 'info') {
                $this->rrLogger->info($text, $context);
            } elseif ($message->level() == 'warning') {
                $this->rrLogger->warning($text, $context);
            } elseif ($message->level() == 'error') {
                $this->rrLogger->error($text, $context);
            } elseif ($message->level() == 'debug') {
                $this->rrLogger->debug($text, $context);
            } else {
                $this->rrLogger->log($text, $context);
            }
        }
    }
}
