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
    private const SESSION_OBJECT_LOOKBACK_DAYS = 5; // 与未下载媒体补偿任务的消息时间窗口保持一致。
    private const OBJECT_HASH_READ_SIZE = 1024 * 1024; // 以 1MB 分块校验大文件，避免占满 PHP 内存。

    public static function hasAvailableStorage(string $hash): bool
    {
        return self::findAvailableStorage($hash) !== null;
    }

    private static function findAvailableStorage(string $hash): ?StorageModel
    {
        $storages = StorageModel::query()
            ->where(['hash' => strtolower($hash)])
            ->orderBy(['id' => SORT_DESC])
            ->getAll();

        foreach ($storages as $storage) {
            /** @var StorageModel $storage */
            if (!$storage->get('is_deleted_local')) {
                return $storage;
            }
            if (!empty($storage->get('cloud_storage_object_key'))
                && CloudStorageSettingModel::query()->where(['id' => $storage->get('cloud_storage_setting_id')])->exists()
            ) {
                return $storage;
            }
        }

        return null;
    }

    /**
     * 生成对象键
     */
    public static function generateObjectKey(string $fileName, string $md5): string
    {
        $now = Carbon::now();
        return sprintf("%d/%02d/%02d/%s/%s", $now->year, $now->month, $now->day, $md5, basename($fileName));
    }

    /**
     * 查找已上传完成但尚未写入 storage 表的会话文件。
     *
     * 这里只通过 S3 API 扫描完整对象，未完成的 multipart 分片不会出现在 ListObjectsV2 结果中，
     * 因而不会被误登记为可下载文件。查找过程中发生 MinIO 异常时主动向上抛出，避免把
     * “无法确认”误判为“不存在”，进而再次从企业微信下载大文件。
     *
     * @return array{object_key: string, file_size: int, mime_type: string}|null
     * @throws Throwable
     */
    public static function findRecoverableSessionObject(string $md5, int $expectedSize): ?array
    {
        // 缺少可靠的 MD5 或文件大小时无法验证对象完整性，宁可重新下载也不回填错误记录。
        if (!is_md5($md5) || $expectedSize <= 0) {
            return null;
        }

        $s3Client = self::getLocalS3Client();
        $now = Carbon::now();

        // 对象 Key 含上传日期，补偿任务只扫描最近五天消息，因此逐日扫描相同时间窗口。
        for ($daysAgo = 0; $daysAgo <= self::SESSION_OBJECT_LOOKBACK_DAYS; $daysAgo++) {
            $date = $now->copy()->subDays($daysAgo);
            $prefix = sprintf('%d/%02d/%02d/%s/', $date->year, $date->month, $date->day, strtolower($md5));
            $continuationToken = null;

            do {
                $params = [
                    'Bucket' => StorageModel::SESSION_BUCKET,
                    'Prefix' => $prefix,
                ];
                // 支持同一 MD5 产生超过一页重复对象，避免漏掉后续页中的有效文件。
                if ($continuationToken !== null) {
                    $params['ContinuationToken'] = $continuationToken;
                }

                $result = $s3Client->listObjectsV2($params);
                foreach ($result['Contents'] ?? [] as $object) {
                    $objectKey = (string) ($object['Key'] ?? '');
                    $objectSize = (int) ($object['Size'] ?? 0);

                    // 先用对象大小做低成本过滤，避免对明显不完整的 2GB 对象执行全量读取。
                    if ($objectKey === '' || $objectSize !== $expectedSize) {
                        continue;
                    }

                    // multipart ETag 不是文件 MD5，必须流式读取对象并计算真实 MD5 后才能回填。
                    if (!self::objectMd5Matches($s3Client, $objectKey, strtolower($md5))) {
                        continue;
                    }

                    // HeadObject 用于取得可信的对象大小和 MIME 信息，供 storage 表完整落库。
                    $head = $s3Client->headObject([
                        'Bucket' => StorageModel::SESSION_BUCKET,
                        'Key' => $objectKey,
                    ]);
                    if ((int) ($head['ContentLength'] ?? 0) !== $expectedSize) {
                        continue;
                    }

                    return [
                        'object_key' => $objectKey,
                        'file_size' => $expectedSize,
                        'mime_type' => (string) ($head['ContentType'] ?? ''),
                    ];
                }

                $continuationToken = !empty($result['IsTruncated'])
                    ? (string) ($result['NextContinuationToken'] ?? '')
                    : null;
                if ($continuationToken === '') {
                    $continuationToken = null;
                }
            } while ($continuationToken !== null);
        }

        return null;
    }

    /**
     * 流式计算 MinIO 对象 MD5，校验 2GB 等大文件时不会一次性载入 PHP 内存。
     *
     * @throws Throwable
     */
    private static function objectMd5Matches(S3Client $s3Client, string $objectKey, string $expectedMd5): bool
    {
        $result = $s3Client->getObject([
            'Bucket' => StorageModel::SESSION_BUCKET,
            'Key' => $objectKey,
        ]);
        $body = $result['Body'];
        $hashContext = hash_init('md5');

        // PSR-7 流按固定块读取，既控制内存，也确保完整对象的每个字节都参与校验。
        while (!$body->eof()) {
            $chunk = $body->read(self::OBJECT_HASH_READ_SIZE);
            if ($chunk === '') {
                // 阻塞式 S3 流在未结束时不应返回空数据，直接失败可避免异常连接导致 CPU 空转。
                throw new Exception("读取MinIO对象内容失败：{$objectKey}");
            }
            hash_update($hashContext, $chunk);
        }

        return hash_equals($expectedMd5, hash_final($hashContext));
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

        Yii::logger()->info("开始上传文件到云存储", ['id' => $model->get('id'), 'original_filename' => $model->get('original_filename')]);

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
        $storage = self::findAvailableStorage($hash);
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
     * 根据哈希值下载对象内容
     * 优先从本地 MinIO 读取，若本地文件已删除且存在云端副本，则从云端读取
     *
     * @throws Throwable
     */
    public static function downloadObjectContent(string $hash): string
    {
        $storage = StorageModel::query()->where(['hash' => $hash])->orderBy(['id' => SORT_DESC])->getOne();
        if (empty($storage)) {
            throw new Exception('文件不存在');
        }

        // 读取本地 MinIO
        if (!$storage->get('is_deleted_local')) {
            $s3Client = self::getLocalS3Client();
            $result = $s3Client->getObject([
                'Bucket' => $storage->get('local_storage_bucket'),
                'Key'    => $storage->get('local_storage_object_key'),
            ]);
            return (string) $result['Body'];
        }

        // 读取云端（如已迁移）
        if (!empty($storage->get('cloud_storage_object_key')) && $setting = CloudStorageSettingModel::query()->where(['id' => $storage->get('cloud_storage_setting_id')])->getOne()) {
            /* @var CloudStorageSettingModel $setting */
            $s3Client = self::getCloudS3Client($setting);
            $result = $s3Client->getObject([
                'Bucket' => $setting->get('bucket'),
                'Key'    => $storage->get('cloud_storage_object_key'),
            ]);
            return (string) $result['Body'];
        }

        throw new Exception('文件内容不存在或已被清理');
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
