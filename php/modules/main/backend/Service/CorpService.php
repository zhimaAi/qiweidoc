<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\Main\Service;

use Common\Yii;
use HttpSoft\Message\UploadedFile;
use LogicException;
use Modules\Main\DTO\Corp\InitCorpInfoBaseDTO;
use Modules\Main\DTO\Corp\SaveCallbackEventTokenBaseDTO;
use Modules\Main\DTO\Corp\SaveCorpInfoBaseDTO;
use Modules\Main\DTO\Corp\UpdateCorpConfigBaseDTO;
use Modules\Main\Model\CorpModel;
use Modules\Main\Model\StorageModel;
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
     * 获取基础信息
     * @return array|string[]
     * @throws Throwable
     */
    public static function getBaseNameAndLogo()
    {
        //获取最后一个登录的企业名和logo
        $corp = CorpModel::query()->orderBy(['id' => SORT_DESC])->getOne();
        $result= [
            'corp_name'=>'',
            'corp_logo'=>'',
        ];
        if (!empty($corp)){
            $result= [
                'corp_name'=>$corp->get('corp_name'),
                'corp_logo'=>$corp->get('corp_logo'),
            ];
        }
        return $result;
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
        } catch (Throwable $e) {
            throw new LogicException('会话密钥不正确: ' . $e->getMessage());
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
        if (!empty($dto->corpName)) {
            $corpModel->set('corp_name', $dto->corpName);
        }
        if (!empty($dto->corpLogo)) {
            $corpModel->set('corp_logo', $dto->corpLogo);
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

    /**
     * 更新企业名称和logo
     * @param CorpModel $corp
     * @param UpdateCorpConfigBaseDTO $dto
     * @return array
     * @throws Throwable
     */
    public static function SaveNameOrLogo(CorpModel $corp, UpdateCorpConfigBaseDTO $dto): array
    {
        $data = [];

        if (!empty($dto->corpName)) {
            $data['corp_name'] = $dto->corpName;
        }
        if (!empty($dto->corpLogo)) {
            $data['corp_logo'] = $dto->corpLogo;
        }

        $corp->update($data);

        return $data;
    }

    /**
     * @throws Throwable
     */
    public static function uploadLogo(UploadedFile $file): string
    {
        $filepath = '/tmp/' . $file->getClientFilename();
        if ($file->getSize() > 1024 * 1024 * 50) {
            throw new LogicException('文件不能超过50M');
        }
        if (stripos($file->getClientMediaType(), 'jpeg') === false && stripos($file->getClientMediaType(), 'png') === false) {
            throw new LogicException('请上传jpg或png图片logo');
        }

        $file->moveTo($filepath);
        $storage = StorageService::saveLocal($filepath);
        return $storage->get('hash');
    }

    public static function saveCallbackEventToken(CorpModel $corp, SaveCallbackEventTokenBaseDTO $dto)
    {
        $corp->update([
            'callback_event_token' => $dto->callbackEventToken,
            'callback_event_aes_key' => $dto->callbackEventAesKey,
        ]);
    }
}
