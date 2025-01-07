<?php

namespace Common\Command;

use Common\Module;
use Common\Yii;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Yii\Console\ExitCode;

#[AsCommand(name: 'reset-table-owner', description: '修复由于某些未知原因导致表拥有者不是指定模块名的问题', hidden: false)]
class ResetTableOwnerCommand  extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $moduleName = Module::getCurrentModuleName();

        $sql = <<<SQL
DO $$
DECLARE
    cmd text;
BEGIN
    FOR cmd IN
        SELECT 'ALTER TABLE ' || quote_ident(schemaname) || '.' || quote_ident(tablename) || ' OWNER TO {$this->moduleName};'
        FROM pg_tables
        WHERE schemaname = '{$moduleName}'
    LOOP
        EXECUTE cmd;
    END LOOP;
END $$;
SQL;
        Yii::db()->createCommand($sql)->execute();

        return ExitCode::OK;
    }
}
