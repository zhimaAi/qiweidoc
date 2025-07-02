<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration;

/**
 * The RevertibleMigrationInterface defines the minimum set of methods to be implemented
 * by a revertible database migration.
 *
 * Each migration class should provide the {@see up()} method containing the logic for "upgrading" the database.
 * and the {@see down()} method for the "downgrading" logic. If you do not need the migration to be revertible,
 * use {@see MigrationInterface}.
 */
interface RevertibleMigrationInterface extends MigrationInterface
{
    /**
     * This method contains the logic to be executed when removing this migration.
     *
     * The DB logic implemented here will be enclosed within a DB transaction.
     * Child classes may implement this method instead of {@see down()} if the DB logic needs to be within a
     * transaction.
     *
     * Note: Not all DBMS support transactions. And some DB queries cannot be put into a transaction. For some examples,
     * please refer to [implicit commit](https://dev.mysql.com/doc/refman/5.7/en/implicit-commit.html).
     */
    public function down(MigrationBuilder $b): void;
}
