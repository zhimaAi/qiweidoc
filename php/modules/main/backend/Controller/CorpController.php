<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\Main\Controller;

use Common\Controller\BaseController;
use Exception;
use LogicException;
use Modules\Main\DTO\Corp\InitCorpInfoBaseDTO;
use Modules\Main\DTO\Corp\SaveCallbackEventTokenBaseDTO;
use Modules\Main\DTO\Corp\SaveCorpInfoBaseDTO;
use Modules\Main\DTO\Corp\UpdateCorpConfigBaseDTO;
use Modules\Main\Model\CorpModel;
use Modules\Main\Service\CorpService;
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
     * @throws Throwable
     */
    public function initCorpInfo(ServerRequestInterface $request): ResponseInterface
    {
        $initCorpInfoDTO = new InitCorpInfoBaseDTO($request->getParsedBody());

        CorpService::saveBasicCorpInfo($initCorpInfoDTO);

        return $this->jsonResponse();
    }

    /**
     * 获取最后一个企业的名称和logo
     * @return DataResponse
     * @throws Throwable
     */
    public function getBaseNameAndLogo()
    {
        $result= CorpService::getBaseNameAndLogo();

        return $this->jsonResponse($result);
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

        $saveCorpInfoDTO = new SaveCorpInfoBaseDTO($request->getParsedBody());

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

        $updateCorpConfigDTO = new UpdateCorpConfigBaseDTO($request->getParsedBody());

        CorpService::updateConfig($currentCorpInfo, $updateCorpConfigDTO);

        return $this->jsonResponse();
    }

    /**
     * 生成回调事件token
     *
     * @throws Exception
     */
    public function generateCallbackEventToken()
    {
        return $this->jsonResponse([
            'token' => random62(32),
            'aes_key' => random62(43),
        ]);
    }

    /**
     * 保存回调事件token
     */
    public function saveCallbackEventToken(ServerRequestInterface $request): ResponseInterface
    {
        $corp = $request->getAttribute(CorpModel::class);

        $dto = new SaveCallbackEventTokenBaseDTO($request->getParsedBody());

        CorpService::saveCallbackEventToken($corp, $dto);

        return $this->jsonResponse();
    }

    /**
     * 保存企业名称和logo
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws Throwable
     */
    public function saveNameOrLogo(ServerRequestInterface $request): ResponseInterface
    {
        $corp = $request->getAttribute(CorpModel::class);

        $dto = new UpdateCorpConfigBaseDTO($request->getParsedBody());

        CorpService::SaveNameOrLogo($corp, $dto);

        return $this->jsonResponse();
    }

    /**
     * 上传logo 返回链接
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function uploadLogo(ServerRequestInterface $request): ResponseInterface
    {
        $files = $request->getUploadedFiles();
        if (count($files) == 0) {
            throw new LogicException('请上传文件');
        }

        $url = CorpService::uploadLogo($files['file']);

        return $this->jsonResponse(['url' => $url]);
    }
}
