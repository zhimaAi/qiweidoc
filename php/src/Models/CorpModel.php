<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

/**
 * 企业信息
 */

namespace App\Models;

use App\Libraries\Core\DB\BaseModel;
use App\Libraries\Core\Exception\WechatRequestException;
use App\Libraries\Core\Yii;
use EasyWeChat\Kernel\Exceptions\BadResponseException;
use Exception;
use LogicException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class CorpModel extends BaseModel
{
    public function getTableName(): string
    {
        return "corps";
    }

    public function getPrimaryKeys(): string | array
    {
        return "id";
    }

    protected function isAutoIncrementPK(): bool
    {
        return false;
    }

    protected function casts(): array
    {
        return [];
    }

    const SecretTypeDefault = 1;
    const SecretTypeChat = 2;

    /**
     * 封装企微api请求
     *
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws WechatRequestException
     * @throws ServerExceptionInterface
     * @throws BadResponseException
     * @throws Exception
     */
    private function requestWechatApi(string $method, string $url, array $options = [], string $type = 'array', int $secretType = 1): array
    {
        if (! in_array($method, ['GET', 'POST'])) {
            throw new Exception("企微接口请求类型错误");
        }

        if ($secretType == 2) { // 会话存档独立的密钥
            $app = Yii::getEasyWechatClient($this->get('id'), $this->get('chat_secret'));
        } else {
            $app = Yii::getEasyWechatClient($this->get('id'), $this->get('agent_secret'));
        }

        try {
            if ($method == 'GET') {
                $res = $app->getClient()->get($url, $options);
            } else {
                if ($type == 'json') {
                    $res = $app->getClient()->postJson($url, $options);
                } else {
                    $res = $app->getClient()->post($url, $options);
                }
            }
            $isFail = $res->isFailed(); //企微返回错误状态码转换成异常
            if ($isFail) {
                throw new LogicException($res->toJson(), $res->toArray()['errcode'] ?? -1);
            }
        } catch (LogicException $e) {
            throw new WechatRequestException($e, $e->getCode());
        } catch (\Throwable $e) {
            throw new WechatRequestException($e, WechatRequestException::STATIC_ERROR, $e);
        }

        return $res->toArray();
    }

    /**
     * 请求企微get接口
     *
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws WechatRequestException
     * @throws ServerExceptionInterface
     * @throws BadResponseException
     */
    public function getWechatApi(string $url, array $options = [], int $secretType = 1): array
    {
        return $this->requestWechatApi('GET', $url, $options, 'array', $secretType);
    }

    /**
     * 请求企微post接口
     *
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws WechatRequestException
     * @throws ServerExceptionInterface
     * @throws BadResponseException
     */
    public function postWechatApi(string $url, array $options = [], string $type = 'array', int $secretType = 1): array
    {
        return $this->requestWechatApi('POST', $url, $options, $type, $secretType);
    }

    /**
     * 批量请求企微GET接口
     *
     * 数据格式比较复杂，不做任务数据校验，请启用try-cache包起来处理错误
     *
     * 封装的golang提供的rpc接口
     *
     * 请求格式：
     *
     * $inputs = [
     *    'requests' => [
     *         [
     *              'url' => '/cgi-bin/users/list',
     *              'headers => ['content-type' => 'application/json'],
     *              'method' => 'GET',
     *              'params' => ['a' => 1, 'b' => 2],
     *          ],
     *          [
     *              'url' => '/cgi-bin/users/list',
     *              'headers' => ['content-type' => 'application/json'],
     *              'method' => 'POST',
     *              'params' => ['a' => 1, 'b' => 2],
     *              'body' => ['aaa' => 111, 'bbb' => 222],
     *           ]
     *      ],
     *    'concurrency' => 10,
     *    'qps' => 10,
     *    'timeout' => 60,
     * ];
     *
     *  测试代码：
     *  $corpModel = CorpModel::findByPK('ww5f432b3a24a9b9f1');
     *  $inputs = ['concurrency' => 10];
     *       for ($i = 0; $i < 2; $i++) {
     *           $inputs['requests'][] = [
     *               'url' => '/cgi-bin/user/list',
     *               'method' => 'GET',
     *       ];
     *  }
     *  $result =$corpModel->batchPostWechatApi($inputs);
     *  dump($result);
     *
     * 正确响应结果示例：
     * [
     *      'responses' => [
     *          0 => [
     *              'request_id' => '53cbe3cb-0249-4c37-bdcd-1c3cb2704048-0'
     *              'url' => 'https://qyapi.weixin.qq.com/cgi-bin/get_api_domain_ip'
     *              'status_code' => 200
     *              'headers' => [
     *                  'Content-Length' => '336'
     *                  'Content-Type' => 'application/json; charset=UTF-8'
     *                  'Date' => 'Fri, 01 Nov 2024 10:46:58 GMT'
     *                  'Error-Code' => '0'
     *                  'Error-Msg' => 'ok'
     *                  'Server' => 'nginx'
     *                  'X-W-No' => '6'
     *              ]
     *              'body' => '{"ip_list":["183.47.100.66","183.47.102.153","157.148.55.111","157.148.41.225","120.233.17.190","120.241.149.189","42.194.252.200","42.194.252.76","101.91.40.24","101.226.141.58","210.22.244.32","140.206.161.227","117.135.156.58","117.185.253.167","81.69.54.213","81.69.87.29","43.135.106.227","43.135.106.8"],"errcode":0,"errmsg":"ok"}'
     *          ]
     *          1 => [
     *              'request_id' => '53cbe3cb-0249-4c37-bdcd-1c3cb2704048-1'
     *              'url' => 'https://qyapi.weixin.qq.com/cgi-bin/get_api_domain_ip'
     *              'status_code' => 200
     *              'headers' => [
     *                  'Content-Length' => '336'
     *                  'Content-Type' => 'application/json; charset=UTF-8'
     *                  'Date' => 'Fri, 01 Nov 2024 10:46:58 GMT'
     *                  'Error-Code' => '0'
     *                  'Error-Msg' => 'ok'
     *                  'Server' => 'nginx'
     *                  'X-W-No' => '5'
     *              ]
     *              'body' => '{"ip_list":["183.47.100.66","183.47.102.153","157.148.55.111","157.148.41.225","120.233.17.190","120.241.149.189","42.194.252.200","42.194.252.76","101.91.40.24","101.226.141.58","210.22.244.32","140.206.161.227","117.135.156.58","117.185.253.167","81.69.54.213","81.69.87.29","43.135.106.227","43.135.106.8"],"errcode":0,"errmsg":"ok"}'
     *          ]
     *      ]
     *      'batch_id' => '53cbe3cb-0249-4c37-bdcd-1c3cb2704048'
     * ]
     *
     * 错误响应结果示例:
     *  [
     *      'responses' => [
     *          0 => [
     *              'request_id' => '16e7c408-e6d6-4d1b-bfcc-5502f258c44c-0'
     *              'url' => 'https://qyapi.weixin.qq.com/cgi-bin/user/list'
     *              'status_code' => 200
     *              'headers' => [
     *                  'Content-Length' => '190'
     *                  'Content-Type' => 'application/json; charset=UTF-8'
     *                  'Date' => 'Fri, 01 Nov 2024 10:10:38 GMT'
     *                  'Error-Code' => '41001'
     *                  'Error-Msg' => 'access_token missing, hint: [1730455838562580895026052], from ip: 171.43.188.73, more info at https://open.work.weixin.qq.com/devtool/query?e=41001'
     *                  'Server' => 'nginx'
     *                  'X-W-No' => '7'
     *              ]
     *              'body' => '{"ip_list":[],"errcode":60020,"errmsg":"not allow to access from your ip, hint: [1730458186056773912722469], from ip: 171.43.188.73, more info at https://open.work.weixin.qq.com/devtool/query?e=60020"}'
     *          ]
     *          1 => [
     *              'request_id' => '16e7c408-e6d6-4d1b-bfcc-5502f258c44c-1'
     *              'url' => 'https://qyapi.weixin.qq.com/cgi-bin/user/list'
     *              'status_code' => 200
     *              'headers' => [
     *                  'Content-Length' => '190'
     *                  'Content-Type' => 'application/json; charset=UTF-8'
     *                  'Date' => 'Fri, 01 Nov 2024 10:10:38 GMT'
     *                  'Error-Code' => '41001'
     *                  'Error-Msg' => 'access_token missing, hint: [1730455838197562819860875], from ip: 171.43.188.73, more info at https://open.work.weixin.qq.com/devtool/query?e=41001'
     *                  'Server' => 'nginx'
     *                  'X-W-No' => '4'
     *               ]
     *              'body' => '{"ip_list":[],"errcode":60020,"errmsg":"not allow to access from your ip, hint: [1730458186419203598556882], from ip: 171.43.188.73, more info at https://open.work.weixin.qq.com/devtool/query?e=60020"}'
     *          ]
     *      ],
     *      'batch_id' => '16e7c408-e6d6-4d1b-bfcc-5502f258c44c'
     * ]
     *
     *
     * @throws Exception
     */
    public function batchPostWechatApi(array $input)
    {
        $accessToken = Yii::getEasyWechatClient($this->get('id'), $this->get('agent_secret'))
            ->getAccessToken()
            ->getToken();

        $baseUri = "https://qyapi.weixin.qq.com";

        foreach ($input['requests'] as &$request) {
            $url = $baseUri . $request['url'];
            $params['access_token'] = $accessToken;
            $request['url'] = $url;
            $request['params'] = $params;
        }

        return Yii::getRpcClient()->call('httpbatch.Request', $input);
    }
}
