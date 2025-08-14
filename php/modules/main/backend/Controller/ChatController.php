<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\Main\Controller;

use Common\Controller\BaseController;
use Common\Job\Producer;
use Exception;
use Modules\Main\Consumer\ChatMsgExportConsumer;
use Modules\Main\DTO\ChatSession\CollectDTO;
use Modules\Main\Enum\EnumUserRoleType;
use Modules\Main\Model\ChatMsgExportTaskModel;
use Modules\Main\Model\CorpModel;
use Modules\Main\Service\ChatSessionService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use Yiisoft\Auth\Middleware\Authentication;
use Yiisoft\Db\Exception\InvalidConfigException;

class ChatController extends BaseController
{
    /**
     * 按员工查询与客户的会话列表
     */
    public function getCustomerConversationListByStaff(ServerRequestInterface $request): ResponseInterface
    {
        $currentCorp = $request->getAttribute(CorpModel::class);
        $currentUserInfo = $request->getAttribute(Authentication::class);

        $params = $request->getQueryParams();
        $page = max($params['page'] ?? 1, 1);
        $size = max($params['size'] ?? 10, 1);
        $staffUserid = $params['staff_userid'] ?? '';

        $result = ChatSessionService::getCustomerConversationListByStaff($page, $size, $currentCorp,$currentUserInfo, $staffUserid, $params);

        return $this->jsonResponse($result);
    }

    /**
     * 按收藏查询与客户的会话列表
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws Throwable
     * @throws Exception
     * @throws InvalidConfigException
     */
    public function getCustomerConversationListByCollect(ServerRequestInterface $request): ResponseInterface
    {
        $currentCorp = $request->getAttribute(CorpModel::class);

        $params = $request->getQueryParams();
        $page = max($params['page'] ?? 1, 1);
        $size = max($params['size'] ?? 10, 1);

        $result = ChatSessionService::getCustomerConversationListByCollect($page, $size, $currentCorp, $params);

        return $this->jsonResponse($result);
    }

    /**
     * 按员工查询与员工的会话列表
     */
    public function getStaffConversationListByStaff(ServerRequestInterface $request): ResponseInterface
    {
        $currentCorp = $request->getAttribute(CorpModel::class);

        $params = $request->getQueryParams();
        $page = max($params['page'] ?? 1, 1);
        $size = max($params['size'] ?? 10, 1);
        $staffUserid = $params['staff_userid'] ?? '';

        $result = ChatSessionService::getStaffConversationListByStaff($page, $size, $currentCorp, $staffUserid, $params);

        return $this->jsonResponse($result);
    }

    /**
     * 按员工查询与群的会话列表
     */
    public function getRoomConversationListByStaff(ServerRequestInterface $request): ResponseInterface
    {
        $currentCorp = $request->getAttribute(CorpModel::class);

        $params = $request->getQueryParams();
        $page = max($params['page'] ?? 1, 1);
        $size = max($params['size'] ?? 10, 1);
        $staffUserid = $params['staff_userid'] ?? '';

        $result = ChatSessionService::getRoomConversationListByStaff($page, $size, $currentCorp, $staffUserid, $params);

        return $this->jsonResponse($result);
    }

    /**
     * 按收藏查询与群的会话列表
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws Throwable
     */
    public function getRoomConversationListByCollect(ServerRequestInterface $request): ResponseInterface
    {
        $currentCorp = $request->getAttribute(CorpModel::class);

        $params = $request->getQueryParams();
        $page = max($params['page'] ?? 1, 1);
        $size = max($params['size'] ?? 10, 1);

        $result = ChatSessionService::getRoomConversationListByCollect($page, $size, $currentCorp, $params);

        return $this->jsonResponse($result);
    }

    /**
     * 加入收藏
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws Throwable
     */
    public function joinCollect(ServerRequestInterface $request): ResponseInterface
    {
        $currentCorp = $request->getAttribute(CorpModel::class);

        $collectDTO = new CollectDTO($request->getParsedBody());

        $result = ChatSessionService::joinCollect($collectDTO);

        return $this->jsonResponse($result);
    }

    /**
     * 取消收藏
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws Throwable
     */
    public function cancelCollect(ServerRequestInterface $request): ResponseInterface
    {
        $currentCorp = $request->getAttribute(CorpModel::class);

        $collectDTO = new CollectDTO($request->getParsedBody());

        $result = ChatSessionService::cancelCollect($collectDTO);

        return $this->jsonResponse($result);
    }

    /**
     * 按客户查询与员工的会话列表
     */
    public function getStaffConversationListByCustomer(ServerRequestInterface $request): ResponseInterface
    {
        $currentCorp = $request->getAttribute(CorpModel::class);

        $params = $request->getQueryParams();
        $page = max($params['page'] ?? 1, 1);
        $size = max($params['size'] ?? 10, 1);
        $externalUserid = $params['external_userid'] ?? '';

        $result = ChatSessionService::getStaffConversationListByCustomer($page, $size, $currentCorp, $externalUserid, $params);

        return $this->jsonResponse($result);
    }

    /**
     * 根据会话获取聊天内容
     */
    public function getMessageListByConversation(ServerRequestInterface $request): ResponseInterface
    {
        $currentCorp = $request->getAttribute(CorpModel::class);

        $currentUserInfo = $request->getAttribute(Authentication::class);

        $params = $request->getQueryParams();
        $page = max($params['page'] ?? 1, 1);
        $size = max($params['size'] ?? 10, 1);
        $conversationId = ($params['conversation_id'] ?? '');
        $params["users_id"] = $currentUserInfo->get("id");

        $result = ChatSessionService::getMessageListByConversation($page, $size, $currentCorp, $conversationId, $params);

        return $this->jsonResponse($result);
    }

    /**
     * 根据群聊获取聊天内容
     */
    public function getMessageListByGroup(ServerRequestInterface $request): ResponseInterface
    {
        $currentCorp = $request->getAttribute(CorpModel::class);

        $params = $request->getQueryParams();
        $page = max($params['page'] ?? 1, 1);
        $size = max($params['size'] ?? 10, 1);
        $groupChatId = ($params['group_chat_id'] ?? '');

        $result = ChatSessionService::getMessageListByGroup($page, $size, $currentCorp, $groupChatId, $params);

        return $this->jsonResponse($result);
    }


    /**
     * @param ServerRequestInterface $request
     * Notes: 会话搜索
     * User: rand
     * Date: 2024/11/11 12:03
     * @return ResponseInterface
     * @throws Throwable
     */
    public function getSearch(ServerRequestInterface $request): ResponseInterface
    {
        $currentUserInfo = $request->getAttribute(Authentication::class);

        $res = ChatSessionService::getSearch($currentUserInfo, $request->getQueryParams());

        return $this->jsonResponse($res);
    }


    /**
     * @param ServerRequestInterface $request
     * Notes: 获取会话存档展示配置
     * User: rand
     * Date: 2024/12/10 16:40
     * @return ResponseInterface
     * @throws Throwable
     */
    public function getChatConfigInfo(ServerRequestInterface $request): ResponseInterface
    {
        $currentCorp = $request->getAttribute(CorpModel::class);

        $res = ChatSessionService::getChatConfigInfo($currentCorp);

        return $this->jsonResponse($res);
    }

    /**
     * @param ServerRequestInterface $request
     * Notes: 设置会话存档展示配置
     * User: rand
     * Date: 2024/12/10 16:52
     * @return ResponseInterface
     * @throws Throwable
     */
    public function saveChatConfig(ServerRequestInterface $request): ResponseInterface
    {
        $currentCorp = $request->getAttribute(CorpModel::class);

        ChatSessionService::saveChatConfig($currentCorp, $request->getParsedBody());

        return $this->jsonResponse();
    }

    /**
     * @param ServerRequestInterface $request
     * Notes: 获取会话导出列表
     * User: rand
     * Date: 2025/8/11 18:05
     * @return ResponseInterface
     * @throws Throwable
     */
    public function getExportTaskList(ServerRequestInterface $request): ResponseInterface
    {
        $currentCorp = $request->getAttribute(CorpModel::class);
        $params = $request->getQueryParams();


        $page = max($params['page'] ?? 1, 1);
        $size = max($params['size'] ?? 10, 1);

        $result = ChatSessionService::getExportTaskList($currentCorp, $page, $size);

        return $this->jsonResponse($result);

    }

    /**
     * @param ServerRequestInterface $request
     * Notes: 变更导出任务状态
     * User: rand
     * Date: 2025/8/11 18:27
     * @return \Yiisoft\DataResponse\DataResponse
     */
    public function changeExportTaskState(ServerRequestInterface $request)
    {
        $currentCorp = $request->getAttribute(CorpModel::class);
        $params = $request->getParsedBody();

        $task_id = $params['task_id'] ?? 0;
        $state = $params['state'] ?? 0;

        ChatSessionService::changeExportTaskState($currentCorp, $task_id, $state);

        return $this->jsonResponse();

    }

    /**
     * @param ServerRequestInterface $request
     * Notes: 会话消息导出
     * User: rand
     * Date: 2025/8/8 15:47
     * @return ResponseInterface
     * @throws Throwable
     */
    public function getMessageExport(ServerRequestInterface $request): ResponseInterface
    {

        $currentCorp = $request->getAttribute(CorpModel::class);
        $currentUserInfo = $request->getAttribute(Authentication::class);


        $params = $request->getQueryParams();
        $conversationId = $params['conversation_id'] ?? '';
        $group_chat_id = $params['group_chat_id'] ?? '';

        $conversation_info = ChatSessionService::getConversationInfo($conversationId, $group_chat_id);

        $res = ChatMsgExportTaskModel::create([
            "corp_id" => $currentCorp->get('id'),
            "req_data" => [
                "conversation_id" => $conversationId,
                "group_chat_id" => $group_chat_id,
                "msg_start_time" => $params["msg_start_time"] ?? "",
                "msg_end_time" => $params["msg_end_time"] ?? "",
            ],
            "create_userid" => $currentUserInfo->get("userid"),
            "task_title" => ($conversation_info["task_title"] ?? "") . "_" . date("Ymd", time()),
        ]);
        $task_info = $res->toArray();

        Producer::dispatch(ChatMsgExportConsumer::class, ['corp' => $currentCorp, 'taskId' => $task_info["id"]]);

        return $this->jsonResponse();
    }


}
