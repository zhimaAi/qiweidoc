<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Db\Migration\Migrator;
use Yiisoft\Db\Migration\Service\MigrationService;

use function count;

/**
 * Displays not yet applied migrations.
 *
 * This command will show the new migrations that have not been applied yet.
 *
 * For example:
 *
 * ```shell
 * ./yii migrate:new                                           # first 10 new migrations
 * ./yii migrate:new --limit=5                                 # first 5 new migrations
 * ./yii migrate:new --all                                     # all new migrations
 * ./yii migrate:new --path=@vendor/yiisoft/rbac-db/migrations # new migrations from the directory
 * ./yii migrate:new --namespace=Yiisoft\\Rbac\\Db\\Migrations # new migrations from the namespace
 *
 * # new migrations from multiple directories and namespaces
 * ./yii migrate:new --path=@vendor/yiisoft/rbac-db/migrations --path=@vendor/yiisoft/cache-db/migrations
 * ./yii migrate:new --namespace=Yiisoft\\Rbac\\Db\\Migrations --namespace=Yiisoft\\Cache\\Db\\Migrations
 * ```
 */
#[AsCommand('migrate:new', 'Displays not yet applied migrations.')]
final class NewCommand extends Command
{
    public function __construct(
        private MigrationService $migrationService,
        private Migrator $migrator
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Number of migrations to display.', 10)
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'All new migrations.')
            ->addOption('path', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Path to migrations to display.')
            ->addOption('namespace', 'ns', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Namespace of migrations to display.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->migrator->setIo($io);
        $this->migrationService->setIo($io);

        /** @var string[] $paths */
        $paths = $input->getOption('path');

        /** @var string[] $namespaces */
        $namespaces = $input->getOption('namespace');

        if (!empty($paths) || !empty($namespaces)) {
            $this->migrationService->setSourcePaths($paths);
            $this->migrationService->setSourceNamespaces($namespaces);
        }

        $this->migrationService->before(self::getDefaultName() ?? '');

        $limit = !$input->getOption('all')
            ? (int)$input->getOption('limit')
            : null;

        if ($limit !== null && $limit <= 0) {
            $io->error('The limit option must be greater than 0.');
            $this->migrationService->databaseConnection();

            return Command::INVALID;
        }

        $migrations = $this->migrationService->getNewMigrations();

        if (empty($migrations)) {
            $io->warning('No new migrations found. Your system is up-to-date.');
            $this->migrationService->databaseConnection();

            return Command::FAILURE;
        }

        $n = count($migrations);
        $migrationWord = $n === 1 ? 'migration' : 'migrations';

        if ($limit !== null && $n > $limit) {
            $migrations = array_slice($migrations, 0, $limit);

            $io->warning("Showing $limit out of $n new $migrationWord:\n");
        } else {
            $io->section("Found $n new $migrationWord:");
        }

        foreach ($migrations as $i => $migration) {
            $output->writeln("<info>\t" . ($i + 1) . ". $migration</info>");
        }

        $this->migrationService->databaseConnection();

        return Command::SUCCESS;
    }
}
