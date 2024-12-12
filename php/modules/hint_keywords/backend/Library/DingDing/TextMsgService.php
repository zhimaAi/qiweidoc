<?php

namespace Modules\HintKeywords\Library\DingDing;

/**
 * Created by PhpStorm.
 * User: fabian
 * Date: 2022/7/11
 * Time: 10:57
 * 钉钉文本消息
 */
class TextMsgService implements IMsgService
{
    private $is_at_all = false;
    private $at_mobiles = [];

    const TYPE = 'text';

    /**
     * @param bool $is_at_all
     */
    public function setIsAtAll(bool $is_at_all): void
    {
        $this->is_at_all = $is_at_all;
    }

    /**
     * @param array $at_mobiles
     */
    public function setAtMobiles(array $at_mobiles): void
    {
        $this->at_mobiles = $at_mobiles;
    }

    /**
     * 钉钉消息组装
     * User: fabian
     * Date: 2022/7/19
     * @param string $content
     * @return false|string
     */
    public function makeMsg(string $content)
    {
        $data = [
            'msgtype' => self::TYPE,
            'text' => [
                'content' => $content,
            ],
            'at' => [
                'atMobiles' => $this->at_mobiles,
                'isAtAll' => $this->is_at_all,
            ],
        ];
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }
}
