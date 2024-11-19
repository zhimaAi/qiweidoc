<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace App\Controller;

use App\DTO\Corp\InitCorpInfoDTO;
use App\DTO\Corp\SaveCorpInfoDTO;
use App\DTO\Corp\UpdateCorpConfigDTO;
use App\Libraries\Core\Http\BaseController;
use App\Models\CorpModel;
use App\Services\CorpService;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use Yiisoft\DataResponse\DataResponse;

class CorpController extends BaseController
{
    /**
     * 检查是否已初始化
     */
    public function checkInit(): ResponseInterface
    {
        $corpInfo = CorpModel::query()->getOne();

        return $this->jsonResponse([
            'init' => (bool) $corpInfo,
            'corp_id' => $corpInfo?->get('id'),
            'agent_id' => $corpInfo?->get('agent_id'),
        ]);
    }

    /**
     * 企业基础信息保存
     *
     * @throws Exception
     */
    public function initCorpInfo(ServerRequestInterface $request): ResponseInterface
    {
        $initCorpInfoDTO = new InitCorpInfoDTO($request->getParsedBody());

        CorpService::saveBasicCorpInfo($initCorpInfoDTO);

        return $this->jsonResponse();
    }

    /**
     * 获取当前企业信息
     */
    public function getCurrentCorpInfo(ServerRequestInterface $request): ResponseInterface
    {
        return $this->jsonResponse($request->getAttribute(CorpModel::class));
    }

    /**
     * Notes: 获取会话存档密钥
     * User: rand
     * Date: 2024/11/1 10:27
     * @return DataResponse
     */
    public function getSessionPublicKey(ServerRequestInterface $request): ResponseInterface
    {
        // 生成密钥
        $rsaData = CorpService::createRsa();

        return $this->jsonResponse($rsaData);
    }

    /**
     * Notes: 初始化时保存会话存档配置
     * User: rand
     * Date: 2024/10/31 12:06
     * @return ResponseInterface
     * @throws Throwable
     */
    public function saveConfig(ServerRequestInterface $request): ResponseInterface
    {
        $currentCorpInfo = $request->getAttribute(CorpModel::class);

        $saveCorpInfoDTO = new SaveCorpInfoDTO($request->getParsedBody());

        CorpService::saveConfig($currentCorpInfo, $saveCorpInfoDTO);

        return $this->jsonResponse();
    }

    /**
     * 编辑企业信息
     * @throws Throwable
     */
    public function updateConfig(ServerRequestInterface $request): ResponseInterface
    {
        $currentCorpInfo = $request->getAttribute(CorpModel::class);

        $updateCorpConfigDTO = new UpdateCorpConfigDTO($request->getParsedBody());

        CorpService::updateConfig($currentCorpInfo, $updateCorpConfigDTO);

        return $this->jsonResponse();
    }
}
