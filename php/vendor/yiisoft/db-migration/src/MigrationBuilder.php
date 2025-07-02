<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration;

use Exception;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Constraint\Constraint;
use Yiisoft\Db\Exception\InvalidConfigException;
use Yiisoft\Db\Exception\NotSupportedException;
use Yiisoft\Db\Query\QueryInterface;
use Yiisoft\Db\Schema\Builder\ColumnInterface;
use Yiisoft\Db\Migration\Informer\MigrationInformerInterface;

use function implode;
use function ltrim;
use function microtime;
use function rtrim;
use function sprintf;
use function substr;
use function trim;

final class MigrationBuilder extends AbstractMigrationBuilder
{
    public function __construct(
        private ConnectionInterface $db,
        private MigrationInformerInterface $informer,
        private ?int $maxSqlOutputLength = null,
    ) {
        parent::__construct($this->db->getSchema());
    }

    public function getDb(): ConnectionInterface
    {
        return $this->db;
    }

    /**
     * Executes a SQL statement.
     *
     * This method executes the specified SQL statement using {@see ConnectionInterface}.
     *
     * @param string $sql The SQL statement to be executed.
     * @param array $params Input parameters (name => value) for the SQL execution.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * {@see \Yiisoft\Db\Command\Command::execute()} for more details.
     */
    public function execute(string $sql, array $params = []): void
    {
        $command = $this->db->createCommand($sql)->bindValues($params);
        $sqlOutput = trim($command->getRawSql());

        if ($this->maxSqlOutputLength !== null && $this->maxSqlOutputLength < strlen($sqlOutput)) {
            $sqlOutput = ltrim(rtrim(substr($sqlOutput, 0, $this->maxSqlOutputLength)) . ' [... hidden]');
        }

        $time = $this->beginCommand("Execute SQL: $sqlOutput");
        $command->execute();
        $this->endCommand($time);
    }

    /**
     * Creates and executes an INSERT SQL statement.
     *
     * The method will properly escape the column names and bind the values to be inserted.
     *
     * @param string $table The table that new rows will be inserted into.
     * @param array $columns The column data (name => value) to be inserted into the table.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function insert(string $table, array $columns): void
    {
        $time = $this->beginCommand("Insert into $table");
        $this->db->createCommand()->insert($table, $columns)->execute();
        $this->endCommand($time);
    }

    /**
     * Creates and executes a batch INSERT SQL statement.
     *
     * The method will properly escape the column names and bind the values to be inserted.
     *
     * @param string $table The table that new rows will be inserted into.
     * @param array $columns The column names.
     * @param iterable $rows The rows to be batch inserted into the table
     *
     * @psalm-param iterable<array-key, array<array-key, mixed>> $rows
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function batchInsert(string $table, array $columns, iterable $rows): void
    {
        $time = $this->beginCommand("Insert into $table");
        $this->db->createCommand()->batchInsert($table, $columns, $rows)->execute();
        $this->endCommand($time);
    }

    /**
     * Creates and executes a command to insert rows into a database table if they do not already exist (matching unique
     * constraints), or update them if they do.
     *
     * The method will properly escape the column names and bind the values to be inserted.
     *
     * @param string $table The table that new rows will be inserted into/updated in.
     * @param array|QueryInterface $insertColumns The column data (name => value) to insert into the table or an
     * instance of {@see QueryInterface} to perform `INSERT INTO ... SELECT` SQL statement.
     * @param array|bool $updateColumns The column data (name => value) to be updated if they already exist.
     * If `true` is passed, the column data will be updated to match the insert column data.
     * If `false` is passed, no update will be performed if the column data already exists.
     * @param array $params The parameters to be bound to the command.
     *
     * @psalm-param array<string, mixed>|QueryInterface $insertColumns
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function upsert(
        string $table,
        array|QueryInterface $insertColumns,
        array|bool $updateColumns = true,
        array $params = []
    ): void {
        $time = $this->beginCommand("Upsert into $table");
        $this->db->createCommand()->upsert($table, $insertColumns, $updateColumns, $params)->execute();
        $this->endCommand($time);
    }

    /**
     * Creates and executes an UPDATE SQL statement.
     *
     * The method will properly escape the column names and bind the values to be updated.
     *
     * @param string $table The table to be updated.
     * @param array $columns The column data (name => value) to be updated.
     * @param array|string $condition The condition to put in the `WHERE` part. Please refer to
     * {@see QueryInterface::where()} on how to specify condition.
     * @param array $params The parameters to be bound to the query.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function update(string $table, array $columns, array|string $condition = '', array $params = []): void
    {
        $time = $this->beginCommand("Update $table");
        $this->db->createCommand()->update($table, $columns, $condition, $params)->execute();
        $this->endCommand($time);
    }

    /**
     * Creates and executes a DELETE SQL statement.
     *
     * @param string $table The table where the data will be deleted from.
     * @param array|string $condition The condition to put in the `WHERE` part. Please refer to
     * {@see QueryInterface::where()} on how to specify condition.
     * @param array $params The parameters to be bound to the query.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function delete(string $table, array|string $condition = '', array $params = []): void
    {
        $time = $this->beginCommand("Delete from $table");
        $this->db->createCommand()->delete($table, $condition, $params)->execute();
        $this->endCommand($time);
    }

    /**
     * Builds and executes an SQL statement for creating a new DB table.
     *
     * The columns in the new table should be specified as name-definition pairs (e.g. 'name' => 'string'), where name
     * stands for a column name which will be properly quoted by the method, and definition stands for the column type
     * which can contain an abstract DB type.
     *
     * The {@see \Yiisoft\Db\Query\QueryBuilder::getColumnType()} method will be invoked to convert any abstract type
     * into a physical one.
     *
     * If a column is specified with definition only (e.g. 'PRIMARY KEY (name, type)'), it will be directly put into the
     * generated SQL.
     *
     * @param string $table The name of the table to be created. The name will be properly quoted by the method.
     * @param array $columns The columns (name => definition) in the new table.
     * @param string|null $options Additional SQL fragment that will be appended to the generated SQL.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @psalm-param array<string, string|ColumnInterface> $columns
     */
    public function createTable(string $table, array $columns, string|null $options = null): void
    {
        $time = $this->beginCommand("create table $table");

        $this->db->createCommand()->createTable($table, $columns, $options)->execute();

        foreach ($columns as $column => $type) {
            if ($type instanceof ColumnInterface) {
                $comment = $type->getComment();
                if ($comment !== null) {
                    $this->db->createCommand()->addCommentOnColumn($table, $column, $comment)->execute();
                }
            }
        }

        $this->endCommand($time);
    }

    /**
     * Builds and executes an SQL statement for renaming a DB table.
     *
     * @param string $table The table to be renamed. The name will be properly quoted by the method.
     * @param string $newName The new table name. The name will be properly quoted by the method.
     */
    public function renameTable(string $table, string $newName): void
    {
        $time = $this->beginCommand("rename table $table to $newName");
        $this->db->createCommand()->renameTable($table, $newName)->execute();
        $this->endCommand($time);
    }

    /**
     * Builds and executes an SQL statement for dropping a DB table.
     *
     * @param string $table The table to be dropped. The name will be properly quoted by the method.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function dropTable(string $table): void
    {
        $time = $this->beginCommand("Drop table $table");
        $this->db->createCommand()->dropTable($table)->execute();
        $this->endCommand($time);
    }

    /**
     * Builds and executes an SQL statement for truncating a DB table.
     *
     * @param string $table The table to be truncated. The name will be properly quoted by the method.
     */
    public function truncateTable(string $table): void
    {
        $time = $this->beginCommand("truncate table $table");
        $this->db->createCommand()->truncateTable($table)->execute();
        $this->endCommand($time);
    }

    /**
     * Builds and executes an SQL statement for adding a new DB column.
     *
     * @param string $table The table that the new column will be added to.
     * The table name will be properly quoted by the method.
     * @param string $column The name of the new column. The name will be properly quoted by the method.
     * @param ColumnInterface|string $type The column type. The {@see QueryBuilder::getColumnType()} method
     * will be invoked to convert an abstract column type (if any) into the physical one. Anything not
     * recognized as an abstract type will be kept in the generated SQL. For example, 'string' will be turned
     * into 'varchar(255)', while 'string not null' will become 'varchar(255) not null'.
     */
    public function addColumn(string $table, string $column, ColumnInterface|string $type): void
    {
        $comment = null;
        if ($type instanceof ColumnInterface) {
            $comment = $type->getComment();
            $type = $type->asString();
        }

        $time = $this->beginCommand("add column $column $type to table $table");
        $this->db->createCommand()->addColumn($table, $column, $type)->execute();
        if ($comment !== null) {
            $this->db->createCommand()->addCommentOnColumn($table, $column, $comment)->execute();
        }
        $this->endCommand($time);
    }

    /**
     * Builds and executes an SQL statement for dropping a DB column.
     *
     * @param string $table The table whose column is to be dropped. The name will be properly quoted by the method.
     * @param string $column The name of the column to be dropped. The name will be properly quoted by the method.
     */
    public function dropColumn(string $table, string $column): void
    {
        $time = $this->beginCommand("drop column $column from table $table");
        $this->db->createCommand()->dropColumn($table, $column)->execute();
        $this->endCommand($time);
    }

    /**
     * Builds and executes an SQL statement for renaming a column.
     *
     * @param string $table The table whose column is to be renamed. The name will be properly quoted by the method.
     * @param string $name The old name of the column. The name will be properly quoted by the method.
     * @param string $newName The new name of the column. The name will be properly quoted by the method.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function renameColumn(string $table, string $name, string $newName): void
    {
        $time = $this->beginCommand("Rename column $name in table $table to $newName");
        $this->db->createCommand()->renameColumn($table, $name, $newName)->execute();
        $this->endCommand($time);
    }

    /**
     * Builds and executes an SQL statement for changing the definition of a column.
     *
     * @param string $table The table whose column is to be changed. The method will properly quote the table name.
     * @param string $column The name of the column to be changed. The name will be properly quoted by the method.
     * @param ColumnInterface|string $type The new column type.
     * The {@see \Yiisoft\Db\Query\QueryBuilder::getColumnType()} method will be invoked to convert an abstract column
     * type (if any) into the physical one. Anything not recognized as an abstract type will be kept in the
     * generated SQL. For example, 'string' will be turned into 'varchar(255)', while 'string not null' will become
     * 'varchar(255) not null'.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function alterColumn(string $table, string $column, ColumnInterface|string $type): void
    {
        $comment = null;
        $typeAsString = $type;

        if ($typeAsString instanceof ColumnInterface) {
            $comment = $typeAsString->getComment();
            $typeAsString = $typeAsString->asString();
        }

        $time = $this->beginCommand("Alter column $column in table $table to $typeAsString");

        $this->db->createCommand()->alterColumn($table, $column, $type)->execute();

        if ($comment !== null) {
            $this->db->createCommand()->addCommentOnColumn($table, $column, $comment)->execute();
        }

        $this->endCommand($time);
    }

    /**
     * Builds and executes an SQL statement for creating a primary key.
     *
     * The method will properly quote the table and column names.
     *
     * @param string $table The table that the primary key constraint will be added to.
     * @param string $name The name of the primary key constraint.
     * @param array|string $columns Comma separated string or array of columns that the primary key will consist of.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @psalm-param string[]|string $columns
     */
    public function addPrimaryKey(string $table, string $name, array|string $columns): void
    {
        $time = $this->beginCommand(
            "Add primary key $name on $table (" . implode(',', (array) $columns) . ')'
        );
        $this->db->createCommand()->addPrimaryKey($table, $name, $columns)->execute();
        $this->endCommand($time);
    }

    /**
     * Builds and executes an SQL statement for dropping a primary key.
     *
     * @param string $table The table that the primary key constraint will be removed from.
     * @param string $name The name of the primary key constraint to be removed.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function dropPrimaryKey(string $table, string $name): void
    {
        $time = $this->beginCommand("Drop primary key $name");
        $this->db->createCommand()->dropPrimaryKey($table, $name)->execute();
        $this->endCommand($time);
    }

    /**
     * Builds an SQL statement for adding a foreign key constraint to an existing table.
     *
     * The method will properly quote the table and column names.
     *
     * @param string $table The table that the foreign key constraint will be added to.
     * @param string $name The name of the foreign key constraint.
     * @param array|string $columns The name of the column to that the constraint will be added on. If there are
     * multiple columns, separate them with commas or use an array.
     * @param string $refTable The table that the foreign key references to.
     * @param array|string $refColumns The name of the column that the foreign key references to. If there are multiple
     * columns, separate them with commas or use an array.
     * @param string|null $delete The `ON DELETE` option. Most DBMS support these options: `RESTRICT`, `CASCADE`,
     * `NO ACTION`, `SET DEFAULT`, `SET NULL`.
     * @param string|null $update The `ON UPDATE` option. Most DBMS support these options: `RESTRICT`, `CASCADE`,
     * `NO ACTION`, `SET DEFAULT`, `SET NULL`.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @psalm-param string[]|string $columns
     * @psalm-param string[]|string $refColumns
     */
    public function addForeignKey(
        string $table,
        string $name,
        array|string $columns,
        string $refTable,
        array|string $refColumns,
        string|null $delete = null,
        string|null $update = null
    ): void {
        $time = $this->beginCommand(
            "Add foreign key $name: $table (" . implode(
                ',',
                (array) $columns
            ) . ") references $refTable (" . implode(
                ',',
                (array) $refColumns
            ) . ')'
        );
        $this->db->createCommand()->addForeignKey(
            $table,
            $name,
            $columns,
            $refTable,
            $refColumns,
            $delete,
            $update
        )->execute();
        $this->endCommand($time);
    }

    /**
     * Builds an SQL statement for dropping a foreign key constraint.
     *
     * @param string $table The table whose foreign is to be dropped. The name will be properly quoted by the method.
     * @param string $name The name of the foreign key constraint to be dropped. The method will properly quote the name.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function dropForeignKey(string $table, string $name): void
    {
        $time = $this->beginCommand("Drop foreign key $name from table $table");
        $this->db->createCommand()->dropForeignKey($table, $name)->execute();
        $this->endCommand($time);
    }

    /**
     * Builds and executes an SQL statement for creating a new index.
     *
     * @param string $table The table that the new index will be created for. The table name will be properly quoted by
     * the method.
     * @param string $name The name of the index. The name will be properly quoted by the method.
     * @param array|string $columns The column(s) that should be included in the index. If there are multiple columns,
     * please separate them by commas or use an array. Each column name will be properly quoted by the method. Quoting
     * will be skipped for column names that include a left parenthesis "(".
     * @param string|null $indexType Type of index supported DBMS - for example, `UNIQUE`, `FULLTEXT`, `SPATIAL`,
     * `BITMAP` or `null` as default.
     * @param string|null $indexMethod For setting index organization method (with 'USING', not all DBMS).
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     *
     * @psalm-param string[]|string $columns
     */
    public function createIndex(
        string $table,
        string $name,
        array|string $columns,
        string|null $indexType = null,
        string|null $indexMethod = null
    ): void {
        $time = $this->beginCommand(
            'Create'
            . ($indexType !== null ? ' ' . $indexType : '')
            . " index $name on $table (" . implode(',', (array) $columns) . ')'
            . ($indexMethod !== null ? ' using ' . $indexMethod : '')
        );
        $this->db->createCommand()->createIndex($table, $name, $columns, $indexType, $indexMethod)->execute();
        $this->endCommand($time);
    }

    /**
     * Builds and executes an SQL statement for creating a view.
     *
     * @param string $viewName The name of the view to create.
     * @param QueryInterface|string $subQuery The select statement which defines the view. This can be either a string
     * or a {@see QueryInterface}.
     *
     * @throws InvalidConfigException
     * @throws NotSupportedException If this isn't supported by the underlying DBMS.
     * @throws Exception
     *
     * Note: The method will quote the `viewName` parameter before using it in the generated SQL.
     */
    public function createView(string $viewName, QueryInterface|string $subQuery): void
    {
        $time = $this->beginCommand("Create view $viewName");
        $this->db->createCommand()->createView($viewName, $subQuery)->execute();
        $this->endCommand($time);
    }

    /**
     * Builds and executes an SQL statement for dropping an index.
     *
     * @param string $table The table whose index is to be dropped. The name will be properly quoted by the method.
     * @param string $name The name of the index to be dropped. The name will be properly quoted by the method.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function dropIndex(string $table, string $name): void
    {
        if ($this->hasIndex($table, $name) === false) {
            $time = $this->beginCommand("Drop index $name on $table skipped. Index does not exist.");
            $this->endCommand($time);
            return;
        }

        $time = $this->beginCommand("Drop index $name on $table");
        $this->db->createCommand()->dropIndex($table, $name)->execute();
        $this->endCommand($time);
    }

    /**
     * Builds and executes an SQL statement for dropping a DB view.
     *
     * @param string $viewName The name of the view to be dropped.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException If this isn't supported by the underlying DBMS.
     *
     * Note: The method will quote the `viewName` parameter before using it in the generated SQL.
     */
    public function dropView(string $viewName): void
    {
        $time = $this->beginCommand("Drop view $viewName");
        $this->db->createCommand()->dropView($viewName)->execute();
        $this->endCommand($time);
    }

    /**
     * Builds and execute a SQL statement for adding comment to column.
     *
     * @param string $table The table whose column is to be commented. The method will properly quote the table name.
     * @param string $column The name of the column to be commented. The method will properly quote the column name.
     * @param string $comment The text of the comment to be added. The comment will be properly quoted by the method.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function addCommentOnColumn(string $table, string $column, string $comment): void
    {
        $time = $this->beginCommand("Add comment on column $column");
        $this->db->createCommand()->addCommentOnColumn($table, $column, $comment)->execute();
        $this->endCommand($time);
    }

    /**
     * Builds an SQL statement for adding comment to table.
     *
     * @param string $table The table to be commented. The table name will be properly quoted by the method.
     * @param string $comment The text of the comment to be added. The comment will be properly quoted by the method.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function addCommentOnTable(string $table, string $comment): void
    {
        $time = $this->beginCommand("Add comment on table $table");
        $this->db->createCommand()->addCommentOnTable($table, $comment)->execute();
        $this->endCommand($time);
    }

    /**
     * Builds and execute a SQL statement for dropping comment from column.
     *
     * @param string $table The table whose column is to be commented. The method will properly quote the table name.
     * @param string $column The name of the column to be commented. The method will properly quote the column name.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function dropCommentFromColumn(string $table, string $column): void
    {
        $time = $this->beginCommand("Drop comment from column $column");
        $this->db->createCommand()->dropCommentFromColumn($table, $column)->execute();
        $this->endCommand($time);
    }

    /**
     * Builds a SQL statement for dropping comment from table.
     *
     * @param string $table The table whose column is to be commented. The method will properly quote the table name.
     *
     * @throws Exception
     * @throws InvalidConfigException
     * @throws NotSupportedException
     */
    public function dropCommentFromTable(string $table): void
    {
        $time = $this->beginCommand("drop comment from table $table");
        $this->db->createCommand()->dropCommentFromTable($table)->execute();
        $this->endCommand($time);
    }

    /**
     * Prepares for a command to be executed, and outputs to the console.
     *
     * @param string $description The description for the command, to be output to the console.
     *
     * @return float The time before the command is executed, for the time elapsed to be calculated.
     */
    protected function beginCommand(string $description): float
    {
        $this->informer->beginCommand($description);
        return microtime(true);
    }

    /**
     * Finalizes after the command has been executed, and outputs to the console the time elapsed.
     *
     * @param float $time The time before the command was executed.
     */
    protected function endCommand(float $time): void
    {
        $this->informer->endCommand('Done in ' . sprintf('%.3f', microtime(true) - $time) . 's.');
    }

    private function hasIndex(string $table, string $column): bool
    {
        /** @var Constraint[] $indexes */
        $indexes = $this->db->getSchema()->getTableIndexes($table);

        foreach ($indexes as $index) {
            if ($index->getName() === $column) {
                return true;
            }
        }

        return false;
    }
}
