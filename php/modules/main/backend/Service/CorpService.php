<?php
// Copyright © 2016- 2024 Sesame Network Technology all right reserved

namespace Modules\Main\Service;

use Common\Yii;
use LogicException;
use Modules\Main\DTO\Corp\InitCorpInfoBaseDTO;
use Modules\Main\DTO\Corp\SaveCallbackEventTokenBaseDTO;
use Modules\Main\DTO\Corp\SaveCorpInfoBaseDTO;
use Modules\Main\DTO\Corp\UpdateCorpConfigBaseDTO;
use Modules\Main\Model\CorpModel;
use Throwable;

class CorpService
{
    /**
     * @throws Throwable
     */
    public static function saveBasicCorpInfo(InitCorpInfoBaseDTO $corpInfoDTO): void
    {
        // 把验证信息存入缓存
        Yii::cache()->psr()->set($corpInfoDTO->verifyDomainFileName, $corpInfoDTO->verifyDomainFileContent, 60 * 3600);

        // 创建企业信息
        if (CorpModel::query()->where(['id' => (string) $corpInfoDTO->corpId])->getOne()) {
            return;
        }

        $corpModel = new CorpModel();
        $corpModel->set('id', $corpInfoDTO->corpId);
        $corpModel->set('agent_id', $corpInfoDTO->agentId);
        $corpModel->set('agent_secret', $corpInfoDTO->secret);

        // 检查企业id和密钥是否正确
        try {
            $corpModel->getWechatApi('/cgi-bin/get_api_domain_ip');
        } catch (Throwable $e) {
            if ($e->getCode() == 60020) {
                throw new LogicException("您的ip不在可信名单内");
            } else {
                Yii::logger()->warning($e);

                throw new LogicException("企业信息验证不通过");
            }
        }

        // 检查应用id是否正确
        try {
            $corpModel->getWechatApi('/cgi-bin/agent/get', ['agentid' => $corpInfoDTO->agentId]);
        } catch (Throwable $e) {
            Yii::logger()->warning($e);

            throw new LogicException("应用id不正确");
        }

        $corpModel->saveOrFail();
    }


    /**
     * Notes: 获取RSA加密密钥对
     * User: rand
     * Date: 2024/10/31 11:40
     * @return array
     */
    public static function createRsa(): array
    {
        $res = openssl_pkey_new(['private_key_bits' => 2048]);

        // 提取私钥
        openssl_pkey_export($res, $privateKey);

        // 提取公钥
        $publicKey = openssl_pkey_get_details($res);

        if (empty($publicKey['key']) && empty($privateKey)) {
            throw new LogicException("生成密钥错误");
        }

        return ["public_key" => $publicKey['key'] ?? "", "private_key" => $privateKey];
    }

    /**
     * @param CorpModel $corpModel
     * @param SaveCorpInfoBaseDTO $dto
     * Notes: 保存会话存档配置
     * User: rand
     * Date: 2024/11/1 12:23
     * @return void
     * @throws Throwable
     */
    public static function saveConfig(CorpModel $corpModel, SaveCorpInfoBaseDTO $dto): void
    {
        $corpModel->set('chat_public_key', $dto->chatPublicKey);
        $corpModel->set('chat_private_key', $dto->chatPrivateKey);
        $corpModel->set('chat_secret', $dto->chatSecret);
        $corpModel->set('chat_public_key_version', $dto->chatPublicKeyVersion);

        try {
            $corpModel->getWechatApi("cgi-bin/msgaudit/get_permit_user_list", [], CorpModel::SecretTypeChat);
        } catch (Throwable) {
            throw new LogicException('会话密钥不正确');
        }

        $corpModel->saveOrFail();
    }

    /**
     * 编辑企业信息
     * @throws Throwable
     */
    public static function updateConfig(CorpModel $corpModel, UpdateCorpConfigBaseDTO $dto): void
    {
        if (!empty($dto->agentSecret)) {
            $corpModel->set('agent_secret', $dto->agentSecret);
        }
        if (!empty($dto->chatSecret)) {
            $corpModel->set('chat_secret', $dto->chatSecret);
        }
        if (!empty($dto->chatPublicKeyVersion)) {
            $corpModel->set('chat_public_key_version', $dto->chatPublicKeyVersion);
        }
        if (!empty($dto->callbackEventToken)) {
            $corpModel->set('callback_event_token', $dto->callbackEventToken);
        }
        if (!empty($dto->callbackEventAesKey)) {
            $corpModel->set('callback_event_aes_key', $dto->callbackEventAesKey);
        }

        // 检查应用密钥是否正确
        try {
            $corpModel->getWechatApi('/cgi-bin/get_api_domain_ip');
        } catch (Throwable $e) {
            if ($e->getCode() == 60020) {
                throw new LogicException("您的ip不在可信名单内");
            } else {
                throw new LogicException("应用密钥不正确");
            }
        }

        // 检查会话密钥是否正确
        try {
            $corpModel->getWechatApi("cgi-bin/msgaudit/get_permit_user_list", [], CorpModel::SecretTypeChat);
        } catch (Throwable) {
            throw new LogicException('会话密钥不正确');
        }

        $corpModel->saveOrFail();
    }

    public static function saveCallbackEventToken(CorpModel $corp, SaveCallbackEventTokenBaseDTO $dto)
    {
        $corp->update([
            'callback_event_token' => $dto->callbackEventToken,
            'callback_event_aes_key' => $dto->callbackEventAesKey,
        ]);
    }
}
