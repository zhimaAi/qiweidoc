<?php

namespace Modules\Main\Controller;

use Common\Controller\BaseController;
use RuntimeException;
use Yiisoft\DataResponse\DataResponse;

class SystemController extends BaseController
{
    public function getDiskUsage(): DataResponse
    {
        // /var/www 是宿主机项目目录的挂载点，df 返回其所在宿主机分区的实时数据。
        $path = '/var/www';
        $output = [];
        $returnCode = 0;
        exec('df -P -B1 -- ' . escapeshellarg($path), $output, $returnCode);

        if ($returnCode !== 0 || count($output) < 2) {
            throw new RuntimeException('获取宿主机磁盘信息失败');
        }

        $fields = preg_split('/\s+/', trim($output[count($output) - 1]), 6);
        if (count($fields) < 5 || (int) $fields[1] <= 0) {
            throw new RuntimeException('宿主机磁盘信息格式异常');
        }

        $totalBytes = (int) $fields[1];
        $usedBytes = (int) $fields[2];
        $freeBytes = (int) $fields[3];

        return $this->jsonResponse([
            'total_bytes' => $totalBytes,
            'used_bytes' => $usedBytes,
            'free_bytes' => $freeBytes,
            'usage_percent' => round($usedBytes / $totalBytes * 100, 1),
            'free_percent' => round($freeBytes / $totalBytes * 100, 1),
            'mount_path' => $path,
            'checked_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
