<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\Main\Controller;

use Common\Controller\BaseController;
use Common\Micro;
use Common\Module;
use Common\Yii;
use LogicException;
use Modules\Main\DTO\UpdateArchiveStaffDTO;
use Modules\Main\Enum\EnumUserRoleType;
use Modules\Main\Model\CorpModel;
use Modules\Main\Model\StaffModel;
use Modules\Main\Service\StaffService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Auth\Middleware\Authentication;

/**
 * @author rand
 * @ClassName StaffController
 * @date 2024/11/116:05
 * @description
 */
class StaffController extends BaseController
{

    /**
     * @param ServerRequestInterface $request
     * Notes: 员工列表
     * User: rand
     * Date: 2024/11/7 18:32
     * @return ResponseInterface
     */
    public function list(ServerRequestInterface $request): ResponseInterface
    {
        $corp = $request->getAttribute(CorpModel::class);

        $res = StaffService::list($corp, $request->getQueryParams());

        return $this->jsonResponse($res);
    }

    /**
     * 获取可配置的最大存档员工数量
     */
    public function getMaxStaffArchiveNum(): ResponseInterface
    {
        return $this->jsonResponse(['num' => $this->getMaxStaffArchiveNumFromMicro()]);
    }

    /**
     * 设置存档员工
     */
    public function updateArchiveStaffEnable(ServerRequestInterface $request): ResponseInterface
    {
        $corp = $request->getAttribute(CorpModel::class);

        $body = $request->getParsedBody();
        $dto = new UpdateArchiveStaffDTO($body);

        $maxStaffNum = $this->getMaxStaffArchiveNumFromMicro();
        if (count($dto->get('staff_userid_list')) > $maxStaffNum) {
            throw new LogicException('超出范围了');
        }

        Yii::db()->transaction(function () use ($corp, $dto) {
            StaffModel::query()->where(['corp_id' => $corp->get('id')])->update(['enable_archive' => false]);
            foreach ($dto->get('staff_userid_list') as $staffUserid) {
                StaffModel::query()
                    ->where(['corp_id' => $corp->get('id'), 'userid' => $staffUserid])
                    ->update(['enable_archive' => true]);
            }
        });

        return $this->jsonResponse();
    }

    private function getMaxStaffArchiveNumFromMicro(): int
    {
        $moduleConfig = Module::getLocalModuleConfig("archive_staff");
        if (isset($moduleConfig['paused']) && !$moduleConfig["paused"]) {
            $settings = Micro::call('archive_staff', 'query', '');
            $maxStaffNum = $settings['max_staff_num'] ?? 5;
        } else {
            $maxStaffNum = 5;
        }

        return (int)$maxStaffNum;
    }
}
