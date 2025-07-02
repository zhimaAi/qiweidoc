<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Builder;

use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ArrayExpression;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Expression\JsonExpression;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

use function implode;
use function in_array;
use function is_iterable;
use function str_repeat;

/**
 * Builds expressions for {@see ArrayExpression} for PostgreSQL Server.
 */
final class ArrayExpressionBuilder implements ExpressionBuilderInterface
{
    public function __construct(private QueryBuilderInterface $queryBuilder)
    {
    }

    /**
     * The Method builds the raw SQL from the expression that won't be additionally escaped or quoted.
     *
     * @param ArrayExpression $expression The expression build.
     * @param array $params The binding parameters.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @return string The raw SQL that won't be additionally escaped or quoted.
     */
    public function build(ExpressionInterface $expression, array &$params = []): string
    {
        /** @psalm-var array|mixed|QueryInterface $value */
        $value = $expression->getValue();

        if ($value === null) {
            return 'NULL';
        }

        if ($value instanceof QueryInterface) {
            [$sql, $params] = $this->queryBuilder->build($value, $params);
            return $this->buildSubqueryArray($sql, $expression);
        }

        /** @psalm-var string[] $placeholders */
        $placeholders = $this->buildPlaceholders($expression, $params);

        return 'ARRAY[' . implode(', ', $placeholders) . ']' . $this->getTypeHint($expression);
    }

    /**
     * Builds a placeholder array out of $expression values.
     *
     * @param array $params The binding parameters.
     *
     * @throws Exception
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    private function buildPlaceholders(ArrayExpression $expression, array &$params): array
    {
        $placeholders = [];

        /** @psalm-var mixed $value */
        $value = $expression->getValue();

        if (!is_iterable($value)) {
            return $placeholders;
        }

        if ($expression->getDimension() > 1) {
            /** @psalm-var mixed $item */
            foreach ($value as $item) {
                $placeholders[] = $this->build($this->unnestArrayExpression($expression, $item), $params);
            }
            return $placeholders;
        }

        /** @psalm-var ExpressionInterface|int $item */
        foreach ($value as $item) {
            if ($item instanceof QueryInterface) {
                [$sql, $params] = $this->queryBuilder->build($item, $params);
                $placeholders[] = $this->buildSubqueryArray($sql, $expression);
                continue;
            }

            $item = $this->typecastValue($expression, $item);

            if ($item instanceof ExpressionInterface) {
                $placeholders[] = $this->queryBuilder->buildExpression($item, $params);
            } else {
                $placeholders[] = $this->queryBuilder->bindParam($item, $params);
            }
        }

        return $placeholders;
    }

    private function unnestArrayExpression(ArrayExpression $expression, mixed $value): ArrayExpression
    {
        return new ArrayExpression($value, $expression->getType(), $expression->getDimension() - 1);
    }

    /**
     * @return string The typecast expression based on {@see type}.
     */
    private function getTypeHint(ArrayExpression $expression): string
    {
        $type = $expression->getType();

        if ($type === null) {
            return '';
        }

        $dimension = $expression->getDimension();

        return '::' . $type . str_repeat('[]', $dimension);
    }

    /**
     * Build an array expression from a sub-query SQL.
     *
     * @param string $sql The sub-query SQL.
     * @param ArrayExpression $expression The array expression.
     *
     * @return string The sub-query array expression.
     */
    private function buildSubqueryArray(string $sql, ArrayExpression $expression): string
    {
        return 'ARRAY(' . $sql . ')' . $this->getTypeHint($expression);
    }

    /**
     * @return array|bool|ExpressionInterface|float|int|JsonExpression|string|null The cast value or expression.
     */
    private function typecastValue(
        ArrayExpression $expression,
        array|bool|float|int|string|ExpressionInterface|null $value
    ): array|bool|float|int|string|JsonExpression|ExpressionInterface|null {
        if ($value instanceof ExpressionInterface) {
            return $value;
        }

        if (in_array($expression->getType(), ['json', 'jsonb'], true)) {
            return new JsonExpression($value);
        }

        return $value;
    }
}
