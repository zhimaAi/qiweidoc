<?php

namespace Modules\Main\Service;

use Aws\S3\S3Client;
use Carbon\Carbon;
use Common\Yii;
use Exception;
use Modules\Main\Model\CloudStorageSettingModel;
use Modules\Main\Model\StorageModel;
use Throwable;

class StorageService
{
    /**
     * 生成对象键
     */
    public static function generateObjectKey(string $fileName, string $md5): string
    {
        $now = Carbon::now();
        return sprintf("%d/%02d/%02d/%s/%s", $now->year, $now->month, $now->day, $md5, basename($fileName));
    }

    public static function getLocalS3Client()
    {
        return new S3Client([
            'version' => 'latest',
            'region' => Yii::params()['local-storage']['region'],
            'endpoint' => Yii::params()['local-storage']['endpoint'],
            'credentials' => [
                'key'    => Yii::params()['local-storage']['access_key'],
                'secret' => Yii::params()['local-storage']['secret_key'],
            ],
            'use_path_style_endpoint' => true,
        ]);
    }

    public static function getCloudS3Client(CloudStorageSettingModel $setting)
    {
        $usePathStyleEndpoint = false;
        if ($setting->get('provider') == 'MinIO') {
            $usePathStyleEndpoint = true;
        }
        return new S3Client([
            'version' => 'latest',
            'region' => $setting->get('region'),
            'endpoint' => $setting->get('endpoint'),
            'credentials' => [
                'key'    => $setting->get('access_key'),
                'secret' => $setting->get('secret_key'),
            ],
            'use_path_style_endpoint' => $usePathStyleEndpoint,
        ]);
    }

    /**
     * 初始化本地存储
     *
     * @throws Exception
     */
    public static function initLocalBucket(): void
    {
        $s3Client = self::getLocalS3Client();
        foreach (StorageModel::LOCAL_BUCKET_LIST as $bucket) {
            if (!$s3Client->doesBucketExist($bucket)) {
                $s3Client->createBucket([
                    'Bucket' => $bucket,
                ]);
            }
            $s3Client->putBucketPolicy([
                'Bucket' => $bucket,
                'Policy' => json_encode([
                    'Version' => '2012-10-17',
                    'Statement' => [
                        [
                            'Sid' => 'PublicRead',
                            'Effect' => 'Allow',
                            'Principal' => '*',
                            'Action' => ['s3:GetObject'],
                            'Resource' => ["arn:aws:s3:::$bucket/*"],
                        ]
                    ]
                ])
            ]);
        }
    }

    /**
     * 保存文件到本地对象存储
     *
     * @throws Throwable
     */
    public static function saveLocal(string $filePath, string $bucketName = StorageModel::DEFAULT_BUCKET, ?int $preserveSeconds = 0): StorageModel
    {
        if (! file_exists($filePath)) {
            throw new Exception("文件{$filePath}不存在");
        }

        $objectKey = self::generateObjectKey($filePath, md5_file($filePath));

        $s3Client = self::getLocalS3Client();
        $s3Client->putObject([
            'Bucket' => $bucketName,
            'Key' => $objectKey,
            'SourceFile' => $filePath,
        ]);

        $expiredAt = null;
        if ($preserveSeconds > 0) {
            $expiredAt = Carbon::now()->addSeconds($preserveSeconds);
        }

        $result = StorageModel::create([
            'hash'                      => hash_file('md5', $filePath),
            'original_filename'         => basename($filePath),
            'file_extension'            => pathinfo($filePath, PATHINFO_EXTENSION),
            'mime_type'                 => mime_content_type($filePath) ?: "application/octet-stream",
            'file_size'                 => filesize($filePath),
            'local_storage_bucket'      => $bucketName,
            'local_storage_object_key'  => $objectKey,
            'local_storage_expired_at'  => $expiredAt,
        ]);
        @unlink($filePath);

        return $result;
    }

    /**
     * 从本地对象存储删除文件
     *
     * @throws Throwable
     */
    public static function removeExpiredLocalFile(StorageModel $model): void
    {
        if (!$model->get('is_deleted_local') && $model->get('local_storage_expired_at') < now() && !empty($model->get('cloud_storage_setting_id'))) {
            $s3Client = self::getLocalS3Client();
            $s3Client->deleteObject([
                'Bucket' => $model->get('local_storage_bucket'),
                'Key' => $model->get('local_storage_object_key'),
            ]);
            $model->update(['is_deleted_local' => true]);
        }
    }

    /**
     * 保存文件到云存储
     *
     * @throws Throwable
     */
    public static function saveCloud(StorageModel $model): void
    {
        /* @var CloudStorageSettingModel $cloudStorageSetting */
        $cloudStorageSetting = CloudStorageSettingModel::query()->orderBy(['id' => SORT_DESC])->getOne();
        if (empty($cloudStorageSetting)) {
            return;
        }

        // 用mc注册本地配置
        $endpoint = Yii::params()['local-storage']['endpoint'];
        $accessKey = Yii::params()['local-storage']['access_key'];
        $secretKey = Yii::params()['local-storage']['secret_key'];
        $bucket = $model->get('local_storage_bucket');
        $objectKey = $model->get('local_storage_object_key');
        $command = "mc alias set local {$endpoint} {$accessKey} {$secretKey} --path auto";
        exec($command, $output, $return);
        if ($return != 0) {
            throw new Exception($output[0] ?? 'access_key不合法');
        }

        // 用mc注册云存储配置
        $cloudEndpoint = $cloudStorageSetting->get('endpoint');
        $cloudAccessKey = $cloudStorageSetting->get('access_key');
        $cloudSecretKey = $cloudStorageSetting->get('secret_key');
        $cloudBucket = $cloudStorageSetting->get('bucket');
        $cloudObjectKey = self::generateObjectKey($model->get('original_filename'), $model->get('hash'));

        // MinIO特殊配置
        $path = "off";
        if ($cloudStorageSetting->get('provider') == 'MinIO') {
            $path = "auto";
        }
        $command = "mc alias set cloud {$cloudEndpoint} {$cloudAccessKey} $cloudSecretKey --path {$path}";
        exec($command, $output, $return);
        if ($return != 0) {
            throw new Exception($output[0] ?? 'access_key不合法');
        }

        // 利用minio复制本地对象到云存储
        $command = "mc cp 'local/{$bucket}/{$objectKey}' 'cloud/{$cloudBucket}/{$cloudObjectKey}'";
        exec($command, $output, $return);
        if ($return != 0) {
            throw new Exception($output[0] ?? '复制对象失败');
        }

        // 更新数据库
        $model->update([
            'cloud_storage_setting_id' => $cloudStorageSetting->get('id'),
            'cloud_storage_object_key' => $cloudObjectKey,
        ]);
    }

    /**
     * 生成生成对象的下载链接
     * 默认生成本地存储的下载链接
     * 如果本地文件已过期则取云存储的下载链接
     */
    public static function getDownloadUrl(string $hash): string
    {
        $storage = StorageModel::query()->where(['hash' => $hash])->orderBy(['id' => SORT_DESC])->getOne();
        if (empty($storage)) {
            return "";
        }

        if (!$storage->get('is_deleted_local')) { // 本地文件路径特殊处理,直接走内部代理访问
            $s3Client = self::getLocalS3Client();
            $cmd = $s3Client->getCommand('GetObject', [
                'Bucket' => $storage->get('local_storage_bucket'),
                'Key'    => $storage->get('local_storage_object_key'),
            ]);
            $request = $s3Client->createPresignedRequest($cmd, '+1 hour');

            return self::convertMinioUrlToLocalPath($request->getUri());
        } elseif (!empty($storage->get('cloud_storage_object_key')) && $setting = CloudStorageSettingModel::query()->where(['id' => $storage->get('cloud_storage_setting_id')])->getOne()) { // 云存储的话生成预签名地址
            /* @var CloudStorageSettingModel $setting */
            $s3Client = self::getCloudS3Client($setting);
            $cmd = $s3Client->getCommand('GetObject', [
                'Bucket' => $setting->get('bucket'),
                'Key'    => $storage->get('cloud_storage_object_key'),
            ]);
            $request = $s3Client->createPresignedRequest($cmd, '+1 hour');

            return (string) $request->getUri();
        } else {
            return "";
        }
    }

    /**
     * 本地文件特殊处理
     */
    private static function convertMinioUrlToLocalPath($minioUrl): string
    {
        $urlParts = parse_url($minioUrl);
        if ($urlParts === false || !isset($urlParts['path'])) {
            return "";
        }
        $pathParts = explode('/', trim($urlParts['path'], '/'));
        $newPath = '/storage/' . implode('/', $pathParts);
        if (isset($urlParts['query'])) {
            $newPath .= '?' . $urlParts['query'];
        }

        return $newPath;
    }
}
