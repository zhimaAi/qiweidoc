<?php

namespace Modules\Main\Model;

use Common\DB\BaseModel;

class SettingModel  extends BaseModel
{
    public function getTableName(): string
    {
        return "main.settings";
    }

    protected function isAutoIncrementPK(): bool
    {
        return false;
    }

    protected array | string $primaryKeys = 'key';

    protected function casts(): array
    {
        return [
            "key" => 'string',
            "value"   => 'string',
            "created_at" => 'string',
            "updated_at" => 'string',
        ];
    }

    public static function getValue(string $key): null|string
    {
        return SettingModel::query()->where(['key' => $key])->getOne()?->get('value');
    }

    public static function setValue(string $key, string $value)
    {
        SettingModel::updateOrCreate(['key' => $key], ['key' => $key, 'value' => $value]);
    }
}
