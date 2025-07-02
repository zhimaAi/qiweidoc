<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;
use Yiisoft\Db\Migration\Migrator;
use Yiisoft\Db\Migration\Runner\DownRunner;
use Yiisoft\Db\Migration\Service\MigrationService;

use function array_keys;
use function array_slice;
use function count;

/**
 * Reverts the specified number of latest migrations.
 *
 * For example:
 *
 * ```shell
 * ./yii migrate:down                                           # revert the last migration
 * ./yii migrate:down --limit=3                                 # revert last 3 migrations
 * ./yii migrate:down --all                                     # revert all migrations
 * ./yii migrate:down --path=@vendor/yiisoft/rbac-db/migrations # revert the last migration from the directory
 * ./yii migrate:down --namespace=Yiisoft\\Rbac\\Db\\Migrations # revert the last migration from the namespace
 *
 * # revert migrations from multiple directories and namespaces
 * ./yii migrate:down --path=@vendor/yiisoft/rbac-db/migrations --path=@vendor/yiisoft/cache-db/migrations
 * ./yii migrate:down --namespace=Yiisoft\\Rbac\\Db\\Migrations --namespace=Yiisoft\\Cache\\Db\\Migrations
 * ```
 */
#[AsCommand('migrate:down', 'Reverts the specified number of latest migrations.')]
final class DownCommand extends Command
{
    public function __construct(
        private DownRunner $downRunner,
        private MigrationService $migrationService,
        private Migrator $migrator
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Number of migrations to revert.', 1)
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Revert all migrations.')
            ->addOption('path', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Path to migrations to revert.')
            ->addOption('namespace', 'ns', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Namespace of migrations to revert.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->migrator->setIo($io);
        $this->migrationService->setIo($io);
        $this->downRunner->setIo($io);

        $this->migrationService->before(self::getDefaultName() ?? '');

        $limit = !$input->getOption('all')
            ? (int)$input->getOption('limit')
            : null;

        if ($limit !== null && $limit <= 0) {
            $io->error('The limit option must be greater than 0.');
            $this->migrationService->databaseConnection();

            return Command::INVALID;
        }

        /** @var string[] $paths */
        $paths = $input->getOption('path');
        /** @var string[] $namespaces */
        $namespaces = $input->getOption('namespace');

        if (!empty($paths) || !empty($namespaces)) {
            $migrations = $this->migrator->getHistory();
            $migrations = array_keys($migrations);
            $migrations = $this->migrationService->filterMigrations($migrations, $namespaces, $paths);

            if (empty($migrations)) {
                $io->warning('No applied migrations found.');

                return Command::FAILURE;
            }

            if ($limit !== null) {
                $migrations = array_slice($migrations, 0, $limit);
            }
        } else {
            $migrations = $this->migrator->getHistory($limit);

            if (empty($migrations)) {
                $output->writeln("<fg=yellow> >>> Apply at least one migration first.</>\n");
                $io->warning('No migration has been done before.');

                return Command::FAILURE;
            }

            $migrations = array_keys($migrations);
        }

        $n = count($migrations);
        $migrationWord = $n === 1 ? 'migration' : 'migrations';

        $output->writeln(
            "<fg=yellow>Total $n $migrationWord to be reverted:</>\n"
        );

        foreach ($migrations as $i => $migration) {
            $output->writeln("\t<fg=yellow>" . ($i + 1) . ". $migration</>");
        }

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $question = new ConfirmationQuestion(
            "\n<fg=cyan>Revert the above $migrationWord y/n: ",
            true
        );

        if ($helper->ask($input, $output, $question)) {
            $instances = $this->migrationService->makeRevertibleMigrations($migrations);
            $migrationWas = ($n === 1 ? 'migration was' : 'migrations were');

            foreach ($instances as $i => $instance) {
                try {
                    $this->downRunner->run($instance, $i + 1);
                } catch (Throwable $e) {
                    $output->writeln("\n<fg=yellow> >>> Total $i out of $n $migrationWas reverted.</>\n");
                    $io->error($i > 0 ? 'Partially reverted.' : 'Not reverted.');

                    throw $e;
                }
            }

            $output->writeln("\n<fg=green> >>> [OK] $n $migrationWas reverted.\n");
            $io->success('Migrated down successfully.');
        }

        $this->migrationService->databaseConnection();

        return Command::SUCCESS;
    }
}
