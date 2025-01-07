<?php

namespace Common;

use RoadRunner\Logger\Logger;
use Yiisoft\Log\Target;

class RoadRunnerAppLogTarget extends Target
{
    private Logger $rrLogger;

    public function __construct(int $exportInterval)
    {
        $this->rrLogger = new Logger(Yii::getRpcClient());
        $this->setExportInterval($exportInterval);

        parent::__construct();
    }

    protected function export(): void
    {
        $formattedMessages = $this->getFormattedMessages();

        foreach ($this->getMessages() as $key => $message) {
            $context = $message->context();

            // 过滤掉数据库的连接日志
            if (!empty($context['type']) && $context['type'] == 'connection') {
                continue;
            }

            // 过滤掉一些common context
            unset($context['trace']);
            unset($context['time']);
            unset($context['memory']);
            unset($context['category']);
            unset($context[0]);

            // 过滤掉框架启动时的一些sql
            if (str_contains($message->message(), "pg_class") || str_contains($message->message(), 'migration')) {
                continue;
            }

            // 格式化
            $trace = $message->trace()[0] ?? [];
            $file = $trace['file'] ?? '';
            $line = $trace['line'] ?? 0;
            if ($file) {
                $text = sprintf("[%s:%d] %s", $file, $line, $message->message());
            } else {
                $text = sprintf("%s", $message->message());
            }

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
