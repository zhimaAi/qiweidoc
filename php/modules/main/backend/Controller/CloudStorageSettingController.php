<?php

namespace Modules\Main\Controller;

use Aws\Middleware;
use Common\Controller\BaseController;
use Common\Yii;
use Exception;
use GuzzleHttp\Psr7\Utils;
use LogicException;
use Modules\Main\DTO\CloudStorageSettingDTO;
use Modules\Main\Enum\EnumUserRoleType;
use Modules\Main\Model\CloudStorageSettingModel;
use Modules\Main\Model\SettingModel;
use Modules\Main\Model\UserModel;
use Modules\Main\Service\AuthService;
use Modules\Main\Service\StorageService;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use Yiisoft\Auth\Middleware\Authentication;
use Yiisoft\DataResponse\DataResponse;
use Yiisoft\Router\RouteCollectionInterface;

class CloudStorageSettingController extends BaseController
{
    public function show(ServerRequestInterface $request, RouteCollectionInterface $collection)
    {
        $result = CloudStorageSettingModel::query()->orderBy(['id' => SORT_DESC])->getOne()?->toArray() ?: [];
        $result['local_session_file_retention_days'] = (int)SettingModel::getValue('local_session_file_retention_days') ?: 0;

        return $this->jsonResponse($result);
    }

    public function save(ServerRequestInterface $request)
    {
        /** @var UserModel $user */
        $user = $request->getAttribute(Authentication::class);
        if ($user->get('role_id') != EnumUserRoleType::SUPPER_ADMIN->value) {
            throw new LogicException("没有权限");
        }

        $dto = new CloudStorageSettingDTO($request->getParsedBody());

        try {
            Yii::db()->transaction(function () use ($dto) {
                $setting = CloudStorageSettingModel::query()->where([
                    'provider' => $dto->get('provider'),
                    'region' => $dto->get('region'),
                    'bucket' => $dto->get('bucket'),
                ])->getOne();

                if (empty($setting)) {
                    $setting = CloudStorageSettingModel::create($dto->toArray());
                } else {
                    $setting->update([
                        'endpoint' => $dto->get('endpoint'),
                        'access_key' => $dto->get('access_key'),
                        'secret_key' => $dto->get('secret_key'),
                    ]);
                }

                $s3Client = StorageService::getCloudS3Client($setting);
                $exists = $s3Client->doesBucketExist($setting->get('bucket'));
                if (!$exists) {
                    throw new Exception("bucket不存在或密钥不正确");
                }

                // MinIO 不需要设置cors
                if ($setting->get('provider') != 'MinIO') {
                    $s3Client->getHandlerList()->appendBuild(
                        Middleware::mapRequest(static function ($request) {
                            $body = $request->getBody();
                            $contentMd5 = base64_encode(Utils::hash($body, 'md5', true));
                            return $request->withHeader('Content-MD5', $contentMd5);
                        })
                    );
                    $s3Client->putBucketCors([
                        'Bucket' => $dto->get('bucket'),
                        'CORSConfiguration' => [
                            'CORSRules' => [
                                [
                                    'AllowedHeaders' => ['*'],
                                    'AllowedMethods' => ['GET'],
                                    'AllowedOrigins' => [AuthService::getLoginDomain()],
                                    'ExposeHeaders' => ['ETag'],
                                    'MaxAgeSeconds' => 3000
                                ]
                            ]
                        ],
                    ]);
                }
            });
        } catch (Throwable $e) {
            throw new LogicException("检测失败: " . $e->getMessage());
        }

        SettingModel::setValue('local_session_file_retention_days', $dto->get('local_session_file_retention_days'));

        return $this->jsonResponse();
    }

    /**
     * 获取云存储提供商地区配置
     */
    public function getStorageProviderRegionList(): DataResponse
    {
        $result = CloudStorageSettingDTO::getProviderRegionList();

        return $this->jsonResponse($result);
    }
}
