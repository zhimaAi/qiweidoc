<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Db\Migration\Migrator;
use Yiisoft\Db\Migration\Service\Generate\CreateService;
use Yiisoft\Db\Migration\Service\MigrationService;

use function file_put_contents;
use function implode;
use function in_array;
use function is_dir;
use function preg_match;
use function strlen;

/**
 * Creates a new migration.
 *
 * This command creates a new migration using the available migration template.
 *
 * To use it, configure migrations paths (`newMigrationPath` and `sourcePaths`) in `params.php` file, in your application.
 *
 * ```php
 * 'yiisoft/db-migration' => [
 *     'newMigrationNamespace' => '',
 *     'newMigrationPath' => '',
 *     'sourceNamespaces' => [],
 *     'sourcePaths' => [],
 * ],
 * ```
 *
 * After using this command, developers should modify the created migration skeleton by filling up the actual
 * migration logic.
 *
 * ```shell
 * ./yii migrate:create table --command=table
 * ```
 *
 * To generate a namespaced migration, you should specify a namespace before the migration's name.
 *
 * Note that backslash (`\`) is usually considered a special character in the shell, so you need to escape it properly
 * to avoid shell errors or incorrect behavior.
 *
 * For example:
 *
 * ```shell
 * ./yii migrate:create post --command=table --namespace=Yiisoft\\Db\\Migration\\Migration
 * ./yii migrate:create post --command=table --path=@root/migrations/blog
 * ```
 *
 * In case {@see MigrationService::$newMigrationPath} is not set, and no namespace is provided,
 * {@see MigrationService::$newMigrationNamespace} will be used.
 */
#[AsCommand('migrate:create', 'Creates a new migration.')]
final class CreateCommand extends Command
{
    private const AVAILABLE_COMMANDS = ['create', 'table', 'dropTable', 'addColumn', 'dropColumn', 'junction'];

    public function __construct(
        private CreateService $createService,
        private MigrationService $migrationService,
        private Migrator $migrator
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'Table name to generate migration for.')
            ->addOption('command', 'c', InputOption::VALUE_REQUIRED, 'Command to execute.', 'create')
            ->addOption('fields', 'f', InputOption::VALUE_REQUIRED, 'Table fields to generate.')
            ->addOption('table-comment', null, InputOption::VALUE_REQUIRED, 'Table comment.')
            ->addOption('and', null, InputOption::VALUE_REQUIRED, 'And junction.')
            ->addOption('path', null, InputOption::VALUE_REQUIRED, 'Path to migration directory.')
            ->addOption('namespace', 'ns', InputOption::VALUE_REQUIRED, 'Migration file namespace.')
            ->setHelp('This command generates new migration file.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->migrator->setIo($io);
        $this->migrationService->setIo($io);
        $this->createService->setIo($io);

        /** @var string|null $path */
        $path = $input->getOption('path');

        /** @var string|null $namespace */
        $namespace = $input->getOption('namespace');

        if ($path !== null || $namespace !== null) {
            $this->migrationService->setNewMigrationPath((string) $path);
            $this->migrationService->setNewMigrationNamespace((string) $namespace);
        } else {
            $namespace = $this->migrationService->getNewMigrationNamespace();
        }

        if ($this->migrationService->before(self::getDefaultName() ?? '') === Command::INVALID) {
            return Command::INVALID;
        }

        /** @var string $table */
        $table = $input->getArgument('name');

        if (!preg_match('/^[\w\\\\]+$/', $table)) {
            $io->error(
                'The migration name should contain letters, digits, underscore and/or backslash characters only.'
            );

            return Command::INVALID;
        }

        /** @var string $command */
        $command = $input->getOption('command');

        if (!in_array($command, self::AVAILABLE_COMMANDS, true)) {
            $io->error(
                "Command not found \"$command\". Available commands: " . implode(', ', self::AVAILABLE_COMMANDS) . '.'
            );

            return Command::INVALID;
        }

        /** @var string|null $and */
        $and = $input->getOption('and');
        $name = $this->generateName($command, $table, $and);

        $className = $this->migrationService->generateClassName($name);
        $nameLimit = $this->migrator->getMigrationNameLimit();

        if ($nameLimit !== 0 && strlen($className) > $nameLimit) {
            $io->error('The migration name is too long.');

            return Command::INVALID;
        }

        $migrationPath = $this->migrationService->findMigrationPath();

        if (!is_dir($migrationPath)) {
            $io->error("Invalid path directory $migrationPath");

            return Command::INVALID;
        }

        /** @var QuestionHelper $helper */
        $helper = $this->getHelper('question');

        $question = new ConfirmationQuestion(
            "\n<fg=cyan>Create new migration y/n: </>",
            false,
        );

        if ($helper->ask($input, $output, $question)) {
            /** @var string|null $fields */
            $fields = $input->getOption('fields');
            /** @var string|null $tableComment */
            $tableComment = $input->getOption('table-comment');

            $content = $this->createService->run(
                $command,
                $table,
                $className,
                $namespace,
                $fields,
                $and,
                $tableComment,
            );

            $file = $migrationPath . DIRECTORY_SEPARATOR . $className . '.php';

            file_put_contents($file, $content, LOCK_EX);

            $output->writeln("\n\t<info>$className</info>");
            $output->writeln("\n");
            $io->success('New migration created successfully.');
        }

        $this->migrationService->databaseConnection();

        return Command::SUCCESS;
    }

    private function generateName(string $command, string $name, string|null $and): string
    {
        $result = '';

        return match ($command) {
            'create' => $name,
            'table' => 'Create_' . $name . '_Table',
            'dropTable' => 'Drop_' . $name . '_Table',
            'addColumn' => 'Add_Column_' . $name,
            'dropColumn' => 'Drop_Column_' . $name,
            'junction' => 'Junction_Table_For_' . $name . '_And_' . (string) $and . '_Tables',
            default => $result,
        };
    }
}
