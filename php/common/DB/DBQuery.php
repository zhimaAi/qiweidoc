<?php

namespace Common\DB;

use Carbon\Carbon;
use Common\Yii;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use Throwable;
use Yiisoft\Db\Query\Query;

/**
 * @template-covariant BModel
 */
class DBQuery extends Query
{
    private BaseModel $model;

    public function setModel(BaseModel $model): void
    {
        $this->model = $model;
    }

    /**
     * 执行数据库命令并记录 SQL
     *
     * @throws Throwable
     */
    private function executeCommand(callable $command): mixed
    {
        try {
            $result = $command();
            BaseModel::$lastSql = $this->createCommand()->getRawSql();

            return $result;
        } catch (Throwable $e) {
            BaseModel::$lastSql = $this->createCommand()->getRawSql();
            if (Yii::isDebug()) {
                throw new Exception("sql执行出错：" . BaseModel::$lastSql . $e->getMessage(), 500, $e);
            }

            throw $e;
        }
    }

    /**
     * 查询单条结果并返回一个model实例
     *
     * @return BModel|null
     * @throws Throwable
     */
    public function getOne(): BaseModel|null
    {
        $attributes = $this->executeCommand(fn () => $this->limit(1)->one());

        if (empty($attributes)) {
            return null;
        }

        $modelClass = get_class($this->model);
        $model = new $modelClass();
        $model->setAttributes($attributes, true);

        return $model;
    }

    /**
     * 获取模型集合
     *
     * @throws Throwable
     */
    public function getAll(): ModelCollection
    {
        $collection = new ModelCollection();
        $all = $this->executeCommand(fn () => $this->all());

        $modelClass = get_class($this->model);
        foreach ($all as $attributes) {
            $model = new $modelClass();
            $model->setAttributes($attributes, true);
            $collection->add($model);
        }

        return $collection;
    }

    /**
     * 分页查询
     * @param int $page 页码，从1开始
     * @param int $size 每页数量
     * @return array{
     *     items: ArrayCollection<int, BaseModel>,
     *     total: int,
     *     total_page: int,
     *     page: int,
     *     size: int
     * }
     * @throws Throwable
     */
    public function paginate(int $page = 1, int $size = 20): array
    {
        $total = $this->count();
        $totalPage = (int) ceil($total / $size);

        $items = $this->offset(($page - 1) * $size)
            ->limit($size)
            ->getAll();

        return [
            'items' => $items,
            'total' => $total,
            'total_page' => $totalPage,
            'page' => $page,
            'size' => $size,
        ];
    }

    /**
     * 批量更新
     *
     * @throws Throwable
     */
    public function update(array $attributes): int
    {
        if (!empty($this->model->getTimestampFields()['updated_at'])) {
            $attributes['updated_at'] = Carbon::now()->format('Y-m-d H:i:s.v');
        }

        return $this->executeCommand(function () use ($attributes) {
            return $this->createCommand()
                ->update($this->model->getTableName(), $attributes, $this->where)
                ->execute();
        });
    }

    /**
     * 批量删除当前查询条件匹配的记录
     *
     * @throws Throwable
     */
    public function deleteAll(): int
    {
        return $this->executeCommand(function () {
            return $this->createCommand()
                ->delete($this->model->getTableName(), $this->where)
                ->execute();
        });
    }
}
