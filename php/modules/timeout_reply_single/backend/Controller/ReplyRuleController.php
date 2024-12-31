<?php
// Copyright © 2016- 2025 Sesame Network Technology all right reserved

namespace Modules\TimeoutReplySingle\Controller;

use Common\Controller\BaseController;
use Modules\Main\Model\CorpModel;
use Modules\TimeoutReplySingle\DTO\ReplyRuleDTO;
use Modules\TimeoutReplySingle\Model\ReplyRuleModel;
use Modules\TimeoutReplySingle\Model\RuleModel;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ReplyRuleController extends BaseController
{
    public function show(ServerRequestInterface $request) : ResponseInterface
    {
        $corp = $request->getAttribute(CorpModel::class);

        $data = ReplyRuleModel::query()->where(['corp_id' => $corp->get('id')])->getOne();
        if (empty($data)) {
            $data = ReplyRuleModel::getDefaultData();
            $data['corp_id'] = $corp->get('id');
            ReplyRuleModel::create($data);
        }

        return $this->jsonResponse($data);
    }

    /**
     * 更新规则
     */
    public function save(ServerRequestInterface $request) : ResponseInterface
    {
        $corp = $request->getAttribute(CorpModel::class);

        $body = $request->getParsedBody();
        $dto = new ReplyRuleDTO($body);

        ReplyRuleModel::updateOrCreate(['corp_id' => $corp->get('id')], array_merge(['corp_id' => $corp->get('id')], $dto->toArray()));

        return $this->jsonResponse();
    }
}
