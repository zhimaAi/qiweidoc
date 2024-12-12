<?php

namespace Modules\HintKeywords\Library\DingDing;
/**
 * Created by PhpStorm.
 * User: fabian
 * Date: 2022/7/11
 * Time: 10:56
 * 钉钉消息
 */
class DingDingTalkService
{
    protected $secret_key;
    protected $access_token;
    public $msgService;

    const WEBHOOK_URL = 'https://oapi.dingtalk.com/robot/send';

    public function __construct(string $token, string $secretKey, IMsgService $msgService)
    {
        $this->access_token = $token;
        $this->secret_key = $secretKey;
        $this->msgService = $msgService;
    }

    /**
     * 生成sign
     * User: fabian
     * Date: 2022/7/11
     * @param $time
     * @return string
     */
    private function makeSign($time): string
    {
        $secret = $this->secret_key;
        $sign = hash_hmac('sha256', $time . "\n" . $secret, $secret,true);
        $sign = base64_encode($sign);
        return urlencode($sign);
    }

    /**
     * 生成url
     * User: fabian
     * Date: 2022/7/11
     * @param $timestamp
     * @param string $sign
     * @return string
     */
    private function makeUrl($timestamp, string $sign): string
    {
        $params = [
            'access_token' => $this->access_token,
            'timestamp' => $timestamp,
            'sign' => $sign,
        ];
        return self::WEBHOOK_URL . '?' . http_build_query($params);
    }

    /**
     * 发送钉钉消息
     * User: fabian
     * Date: 2022/7/11
     * @param string $content
     * @return mixed
     */
    public function sendMsg(string $content)
    {
        $now = time() * 1000;
        $sign = $this->makeSign($now);
        $url = $this->makeUrl($now, $sign);
        return requestByPost($url, $this->msgService->makeMsg($content));
    }
}
