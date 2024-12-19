<?php

namespace Modules\TimeoutReplySingle;

use Common\Yii;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use LogicException;
use Modules\Main\Model\ChatConversationsModel;
use Modules\Main\Model\CorpModel;
use Modules\Main\Model\CustomersModel;
use Modules\Main\Model\StaffModel;
use Modules\TimeoutReplySingle\Service\RuleService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Http\Status;

class WxAuthMiddleware implements MiddlewareInterface
{
    public function __construct(protected DataResponseFactoryInterface $responseFactory)
    {
    }

    /**
     * @throws Throwable
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authHeader = $request->getHeaderLine('Authorization');
        $token = null;
        if (str_starts_with($authHeader, 'Bearer ')) {
            $token = substr($authHeader, 7);
        }
        try {
            $decoded = JWT::decode($token, new Key(RuleService::getJwtKey(), 'HS256'));
            $corp = CorpModel::query()->where(['id' => $decoded->corp_id])->getOne();
            if (empty($corp)) {
                Yii::logger()->warning('企业信息不存在', ['decoded' => $decoded]);
                throw new LogicException("企业信息不存在");
            }
            $conversation = ChatConversationsModel::query()->where(['id' => $decoded->conversation_id])->getOne();
            if (empty($conversation)) {
                Yii::logger()->warning('会话信息不存在', ['decoded' => $decoded]);
                throw new LogicException("会话信息不存在");
            }
            $staff = StaffModel::query()->where(['userid' => $decoded->staff_userid])->getOne();
            if (empty($staff)) {
                Yii::logger()->warning("员工信息不存在", ['decoded' => $decoded]);
                throw new LogicException("员工信息不存在");
            }
            $customer = CustomersModel::query()->where(['external_userid' => $decoded->external_userid])->getOne();
            if (empty($customer)) {
                Yii::logger()->warning("客户不存在", ['decoded' => $decoded]);
                throw new LogicException("客户信息不存在");
            }
        } catch (Throwable $e) {
            Yii::logger()->warning($e);
            return $this->responseFactory
                ->createResponse(json_encode([
                    'status' => "failed",
                    'error_message' => "登录过期",
                    'error_code' => Status::UNAUTHORIZED,
                    'data' => [],
                ]), Status::UNAUTHORIZED);
        }

        $newRequest = $request
            ->withAttribute(CorpModel::class, $corp)
            ->withAttribute(ChatConversationsModel::class, $conversation)
            ->withAttribute(StaffModel::class, $staff)
            ->withAttribute(CustomersModel::class, $customer);

        return $handler->handle($newRequest);
    }
}
