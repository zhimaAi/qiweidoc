<?php

declare(strict_types=1);

namespace Yiisoft\Db\Pgsql\Builder;

use Yiisoft\Db\Exception\Exception;
use Yiisoft\Db\Exception\InvalidArgumentException;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Expression\ExpressionBuilderInterface;
use Yiisoft\Db\Expression\ExpressionInterface;
use Yiisoft\Db\Pgsql\StructuredExpression;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\QueryBuilder\QueryBuilderInterface;

use function implode;

/**
 * Builds expressions for {@see StructuredExpression} for PostgreSQL Server.
 */
final class StructuredExpressionBuilder implements ExpressionBuilderInterface
{
    public function __construct(private QueryBuilderInterface $queryBuilder)
    {
    }

    /**
     * The method builds the raw SQL from the expression that won't be additionally escaped or quoted.
     *
     * @param StructuredExpression $expression The expression build.
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
        $value = $expression->getValue();

        if (empty($value)) {
            return 'NULL';
        }

        if ($value instanceof QueryInterface) {
            [$sql, $params] = $this->queryBuilder->build($value, $params);
            return "($sql)" . $this->getTypeHint($expression);
        }

        /** @psalm-var string[] $placeholders */
        $placeholders = $this->buildPlaceholders($expression, $params);

        if (empty($placeholders)) {
            return 'NULL';
        }

        return 'ROW(' . implode(', ', $placeholders) . ')' . $this->getTypeHint($expression);
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
    private function buildPlaceholders(StructuredExpression $expression, array &$params): array
    {
        $value = $expression->getNormalizedValue();

        if (!is_iterable($value)) {
            return [];
        }

        $placeholders = [];
        $columns = $expression->getColumns();

        /** @psalm-var int|string $columnName */
        foreach ($value as $columnName => $item) {
            if (isset($columns[$columnName])) {
                $item = $columns[$columnName]->dbTypecast($item);
            }

            if ($item instanceof ExpressionInterface) {
                $placeholders[] = $this->queryBuilder->buildExpression($item, $params);
            } else {
                $placeholders[] = $this->queryBuilder->bindParam($item, $params);
            }
        }

        return $placeholders;
    }

    /**
     * @return string The typecast expression based on {@see type}.
     */
    private function getTypeHint(StructuredExpression $expression): string
    {
        $type = $expression->getType();

        if ($type === null) {
            return '';
        }

        return '::' . $type;
    }
}
