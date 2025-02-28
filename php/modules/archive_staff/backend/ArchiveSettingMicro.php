<?php

namespace Modules\ArchiveStaff;

class ArchiveSettingMicro
{
    public function __construct(private string $payload = '')
    {
    }

    public function handle()
    {
        $settings = SettingModel::query()->getAll();

        $result = [];
        foreach ($settings as $row) {
            $result[$row->get('key')] = $row->get('value');
        }

        return $result;
    }
}
