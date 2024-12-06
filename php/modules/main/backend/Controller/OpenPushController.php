<?php

namespace Modules\Main\Controller;

use Common\Controller\BaseController;
use Common\Job\Producer;
use EasyWeChat\Work\Application;
use Modules\Main\Consumer\QwOpenPushConsumer;
use Modules\Main\Model\CorpModel;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use Yiisoft\Http\Method;
use Yiisoft\Router\HydratorAttribute\RouteArgument;

class OpenPushController extends BaseController
{
    /**
     * 接收企微事件推送事件
     * @throws Throwable
     */
    public function qwPush(ServerRequestInterface $request,  #[RouteArgument('corp_id')] string $corpId): ResponseInterface
    {
        $corp = CorpModel::query()->where(['id' => $corpId])->getOne();
        if (empty($corp)) {
            return $this->textResponse('error');
        }

        $config = [
            'corp_id' => $corp->get('id'),
            'secret' => $corp->get('agent_secret'),
            'token' => $corp->get('callback_event_token'),
            'aes_key' => $corp->get('callback_event_aes_key'),
        ];

        $server = (new Application($config))->setRequest($request)->getServer();

        if ($request->getMethod() == Method::POST) {
            Producer::dispatch(QwOpenPushConsumer::class, ['corp' => $corp, 'message' => $server->getDecryptedMessage()]);
        }

        return $server->serve();
    }
}
