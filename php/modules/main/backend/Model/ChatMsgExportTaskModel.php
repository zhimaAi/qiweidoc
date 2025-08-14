<?php

namespace Modules\Main\Model;

use Common\DB\BaseModel;

class ChatMsgExportTaskModel extends BaseModel
{

    const STATE_INIT = 1;//未开始
    const STATE_EXPORTING = 2;//导出中
    const STATE_EXPORTED = 3;//已完成
    const STATE_ERROR = 4;//导出失败
    const STATE_CANCEL = 5;//已取消

    public function getTableName(): string
    {
        return "main.chat_msg_export_task";
    }

    protected function casts(): array
    {
        return [
            "id" => 'int',
            "corp_id" => 'string',
            "create_userid" => 'string',
            "export_type" => 'int',
            "state" => 'int',
            "req_data" => 'array',
            "error_info" => 'array',
            "task_title" => 'string',
            "file_path" => 'string',
            "created_at" => 'string',
            "updated_at" => 'string',
        ];
    }
}
