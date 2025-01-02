<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\Main\Consumer;

use Modules\Main\Model\CorpModel;
use Modules\Main\Service\TagsService;
use Throwable;

/**
 * 标签打标消费者
 * Producer::dispatch(MarkTagConsumer::class, ['taskMsg'=>json_encode($tag_task)]);
 * Producer::dispatch(MarkTagConsumer::class, ['taskInfo'=>$tag_task]);
 */
    class MarkTagConsumer
{
    /** @var string 通过event消息通讯的json_encode 的字符串 */
    private string $taskMsg;
    /** @var array 直接使用任务数据通讯的消费者 可以在主模块使用 */
    private array $taskInfo;
    /** @var string 默认来源 */
    private string $defaultSource;

        /**
         * 支持传递字符串 或 直接传递数组
         * @param string $taskMsg
         * @param array $taskInfo
         * @param string $defaultSource
         */
    public function __construct(string $taskMsg='',array $taskInfo=[],string $defaultSource='')
    {
        $this->taskMsg = $taskMsg;
        $this->taskInfo = $taskInfo;
        $this->defaultSource=$defaultSource;
    }

        /**
         * @throws Throwable
         */
        public function handle(): void
    {
        //echo "收到任务 {$this->taskMsg}...\n";

        if (empty($this->taskInfo)){
            $taskData = @json_decode($this->taskMsg, true);
            if (empty($taskData)){
                ddump(['无法解析的任务数据'=>$this->taskMsg]);
                return;
            }
        }else{
            $taskData=$this->taskInfo;
        }

        $corp_id = $taskData['corp_id']??'';
        if (empty($corp_id)){
            ddump(['任务中无企业id'=>$taskData]);
            return;
        }
        //来源记录 如果没有就记录为对应的子模块
        if (empty($taskData['source']) && !empty($this->defaultSource)){
            $taskData['source']=$this->defaultSource;
        }

        //查看企业是否存在
        $corp = CorpModel::query()->where(['id'=>$taskData['corp_id']])->getOne();
        if (empty($corp)){
            ddump(['无法获取对应的企业信息'=>$taskData]);
            return;
        }
        if (!$corp instanceof CorpModel) {
            ddump(['数据库获取的企业信息失败' => $taskData]);
            return;
        }

        TagsService::MarkTag($corp,$taskData);
    }
}
