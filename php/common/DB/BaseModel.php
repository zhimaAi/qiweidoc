<?php

namespace Common\DB;

use BackedEnum;
use Carbon\Carbon;
use Common\Yii;
use Exception;
use RuntimeException;
use Throwable;
use ValueError;
use Yiisoft\Db\Exception\IntegrityException;
use Yiisoft\Db\Expression\Expression;

abstract class BaseModel
{
    protected array | string $primaryKeys = "id";
    protected string $deletedAtColumn = 'deleted_at';
    protected bool $usesSoftDelete = false;

    protected array $attributes = [];
    protected array $original = [];
    protected bool $exists = false;
    protected array $appends = [];
    public static string $lastSql = "";

    abstract public function getTableName(): string;

    abstract protected function casts(): array;

    public function getPrimaryKeys(): string | array
    {
        return $this->primaryKeys;
    }

    /**
     * 检查主键是否为自增类型（默认为自增，如果是UUID等其他类型需要在子类中覆盖该方法）
     */
    protected function isAutoIncrementPK(): bool
    {
        return true;
    }

    public function getTimestampFields(): array
    {
        return [
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
            'deleted_at' => $this->deletedAtColumn,
        ];
    }

    public function setAttributes(array $attributes, bool $exists = false): void
    {
        $this->attributes = $attributes;
        $this->original = $attributes;
        $this->exists = $exists;
    }

    public function append(string $key, mixed $value): void
    {
        $this->appends[$key] = $value;
    }

    public static function query(string $alias = ''): DBQuery
    {
        $model = new static();
        $table = $model->getTableName();
        if (!empty($alias)) {
            $table .= " as {$alias}";
        }
        $query = (new DBQuery(Yii::db()))->from($table);
        $query->setModel($model);

        if ($model->usesSoftDelete) {
            $query->andWhere([$model->deletedAtColumn => null]);
        }

        return $query;
    }

    /**
     * Include soft deleted records in the query
     * @throws Throwable
     */
    public static function withTrashed(): DBQuery
    {
        $model = new static();
        $query = (new DBQuery(Yii::db()))->from($model->getTableName());
        $query->setModel($model);

        return $query;
    }

    /**
     * Only get soft deleted records
     * @throws Throwable
     */
    public static function onlyTrashed(): DBQuery
    {
        $model = new static();
        $query = (new DBQuery(Yii::db()))->from($model->getTableName());

        if ($model->usesSoftDelete) {
            $query->andWhere(new Expression($model->deletedAtColumn . " is not null"));
        }

        $query->setModel($model);

        return $query;
    }

    /**
     * @throws Throwable
     */
    public static function create(array $attributes): static
    {
        $model = new static();
        $model->exists = false;
        $attributes = $model->filterAttributes($attributes);

        $timestampFields = $model->getTimestampFields();
        if (!empty($timestampFields['created_at'])) {
            $attributes[$timestampFields['created_at']] = Carbon::now()->format('Y-m-d H:i:s.v');
        }
        if (!empty($timestampFields['updated_at'])) {
            $attributes[$timestampFields['updated_at']] = Carbon::now()->format('Y-m-d H:i:s.v');
        }
        foreach ($attributes as $field => $value) {
            $model->set($field, $value);
        }

        $model->saveOrFail();

        return $model;
    }

    /**
     * @throws Throwable
     */
    public static function firstOrCreate(array $filter, array $attributes = []): static
    {
        $model = static::query()->where($filter)->getOne();

        if ($model === null) {
            $model = static::create($attributes);
        }

        return $model;
    }

    /**
     * @throws Throwable
     */
    public static function updateOrCreate(array $filter, array $attributes): ModelCollection
    {
        $collection = new ModelCollection();
        $models = static::query()->where($filter)->getAll();

        if ($models->isEmpty()) {
            $model = static::create($attributes);
            $collection->add($model);
        } else {
            foreach ($models as $model) {
                foreach ($attributes as $key => $value) {
                    $model->set($key, $value);
                }
                $model->saveOrFail();
                $collection->add($model);
            }
        }

        return $collection;
    }

    public function toArray(): array
    {
        $result = [];

        foreach ($this->attributes as $field => $value) {
            $result[$field] = $this->transform($field, $value);
        }

        foreach ($this->appends as $field => $value) {
            $result[$field] = $value;
        }

        return $result;
    }

    private function transform(string $field, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $casts = $this->casts();
        if (empty($casts[$field])) {
            return $value;
        }

        $cast = $casts[$field];

        return match ($cast) {
            'array' => is_array($value) ? $value : json_decode($value, true),
            'int' => (int) $value,
            'float' => (float) $value,
            'bool' => (bool) $value,
            'string' => (string) $value,
            default => $this->handleEnumCast($cast, $value),
        };
    }

    /**
     * 枚举值转换
     */
    private function handleEnumCast(string $enumClass, mixed $value): mixed
    {
        if ($value instanceof $enumClass) {
            return $value;
        }

        if (is_subclass_of($enumClass, BackedEnum::class)) {
            try {
                return $enumClass::from($value);
            } catch (ValueError) {
                return null;
            }
        }

        return $value;
    }

    private function handleEnumUncast(string $enumClass, mixed $value): mixed
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        return $value;
    }

    private function untransform(string $field, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $casts = $this->casts();
        if (empty($casts[$field])) {
            return $value;
        }

        $cast = $casts[$field];

        // yii底层会自动处理array所以需要保持array类型
        return match ($cast) {
            'array' => is_string($value) ? json_decode($value, true): $value,
            'int', 'float', 'bool', 'string' => $value,
            default => $this->handleEnumUncast($cast, $value),
        };
    }

    public function get(string $field, mixed $default = null)
    {
        $value = $this->attributes[$field] ?? null;
        if ($value !== null) {
            return $this->transform($field, $value);
        }
        if ($default != null) {
            return $default;
        }

        $casts = $this->casts();
        if (empty($casts[$field])) {
            return null;
        }

        $cast = $casts[$field];

        return match ($cast) {
            'array' => [],
            'int', 'float' => 0,
            'bool' => false,
            'string' => '',
            default => BackedEnum::class,
        };
    }

    public function set(string $field, mixed $value): void
    {
        $this->attributes[$field] = $this->untransform($field, $value);
    }

    public function unset(string $field): void
    {
        unset($this->attributes[$field]);
    }

    /**
     * @throws Exception
     */
    public function __get(string $name)
    {
        throw new Exception("禁止使用魔术方法");
    }

    /**
     * @throws Exception
     */
    public function __set(string $field, mixed $value): void
    {
        throw new Exception("禁止使用魔术方法");
    }

    /**
     * @throws Exception
     */
    public function __isset(string $name): bool
    {
        throw new Exception("禁止使用魔术方法");
    }

    /**
     * @throws Throwable
     */
    public function update(array $attributes): void
    {
        $attributes = $this->filterAttributes($attributes);

        foreach ($attributes as $key => $value) {
            $this->set($key, $value);
        }

        $this->saveOrFail();
    }

    public function getDirty(): array
    {
        $dirty = [];
        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || $this->original[$key] !== $value) {
                $dirty[$key] = $value;
            }
        }

        return $dirty;
    }

    public function save(): bool
    {
        try {
            $this->saveOrFail();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * 批量插入数据
     * @param array[] $rows 二维数组，每个子数组包含单条记录的数据
     * @return int 返回插入的行数
     * @throws Throwable
     */
    public static function batchInsert(array $rows): int
    {
        if (empty($rows)) {
            return 0;
        }

        $model = new static();
        $timestampFields = $model->getTimestampFields();

        foreach ($rows as &$row) {
            if (!empty($timestampFields['created_at'])) {
                $row[$timestampFields['created_at']] = Carbon::now()->format('Y-m-d H:i:s.v');
            }
            if (!empty($timestampFields['updated_at'])) {
                $row[$timestampFields['updated_at']] = Carbon::now()->format('Y-m-d H:i:s.v');
            }
        }

        $columns = array_keys(reset($rows));

        return $model->executeCommand(function ($db) use ($model, $columns, $rows) {
            return $db->createCommand()->batchInsert($model->getTableName(), $columns, $rows)->execute();
        });
    }

    protected function getSequenceName(): ?string
    {
        return match (Yii::db()->getDriverName()) {
            'pgsql' => $this->getTableName() . '_id_seq',
            default => null
        };
    }

    protected function filterAttributes(array $attributes): array
    {
        $casts = $this->casts();
        return array_intersect_key($attributes, $casts);
    }

    /**
     * 保存模型到数据库
     *
     * @throws Throwable
     */
    public function saveOrFail(): void
    {
        if ($this->exists) {
            $dirty = $this->getDirty();
            if (empty($dirty)) {
                return;
            }
            $dirty = $this->filterAttributes($dirty);

            if (!empty($this->getTimestampFields()['updated_at'])) {
                $dirty[$this->getTimestampFields()['updated_at']] = Carbon::now()->format('Y-m-d H:i:s.v');
            }

            $primaryKeys = (array) $this->getPrimaryKeys();
            $condition = array_intersect_key($this->original, array_flip($primaryKeys));

            $this->executeCommand(function ($db) use ($dirty, $condition) {
                return $db->createCommand()->update($this->getTableName(), $dirty, $condition)->execute();
            });
        } else {
            $this->attributes = $this->filterAttributes($this->attributes);

            // 对于非自增主键（如UUID），在插入前必须确保主键值已设置
            if (!$this->isAutoIncrementPK()) {
                $pk = $this->getPrimaryKeys();
                if (is_string($pk) && empty($this->attributes[$pk])) {
                    throw new RuntimeException('Primary key value must be set for non-auto-increment primary key.');
                }
            }

            // 执行sql
            $this->executeCommand(function ($db) {
                return $db->createCommand()->insert($this->getTableName(), $this->attributes)->execute();
            });

            // 仅对自增主键获取last insert id
            if ($this->isAutoIncrementPK() && is_string($this->getPrimaryKeys())) {
                $sequence = $this->getSequenceName();
                $this->attributes[$this->getPrimaryKeys()] = $sequence
                    ? Yii::db()->getLastInsertID($sequence)
                    : Yii::db()->getLastInsertID();
            }

            $this->exists = true;
        }

        $this->original = $this->attributes;
    }

    public function delete(): bool
    {
        try {
            $this->deleteOrFail();

            return true;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @throws Throwable
     */
    public function deleteOrFail(): void
    {
        if (!$this->exists) {
            throw new RuntimeException('Model does not exist.');
        }

        if ($this->usesSoftDelete) {
            $this->set($this->deletedAtColumn, Carbon::now()->format('Y-m-d H:i:s.v'));
            $this->saveOrFail();

            return;
        }

        $primaryKeys = (array) $this->getPrimaryKeys();
        $condition = array_intersect_key($this->attributes, array_flip($primaryKeys));

        $this->executeCommand(function ($db) use ($condition) {
            return $db->createCommand()->delete($this->getTableName(), $condition)->execute();
        });

        $this->exists = false;
    }

    /**
     * 强制删除，包括软删除
     *
     * @throws Throwable
     */
    public function forceDeleteOrFail(): void
    {
        if (!$this->exists) {
            throw new RuntimeException('Model does not exist.');
        }

        $primaryKeys = (array) $this->getPrimaryKeys();
        $condition = array_intersect_key($this->attributes, array_flip($primaryKeys));

        $this->executeCommand(function ($db) use ($condition) {
            return $db->createCommand()->delete($this->getTableName(), $condition)->execute();
        });

        $this->exists = false;
    }

    /**
     * 软删除数据恢复
     *
     * @throws Throwable
     */
    public function restore(): void
    {
        if ($this->usesSoftDelete) {
            $this->set($this->deletedAtColumn, null);
            $this->saveOrFail();
        }
    }

    /**
     * 执行数据库命令并记录 SQL
     * @throws Throwable
     */
    private function executeCommand(callable $command): mixed
    {
        $maxRetries = 3;  // 最大重试次数
        $retryCount = 0;

        while (true) {
            try {
                return $command(Yii::db());
            } catch (IntegrityException $e) {
                if (str_contains($e->getMessage(), 'SQLSTATE[HY000]')) {
                    $retryCount++;
                    if ($retryCount > $maxRetries) {
                        throw new Exception('数据库连接失败，已重试' . $maxRetries . '次', 0, $e);
                    }
                    Yii::db()->close();
                    usleep(100000 * $retryCount);
                    continue;
                }
                throw $e;
            }
        }
    }

    /**
     * 生成空的分页数据格式
     */
    public static function buildPaginate($page, $size, int $total = 0, array $items = []): array
    {
        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'size' => $size,
            'total_page' => (int) ceil($total / $size),
        ];
    }
}
