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
use Yiisoft\Db\Migration\Runner\UpdateRunner;
use Yiisoft\Db\Migration\Service\MigrationService;

use function array_slice;
use function count;
use function strlen;

/**
 * Applies new migrations.
 *
 * For example,
 *
 * ```shell
 * ./yii migrate:up                                           # apply all new migrations
 * ./yii migrate:up --limit=3                                 # apply the first 3 new migrations
 * ./yii migrate:up --path=@vendor/yiisoft/rbac-db/migrations # apply new migrations from the directory
 * ./yii migrate:up --namespace=Yiisoft\\Rbac\\Db\\Migrations # apply new migrations from the namespace
 *
 * # apply new migrations from multiple directories and namespaces
 * ./yii migrate:up --path=@vendor/yiisoft/rbac-db/migrations --path=@vendor/yiisoft/cache-db/migrations
 * ./yii migrate:up --namespace=Yiisoft\\Rbac\\Db\\Migrations --namespace=Yiisoft\\Cache\\Db\\Migrations
 * ```
 */
#[AsCommand('migrate:up', 'Applies new migrations.')]
final class UpdateCommand extends Command
{
    public function __construct(
        private UpdateRunner $updateRunner,
        private MigrationService $migrationService,
        private Migrator $migrator
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('limit', 'l', InputOption::VALUE_REQUIRED, 'Number of migrations to apply.')
            ->addOption('path', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Path to migrations to apply.')
            ->addOption('namespace', 'ns', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Namespace of migrations to apply.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->migrator->setIo($io);
        $this->migrationService->setIo($io);
        $this->updateRunner->setIo($io);

        /** @var string[] $paths */
        $paths = $input->getOption('path');

        /** @var string[] $namespaces */
        $namespaces = $input->getOption('namespace');

        if (!empty($paths) || !empty($namespaces)) {
            $this->migrationService->setSourcePaths($paths);
            $this->migrationService->setSourceNamespaces($namespaces);
        }

        if ($this->migrationService->before(self::getDefaultName() ?? '') === Command::INVALID) {
            return Command::INVALID;
        }

        $limit = $input->getOption('limit');

        if ($limit !== null) {
            $limit = (int)$limit;

            if ($limit <= 0) {
                $io->error('The limit option must be greater than 0.');
                $this->migrationService->databaseConnection();

                return Command::INVALID;
            }
        }

        $migrations = $this->migrationService->getNewMigrations();

        if (empty($migrations)) {
            $output->writeln("<fg=green> >>> No new migrations found.</>\n");
            $io->success('Your system is up-to-date.');
            $this->migrationService->databaseConnection();

            return Command::SUCCESS;
        }

        $migrationsCount = count($migrations);
        $migrationWord = $migrationsCount === 1 ? 'migration' : 'migrations';

        if ($limit !== null && $migrationsCount > $limit) {
            $migrations = array_slice($migrations, 0, $limit);

            $output->writeln("<fg=yellow>Total $limit out of $migrationsCount new $migrationWord to be applied:</>\n");
        } else {
            $output->writeln("<fg=yellow>Total $migrationsCount new $migrationWord to be applied:</>\n");
        }

        foreach ($migrations as $i => $migration) {
            $nameLimit = $this->migrator->getMigrationNameLimit();

            if (strlen($migration) > $nameLimit) {
                $output->writeln(
                    "\n<fg=red>The migration name '$migration' is too long. Its not possible to apply " .
                    'this migration.</>'
                );

                return Command::INVALID;
            }

            $output->writeln("\t<fg=yellow>" . ($i + 1) . ". $migration</>");
        }

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $question = new ConfirmationQuestion(
            "\n<fg=cyan>Apply the above $migrationWord y/n: ",
            true
        );

        if ($helper->ask($input, $output, $question)) {
            $instances = $this->migrationService->makeMigrations($migrations);
            $migrationWas = ($migrationsCount === 1 ? 'migration was' : 'migrations were');

            foreach ($instances as $i => $instance) {
                try {
                    $this->updateRunner->run($instance, $i + 1);
                } catch (Throwable $e) {
                    $output->writeln("\n<fg=yellow> >>> Total $i out of $migrationsCount new $migrationWas applied.</>\n");
                    $io->error($i > 0 ? 'Partially updated.' : 'Not updated.');

                    throw $e;
                }
            }

            $output->writeln("\n<fg=green> >>> Total $migrationsCount new $migrationWas applied.</>\n");
            $io->success('Updated successfully.');
        }

        $this->migrationService->databaseConnection();

        return Command::SUCCESS;
    }
}
