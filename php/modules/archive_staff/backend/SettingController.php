<?php

namespace Modules\ArchiveStaff;

use Common\Controller\BaseController;
use Common\Micro;
use Common\Yii;
use Psr\Http\Message\ServerRequestInterface;

class SettingController extends BaseController
{
    public function get(ServerRequestInterface $request)
    {
        $settings = SettingModel::query()->getAll();

        $result = [];
        foreach ($settings as $row) {
            $result[$row->get('key')] = $row->get('value');
        }

        return $this->jsonResponse($result);
    }

    public function set(ServerRequestInterface $request)
    {
        $dto = new SettingDTO($request->getParsedBody());

        Yii::db()->transaction(function () use ($dto) {
            foreach ($dto->toArray() as $key => $value) {
                SettingModel::updateOrCreate(['key' => $key], [
                    'key' => $key,
                    'value' => $value,
                ]);
            }
        });
        Micro::call('main', 'check_staff_enable_archive', '');

        return $this->jsonResponse();
    }
}
