<?php

namespace Modules\ArchiveStaff;

use Common\DB\BaseModel;

class SettingModel extends BaseModel
{
    public function getTableName(): string
    {
        return "archive_staff.settings";
    }

    protected function isAutoIncrementPK(): bool
    {
        return false;
    }

    protected array | string $primaryKeys = 'key';

    public function casts(): array
    {
        return [
            'key'       => 'string',
            'value'     => 'string',
            "created_at" => 'string',
            "updated_at" => 'string',
        ];
    }
}
