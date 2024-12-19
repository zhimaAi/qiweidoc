<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace Modules\TimeoutReplySingle\Controller;

use Common\Controller\BaseController;
use Common\Yii;
use LogicException;
use Modules\Main\Model\ChatConversationsModel;
use Modules\Main\Model\CorpModel;
use Modules\Main\Service\ChatSessionService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class WxController extends BaseController
{
    public function getAgentConfig(ServerRequestInterface $request): ResponseInterface
    {
        $corp = $request->getAttribute(CorpModel::class);

        $url = $request->getQueryParams()['current_url'] ?? '';
        if (empty($url)) {
            throw new LogicException('缺少参数');
        }

        $app = Yii::getEasyWechatClient($corp->get('id'), $corp->get('agent_secret'));
        $config = $app->getUtils()->buildJsSdkAgentConfig($corp->get('agent_id'), urldecode($url), ['checkJsApi', 'openEnterpriseChat', 'openExistedChatWithMsg']);

        return $this->jsonResponse($config);
    }

    public function messages(ServerRequestInterface $request): ResponseInterface
    {
        $corp = $request->getAttribute(CorpModel::class);
        $conversation = $request->getAttribute(ChatConversationsModel::class);

        $params = $request->getQueryParams();
        $page = max($params['page'] ?? 1, 1);
        $size = max($params['size'] ?? 10, 1);

        $result = ChatSessionService::getMessageListByConversation($page, $size, $corp, $conversation->get('id'));

        return $this->jsonResponse($result);
    }
}
