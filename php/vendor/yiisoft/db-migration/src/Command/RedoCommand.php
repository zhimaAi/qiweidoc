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
use Yiisoft\Db\Migration\Runner\UpdateRunner;
use Yiisoft\Db\Migration\Service\MigrationService;

use function array_keys;
use function array_reverse;
use function count;

/**
 * Redoes the last few migrations.
 *
 * This command will first revert the specified migrations, and then apply them again.
 *
 * For example:
 *
 * ```shell
 * ./yii migrate:redo                                           # redo the last applied migration
 * ./yii migrate:redo --limit=3                                 # redo last 3 applied migrations
 * ./yii migrate:redo --all                                     # redo all migrations
 * ./yii migrate:redo --path=@vendor/yiisoft/rbac-db/migrations # redo the last migration from the directory
 * ./yii migrate:redo --namespace=Yiisoft\\Rbac\\Db\\Migrations # redo the last migration from the namespace
 * ```
 */
#[AsCommand('migrate:redo', 'Redoes the last few migrations.')]
final class RedoCommand extends Command
{
    public function __construct(
        private MigrationService $migrationService,
        private Migrator $migrator,
        private DownRunner $downRunner,
        private UpdateRunner $updateRunner
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Number of migrations to redo.', 1)
            ->addOption('all', 'a', InputOption::VALUE_NONE, 'Redo all migrations.')
            ->addOption('path', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Path to migrations to redo.')
            ->addOption('namespace', 'ns', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Namespace of migrations to redo.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->migrator->setIo($io);
        $this->migrationService->setIo($io);
        $this->downRunner->setIo($io);
        $this->updateRunner->setIo($io);

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
                $io->warning('No migration has been done before.');

                return Command::FAILURE;
            }

            $migrations = array_keys($migrations);
        }

        $n = count($migrations);
        $migrationWord = $n === 1 ? 'migration' : 'migrations';

        $output->writeln("<warning>Total $n $migrationWord to be redone:</warning>\n");

        foreach ($migrations as $i => $migration) {
            $output->writeln("\t<info>" . ($i + 1) . ". $migration</info>");
        }

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion(
            "\n<fg=cyan>Redo the above $migrationWord y/n: ",
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

            foreach (array_reverse($instances) as $i => $instance) {
                try {
                    $this->updateRunner->run($instance, $n - $i);
                } catch (Throwable $e) {
                    $output->writeln("\n<fg=yellow> >>> Total $i out of $n $migrationWas applied.</>\n");
                    $io->error($i > 0 ? 'Reverted but partially applied.' : 'Reverted but not applied.');

                    throw $e;
                }
            }

            $output->writeln("\n<info> >>> $n $migrationWas redone.</info>\n");
            $io->success('Migration redone successfully.');
        }

        $this->migrationService->databaseConnection();

        return Command::SUCCESS;
    }
}
