<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Service\Generate;

use function array_shift;
use function array_unshift;
use function explode;
use function in_array;
use function preg_match;
use function preg_split;
use function str_replace;
use function str_starts_with;

/**
 * @internal
 */
final class FieldsParser
{
    public function __construct(private ForeignKeyFactory $foreignKeyFactory)
    {
    }

    /**
     * @return array[]
     *
     * @psalm-return array{0:Column[],1:ForeignKey[]}
     */
    public function parse(
        string $table,
        ?string $value,
        bool $addDefaultPrimaryKey
    ): array {
        $columns = [];
        $foreignKeys = [];

        if (!empty($value)) {
            $fields = explode(',', $value);
            /** @psalm-var string[] $fields */
            foreach ($fields as $field) {
                $chunks = $this->splitFieldIntoChunks($field);
                $columnName = (string) array_shift($chunks);

                /** @psalm-var string[] $chunks */
                foreach ($chunks as $i => $chunk) {
                    if (str_starts_with($chunk, 'foreignKey')) {
                        preg_match('/foreignKey\((\w*)\s?(\w*)\)/', $chunk, $matches);
                        $foreignKeys[] = $this->foreignKeyFactory->create(
                            $table,
                            $columnName,
                            $matches[1] ?? preg_replace('/_id$/', '', $columnName),
                            empty($matches[2]) ? null : $matches[2]
                        );

                        unset($chunks[$i]);
                        continue;
                    }

                    if (!preg_match('/\(([^(]+)\)$/', $chunk)) {
                        $chunks[$i] .= '()';
                    }
                }

                /** @psalm-var string[] $chunks */
                $columns[] = new Column($columnName, $chunks);
            }
        }

        if ($addDefaultPrimaryKey) {
            $this->addDefaultPrimaryKey($columns);
        }

        return [$columns, $foreignKeys];
    }

    private function splitFieldIntoChunks(string $field): array
    {
        $defaultValue = '';
        $originalDefaultValue = '';

        if (preg_match('/defaultValue\(.*?:.*?\)/', $field, $matches) === 1) {
            $originalDefaultValue = $matches[0];
            $defaultValue = str_replace(':', '{{colon}}', $originalDefaultValue);
            $field = str_replace($originalDefaultValue, $defaultValue, $field);
        }

        /** @var string[] $chunks */
        $chunks = preg_split('/\s?:\s?/', $field, -1, PREG_SPLIT_NO_EMPTY);

        if ($defaultValue !== '') {
            foreach ($chunks as $key => $chunk) {
                $chunks[$key] = str_replace($defaultValue, $originalDefaultValue, $chunk);
            }
        }

        return $chunks;
    }

    /**
     * Adds a default primary key to columns list if there's no primary key specified.
     *
     * @param Column[] $columns
     */
    private function addDefaultPrimaryKey(array &$columns): void
    {
        foreach ($columns as $column) {
            if (in_array('primaryKey()', $column->getDecorators(), true)) {
                return;
            }
        }

        array_unshift($columns, new Column('id', ['primaryKey()']));
    }
}
