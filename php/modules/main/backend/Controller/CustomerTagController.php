<?php

namespace Modules\Main\Controller;

use Common\Controller\BaseController;
use Exception;
use LogicException;
use Modules\Main\DTO\UpdateCorpTagDTO;
use Modules\Main\Model\CorpModel;
use Modules\Main\Service\TagsService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use Yiisoft\Router\HydratorAttribute\RouteArgument;

class CustomerTagController extends BaseController
{
    /**
     * @throws Throwable
     */
    public function list(ServerRequestInterface $request): ResponseInterface
    {
        $corp = $request->getAttribute(CorpModel::class);

        $result = TagsService::customer($corp);

        return $this->jsonResponse($result);
    }

    /**
     * 更新或保存标签
     *
     * @throws Throwable
     */
    public function updateOrCreate(ServerRequestInterface $request): ResponseInterface
    {
        /** @var CorpModel $corp */
        $corp = $request->getAttribute(CorpModel::class);

        $dto = new UpdateCorpTagDTO($request->getParsedBody());

        $res = $corp->postWechatApi('/cgi-bin/externalcontact/get_corp_tag_list');
        $allTagList = [];
        foreach ($res['tag_group'] as $tag_group) {
            $allTagList = array_merge_recursive($allTagList, array_column($tag_group['tag'], null, 'id'));
        }

        if (empty($dto->groupId)) { // 新增
            if (in_array($dto->groupName, array_column($res['tag_group'], 'group_name'))) {
                throw new LogicException("标签组名已存在");
            }

            $data = array_filter($dto->toArray());
            foreach ($data['tag'] as $k => $v) {
                $data['tag'][$k] = array_filter($v);
            }
            $result = $corp->postWechatApi('/cgi-bin/externalcontact/add_corp_tag', $data, 'json');
            if (empty($result['tag_group'])) {
                throw new LogicException("请求企微接口出错");
            }
        } else { // 更新
            $res = $corp->postWechatApi('/cgi-bin/externalcontact/get_corp_tag_list', ['group_id' => [$dto->groupId]], 'json');
            if (empty($res['tag_group'])) {
                throw new LogicException("标签组不存在");
            }

            $result['tag_group']['group_id'] = $res['tag_group'][0]['group_id'];
            $result['tag_group']['group_name'] = $res['tag_group'][0]['group_name'];
            $result['tag_group']['create_time'] = $res['tag_group'][0]['create_time'];

            $tags = array_filter($res['tag_group'][0]['tag'], function ($item) {
                return $item['deleted'] == false;
            });
            $tagNameList = array_column($tags, null, 'name');
            $tagIdList = array_column($tags, null, 'id');

            // 找出需要添加、更新和删除的标签
            $needAddTagList = [];
            $needUpdateTagList = [];
            $needDeleteTagIdList = [];
            if ($dto->groupName != $res['tag_group'][0]['group_name']) {
                $needUpdateTagList[] = ['id' => $dto->groupId, 'name' => $dto->groupName, 'order' => $dto->order];
            }
            foreach ($dto->tag as $tag) {
                if (empty($tag['id'])) { // 标签id为空表示需要新增
                    if (in_array($tag['name'], array_keys($tagNameList))) {
                        throw new LogicException("标签名【{$tag['name']}】重复");
                    }
                    $needAddTagList[] = $tag;
                } else { // 标签id不为空表示是更新操作
                    if (!in_array($tag['id'], array_keys($tagIdList))) { // 这个tag有可能是在别处被删除了，重新添加
                        unset($tag['id']);
                        $needAddTagList[] = $tag;
                    }
                    if (in_array($tag['id'], array_keys($tagIdList)) && !in_array($tag['name'], array_keys($tagNameList))) { // 更新
                        $needUpdateTagList[] = $tag;
                    }
                }
            }
            foreach ($tags as $v) {
                if (!in_array($v['id'], array_column($dto->tag, 'id'))) {
                    $needDeleteTagIdList[] = $v['id'];
                }
            }

            // 更新标签
            if (!empty($needUpdateTagList)) {
                foreach ($needUpdateTagList as $tag) {
                    $data = ['id' => $tag['id'], 'name' => $tag['name'], 'order' => $tag['order']];
                    $corp->postWechatApi('/cgi-bin/externalcontact/edit_corp_tag', $data, 'json');
                }
            }

            // 添加标签
            if (!empty($needAddTagList)) {
                $data = ['group_id' => $dto->groupId, 'group_name' => $dto->groupName, 'tag' => $needAddTagList];
                foreach ($data['tag'] as $k => $v) {
                    $data['tag'][$k] = array_filter($v);
                }
                $result = $corp->postWechatApi('/cgi-bin/externalcontact/add_corp_tag', $data, 'json');
                if (empty($result['tag_group'])) {
                    throw new LogicException("请求企微接口出错");
                }
            }

            // 删除标签
            if (!empty($needDeleteTagIdList)) {
                $data = ['tag_id' => $needDeleteTagIdList];
                $corp->postWechatApi('/cgi-bin/externalcontact/del_corp_tag', $data, 'json');
            }
        }

        return $this->jsonResponse();
    }

    /**
     * 删除标签
     *
     * @throws Throwable
     */
    public function destroyTag(
        ServerRequestInterface $request,
        #[RouteArgument('tag_id')]
        string $tagId,
    ): ResponseInterface {
        $corp = $request->getAttribute(CorpModel::class);

        $data = ['tag_id' => $tagId];
        $corp->postWechatApi('/cgi-bin/externalcontact/del_corp_tag', $data, 'json');

        return $this->jsonResponse();
    }

    /**
     * 删除标签组
     *
     * @throws Throwable
     */
    public function destroyGroup(
        ServerRequestInterface $request,
        #[RouteArgument('group_id')]
        string $groupId,
    ): ResponseInterface {
        $corp = $request->getAttribute(CorpModel::class);

        $data = ['group_id' => $groupId];
        $corp->postWechatApi('/cgi-bin/externalcontact/del_corp_tag', $data, 'json');

        return $this->jsonResponse();
    }
}
