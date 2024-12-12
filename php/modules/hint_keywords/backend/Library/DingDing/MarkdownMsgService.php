<?php
namespace Modules\HintKeywords\Library\DingDing;

/**
 * 钉钉markdown消息
 */
class MarkdownMsgService implements IMsgService {

    private $is_at_all  = false;
    private $at_mobiles = [];
    private $title      = '无标题';

    const TYPE = 'markdown';

    /**
     * @param bool $is_at_all
     */
    public function setIsAtAll(bool $is_at_all): void {
        $this->is_at_all = $is_at_all;
    }

    /**
     * @param array $at_mobiles
     */
    public function setAtMobiles(array $at_mobiles): void {
        $this->at_mobiles = $at_mobiles;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void {
        if (!empty($title)) {
            $this->title = $title;
        }
    }

    public function makeMsg(string $content) {
        if ($this->is_at_all) {
            $content .= sprintf('@%s',"all");
        } else {
            foreach ($this->at_mobiles as $vak) {
                $content .= sprintf('@%s',$vak);
            }
        }
        $data = [
            'msgtype'  => self::TYPE,
            self::TYPE => [
                'title' => $this->title,
                'text'  => $content,
            ],
            'at'       => [
                'atMobiles' => $this->at_mobiles,
                'isAtAll'   => $this->is_at_all,
            ],
        ];
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

}
