<?php

namespace Modules\Main\Controller;

use Common\Controller\BaseController;
use HttpSoft\Message\UploadedFile;
use LogicException;
use Modules\Main\Service\StorageService;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\DataResponse\DataResponse;

class StorageController extends BaseController
{
    public function upload(ServerRequestInterface $request): DataResponse
    {
        $files = $request->getUploadedFiles();
        if (count($files) == 0) {
            throw new LogicException('请上传文件');
        }

        /* @var UploadedFile $file */
        $file = $files['file'];
        $filepath = '/tmp/' . $file->getClientFilename();
        if ($file->getSize() > 1024 * 1024 * 10) {
            throw new LogicException('文件不能超过10M');
        }

        $forbid = ['php', 'exe', 'sh', '.htaccess'];
        if (in_array($file->getClientMediaType(), $forbid)) {
            throw new LogicException('不支持的文件格式');
        }

        $file->moveTo($filepath);
        $storage = StorageService::saveLocal($filepath);

        return $this->jsonResponse($storage);
    }
}
