<?php

namespace Modules\Main\DTO;

use Common\DTO\BaseDTO;
use Yiisoft\Validator\Rule\Each;
use Yiisoft\Validator\Rule\Nested;
use Yiisoft\Validator\Rule\Number;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\Type\IntegerType;
use Yiisoft\Validator\Rule\Type\StringType;

class UpdateCorpTagDTO extends BaseDTO
{
    public ?string $groupId = null;

    public ?string $groupName = null;

    public ?int $order = null;

    public ?array $tag = null;

    public function getRules(): array
    {
        return [
            'groupId' => [
                new StringType(message: '标签组id不合法', skipOnEmpty: true),
            ],
            'groupName' => [
                new Required(message: '标签组名称不能为空'),
                new StringType(message: '标签组名称不合法'),
            ],
            'order' => [
                new Required(message: '标签次序值不能为空'),
                new IntegerType(message: '标签次序不能为空'),
                new Number(min: 0, lessThanMinMessage: '标签次序不能少于0'),
            ],
            'tag' => [
                new Required(message: '标签列表不能为空'),
                new Each([
                    new Nested([
                        'id' => [
                            new StringType(message: '标签id不合法', skipOnEmpty: true),
                        ],
                        'name' => [
                            new Required(message: '标签名不能为空'),
                            new StringType(message: '标签名不合法'),
                        ],
                        'order' => [
                            new Required(message: '标签次序值不能为空'),
                            new IntegerType(message: '标签次序不能为空'),
                            new Number(min: 0, lessThanMinMessage: '标签次序不能少于0'),
                        ],
                    ]),
                ]),

            ],
        ];
    }
}
