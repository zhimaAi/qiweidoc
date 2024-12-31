<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\HintKeywords\Controller;


use Common\Controller\BaseController;
use Modules\HintKeywords\Service\IndexService;
use Modules\Main\Model\CorpModel;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Auth\Middleware\Authentication;

/**
 * @author rand
 * @ClassName TestController
 * @date 2024/12/314:40
 * @description
 */
class IndexController extends BaseController
{

    /**
     * @param ServerRequestInterface $request
     * Notes: 敏感词列表
     * User: rand
     * Date: 2024/12/3 14:55
     * @return ResponseInterface
     * @throws \Throwable
     */
    public function list(ServerRequestInterface $request): ResponseInterface
    {
        $corp = $request->getAttribute(CorpModel::class);

        $res = IndexService::list($corp, $request->getQueryParams());

        return $this->jsonResponse($res);
    }

    /**
     * @param ServerRequestInterface $request
     * Notes: 删除敏感词组
     * User: rand
     * Date: 2024/12/10 11:01
     * @return ResponseInterface
     * @throws \Throwable
     */
    public function delete(ServerRequestInterface $request): ResponseInterface
    {
        $corp = $request->getAttribute(CorpModel::class);

        IndexService::delete($corp, $request->getParsedBody());

        return $this->jsonResponse();
    }

    /**
     * @param ServerRequestInterface $request
     * Notes: 保存敏感词列表
     * User: rand
     * Date: 2024/12/3 14:57
     * @return ResponseInterface
     * @throws \Throwable
     */
    public function save(ServerRequestInterface $request): ResponseInterface
    {
        $corp = $request->getAttribute(CorpModel::class);

        $currentUserInfo = $request->getAttribute(Authentication::class);

        IndexService::save($corp, $currentUserInfo, $request->getParsedBody());

        return $this->jsonResponse();

    }

    /**
     * @param ServerRequestInterface $request
     * Notes: 敏感词规则列表
     * User: rand
     * Date: 2024/12/3 15:13
     * @return ResponseInterface
     */
    public function ruleList(ServerRequestInterface $request): ResponseInterface
    {

        $corp = $request->getAttribute(CorpModel::class);

        $res = IndexService::ruleList($corp, $request->getQueryParams());

        return $this->jsonResponse($res);
    }


    /**
     * @param ServerRequestInterface $request
     * Notes: 敏感词规则列表
     * User: rand
     * Date: 2024/12/3 15:13
     * @return ResponseInterface
     */
    public function ruleStatistic(ServerRequestInterface $request): ResponseInterface
    {

        $corp = $request->getAttribute(CorpModel::class);

        $params = $request->getQueryParams();

        $today = date("Y-m-d", time());

        $res = IndexService::ruleStatistics($corp, $params["id"] ?? 0, $params["start_time"] ?? strtotime($today), strtotime($today) + 86400);

        return $this->jsonResponse($res);
    }

    /**
     * @param ServerRequestInterface $request
     * Notes: 保存敏感词规则
     * User: rand
     * Date: 2024/12/3 16:37
     * @return ResponseInterface
     * @throws \Throwable
     */
    public function ruleSave(ServerRequestInterface $request): ResponseInterface
    {
        $corp = $request->getAttribute(CorpModel::class);

        $currentUserInfo = $request->getAttribute(Authentication::class);

        IndexService::saveRule($corp, $currentUserInfo, $request->getParsedBody());

        return $this->jsonResponse();
    }

    /**
     * @param ServerRequestInterface $request
     * Notes: 更新规则启用状态
     * User: rand
     * Date: 2024/12/6 15:09
     * @return ResponseInterface
     */
    public function changeStatus(ServerRequestInterface $request): ResponseInterface
    {
        $corp = $request->getAttribute(CorpModel::class);

        IndexService::changeStatus($corp, $request->getParsedBody());

        return $this->jsonResponse();
    }

    /**
     * @param ServerRequestInterface $request
     * Notes: 获取规则明细
     * User: rand
     * Date: 2024/12/3 17:30
     * @return ResponseInterface
     * @throws \Throwable
     */
    public function ruleInfo(ServerRequestInterface $request): ResponseInterface
    {
        $corp = $request->getAttribute(CorpModel::class);

        $params = $request->getQueryParams();

        $res = IndexService::ruleInfo($corp, $params["id"] ?? 0);

        return $this->jsonResponse($res);
    }

    /**
     * @param ServerRequestInterface $request
     * Notes: 敏感词规则命中明细
     * User: rand
     * Date: 2024/12/3 17:31
     * @return ResponseInterface
     */
    public function ruleDetail(ServerRequestInterface $request): ResponseInterface
    {
        $corp = $request->getAttribute(CorpModel::class);

        $params = $request->getQueryParams();

        $res = IndexService::ruleDetail($corp, $params);

        return $this->jsonResponse($res);
    }

    /**
     * @param ServerRequestInterface $request
     * Notes: 删除敏感词规则
     * User: rand
     * Date: 2024/12/10 11:03
     * @return ResponseInterface
     */
    public function ruleDelete(ServerRequestInterface $request): ResponseInterface
    {
        $corp = $request->getAttribute(CorpModel::class);

        $params = $request->getParsedBody();

        IndexService::ruleDelete($corp, $params);

        return $this->jsonResponse();
    }

    /**
     * @param ServerRequestInterface $request
     * Notes: 获取通知配置详情
     * User: rand
     * Date: 2024/12/5 10:36
     * @return ResponseInterface
     */
    public function noticeInfo(ServerRequestInterface $request): ResponseInterface
    {
        $corp = $request->getAttribute(CorpModel::class);

        $res = IndexService::noticeInfo($corp);

        return $this->jsonResponse($res);
    }


    /**
     * @param ServerRequestInterface $request
     * Notes: 通知规则保存
     * User: rand
     * Date: 2024/12/5 10:35
     * @return ResponseInterface
     */
    public function noticeSave(ServerRequestInterface $request): ResponseInterface
    {
        $corp = $request->getAttribute(CorpModel::class);

        IndexService::noticeSave($corp, $request->getParsedBody());

        return $this->jsonResponse();
    }


}
