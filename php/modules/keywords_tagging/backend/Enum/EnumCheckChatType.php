<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\KeywordsTagging\Enum;

enum EnumCheckChatType: int
{
    case SingleChat = 1;
    case GroupChat = 2;

    public function getLabel(): string
    {
        return match($this) {
            self::SingleChat => '仅单聊',
            self::GroupChat => '仅群聊',
        };
    }

    public static function getValues(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
