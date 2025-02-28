<?php

namespace Modules\CustomBrand;

use Common\Controller\BaseController;
use Modules\Main\Service\StorageService;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\DataResponse\DataResponse;

class SettingController extends BaseController
{
    public function list(): DataResponse
    {
        $settings = SettingModel::query()->getAll();

        $result = [];
        foreach ($settings as $row) {
            $result[$row->get('key')] = $row->get('value');
            if ($row->get('key') == 'logo') {
                $result['logo'] = StorageService::getDownloadUrl($row->get('value'));
            }
        }

        return $this->jsonResponse($result);
    }

    public function store(ServerRequestInterface $request): DataResponse
    {
        $dto = new SettingDto($request->getParsedBody());

        foreach ($dto->toArray() as $key => $value) {
            if ($key == 'logo' && empty($value)) { // logo不传的话不更新
                continue;
            }

            SettingModel::updateOrCreate(['key' => $key], [
                'key' => $key,
                'value' => $value,
            ]);
        }

        return $this->jsonResponse();
    }
}
