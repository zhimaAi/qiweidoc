<?php

// Copyright © 2016- 2025 Sesame Network Technology all right reserved

declare(strict_types=1);

namespace Common\Command;

use Carbon\Carbon;
use Modules\Main\Model\ChatMessageModel;
use Modules\Main\Model\CorpModel;
use Modules\Main\Service\ChatSessionPullService;
use Modules\Main\Service\ChatSessionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Console\ExitCode;

#[AsCommand(name: 'download-message-media', description: 'download message media', hidden: false)]
class DownloadMessageMediasCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $corp = CorpModel::query()->getOne();
        if (empty($corp)) {
            return ExitCode::OK;
        }

        $messages = ChatMessageModel::query()
            ->where(['corp_id' => $corp->get('id')])
            ->andWhere(['in', 'msg_type', ChatSessionService::ValidMediaType])
            ->andWhere(['msg_content' => ''])
            ->andWhere(['<', 'download_retry_count', ChatSessionPullService::MAX_MEDIA_DOWNLOAD_ATTEMPTS])
            ->andWhere(['<', 'msg_time', Carbon::now()->subHour()->toDateTimeString('millisecond')])
            ->andWhere(['>', 'msg_time', Carbon::now()->subDays(5)->toDateTimeString('millisecond')])
            ->orderBy(['msg_time' => SORT_ASC])
            ->limit(1000)
            ->getAll();
        foreach ($messages as $message) {
            /** @var ChatMessageModel $message */
            ChatSessionPullService::dispatchMediaDownload($corp, $message);
        }

        return ExitCode::OK;
    }
}
