<?php

namespace Modules\Main\Controller;

use Common\Controller\BaseController;
use Common\Yii;
use LogicException;
use Modules\Main\Model\ChatConversationsModel;
use Modules\Main\Model\CorpModel;
use Modules\Main\Model\GroupModel;
use Modules\Main\Service\ChatSessionService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

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

    /**
     * @throws Throwable
     */
    public function getMessageListByConversation(ServerRequestInterface $request): ResponseInterface
    {
        $corp = $request->getAttribute(CorpModel::class);
        $conversation = $request->getAttribute(ChatConversationsModel::class);
        if (empty($conversation)) {
            throw new LogicException('缺少会话id参数');
        }

        $params = $request->getQueryParams();
        $page = max($params['page'] ?? 1, 1);
        $size = max($params['size'] ?? 10, 1);

        $result = ChatSessionService::getMessageListByConversation($page, $size, $corp, $conversation->get('id'));

        return $this->jsonResponse($result);
    }

    /**
     * @throws Throwable
     */
    public function getMessageListByGroup(ServerRequestInterface $request): ResponseInterface
    {
        $corp = $request->getAttribute(CorpModel::class);
        $group = $request->getAttribute(GroupModel::class);
        if (empty($group)) {
            throw new LogicException('缺少群聊id参数');
        }

        $params = $request->getQueryParams();
        $page = max($params['page'] ?? 1, 1);
        $size = max($params['size'] ?? 10, 1);

        $result = ChatSessionService::getMessageListByGroup($page, $size, $corp, $group->get('chat_id'));

        return $this->jsonResponse($result);
    }
}
