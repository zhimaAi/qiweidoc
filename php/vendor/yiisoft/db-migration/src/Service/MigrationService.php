<?php

declare(strict_types=1);

namespace Yiisoft\Db\Migration\Service;

use Composer\Autoload\ClassLoader;
use LogicException;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Injector\Injector;
use Yiisoft\Db\Migration\MigrationInterface;
use Yiisoft\Db\Migration\Migrator;
use Yiisoft\Db\Migration\RevertibleMigrationInterface;

use function array_map;
use function array_unique;
use function array_values;
use function closedir;
use function dirname;
use function gmdate;
use function in_array;
use function is_dir;
use function is_file;
use function krsort;
use function ksort;
use function opendir;
use function preg_match;
use function preg_replace;
use function readdir;
use function realpath;
use function reset;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function strlen;
use function strrchr;
use function strrpos;
use function substr;
use function trim;
use function ucwords;

final class MigrationService
{
    private string $newMigrationNamespace = '';
    private string $newMigrationPath = '';
    /** @var string[] */
    private array $sourceNamespaces = [];
    /** @var string[] */
    private array $sourcePaths = [];
    private string $version = '1.0';
    private ?SymfonyStyle $io = null;

    public function __construct(
        private ConnectionInterface $db,
        private Injector $injector,
        private Migrator $migrator
    ) {
    }

    public function setIo(?SymfonyStyle $io): void
    {
        $this->io = $io;
    }

    /**
     * This method is invoked right before an action is to be executed (after all possible filters.)
     *
     * It checks the existence of the {@see $newMigrationPath}, {@see $sourcePaths}, {@see $newMigrationNamespace},
     * {@see $sourceNamespaces}.
     *
     * @return int Whether the action should continue to be executed.
     */
    public function before(string $commandName): int
    {
        switch ($commandName) {
            case 'migrate:create':
                if (empty($this->newMigrationNamespace) && empty($this->newMigrationPath)) {
                    $this->io?->error(
                        'One of `newMigrationNamespace` or `newMigrationPath` should be specified.'
                    );

                    return Command::INVALID;
                }

                if (!empty($this->newMigrationNamespace) && !empty($this->newMigrationPath)) {
                    $this->io?->error(
                        'Only one of `newMigrationNamespace` or `newMigrationPath` should be specified.'
                    );

                    return Command::INVALID;
                }
                break;
            case 'migrate:up':
                if (empty($this->sourceNamespaces) && empty($this->sourcePaths)) {
                    $this->io?->error(
                        'At least one of `sourceNamespaces` or `sourcePaths` should be specified.'
                    );

                    return Command::INVALID;
                }
                break;
        }

        return Command::SUCCESS;
    }

    /**
     * Returns the migrations that are not applied.
     *
     * @return string[] List of new migrations.
     *
     * @psalm-return list<class-string>
     */
    public function getNewMigrations(): array
    {
        $applied = [];

        foreach ($this->migrator->getHistory() as $class => $time) {
            $applied[trim($class, '\\')] = true;
        }

        $migrationPaths = [];

        foreach ($this->sourcePaths as $path) {
            $migrationPaths[] = [$path, ''];
        }

        foreach ($this->sourceNamespaces as $namespace) {
            $migrationPaths[] = [$this->getNamespacePath($namespace), $namespace];
        }

        $migrations = [];
        foreach ($migrationPaths as $item) {
            [$sourcePath, $namespace] = $item;

            if (!is_dir($sourcePath)) {
                continue;
            }

            $handle = opendir($sourcePath);
            while (($file = readdir($handle)) !== false) {
                if ($file === '.' || $file === '..') {
                    continue;
                }

                $path = $sourcePath . DIRECTORY_SEPARATOR . $file;

                if (is_file($path) && preg_match('/^(M(\d{12}).*)\.php$/s', $file, $matches)) {
                    [, $class, $time] = $matches;

                    if (!empty($namespace)) {
                        $class = $namespace . '\\' . $class;
                    }

                    /** @psalm-var class-string $class */

                    if (!isset($applied[$class])) {
                        $migrations[$time . '\\' . $class] = $class;
                    }
                }
            }
            closedir($handle);
        }
        ksort($migrations);

        return array_values($migrations);
    }

    /**
     * List of namespaces containing the migration update classes.
     *
     * @psalm-param string[] $value
     */
    public function setSourceNamespaces(array $value): void
    {
        $this->sourceNamespaces = $value;
    }

    /**
     * The directory containing the migration update classes.
     *
     * Migration classes located on this path should be declared without a namespace.
     * Use {@see $newMigrationNamespace} property in case you are using namespaced migrations.
     *
     * If you have set up {@see $newMigrationNamespace}, you may set this field to `null` to disable usage of migrations
     * that are not namespaced.
     *
     * In general, to load migrations from different locations, {@see $newMigrationNamespace} is the preferable solution as the
     * migration name contains the origin of the migration in the history, which is not the case when using multiple
     * migration paths.
     *
     *
     * {@see $newMigrationNamespace}
     *
     * @psalm-param string[] $value
     */
    public function setSourcePaths(array $value): void
    {
        $this->sourcePaths = $value;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function databaseConnection(): void
    {
        $this->io?->writeln(
            "<fg=cyan>\nDatabase connection: {$this->db->getDriverName()}.</>"
        );
    }

    /**
     * Creates a new migration instance.
     *
     * @param string $class The migration class name.
     *
     * @return object The migration instance.
     */
    private function makeMigrationInstance(string $class): object
    {
        $class = trim($class, '\\');

        if (!str_contains($class, '\\')) {
            $isIncluded = false;
            foreach ($this->sourcePaths as $path) {
                $file = $path . DIRECTORY_SEPARATOR . $class . '.php';

                if (is_file($file)) {
                    /** @psalm-suppress UnresolvableInclude */
                    require_once $file;
                    $isIncluded = true;
                    break;
                }
            }

            if (!$isIncluded) {
                throw new RuntimeException('Migration file not found.');
            }
        }

        /** @var class-string $class */
        $class = '\\' . $class;

        return $this->injector->make($class);
    }

    public function makeMigration(string $class): MigrationInterface
    {
        $migration = $this->makeMigrationInstance($class);

        if (!$migration instanceof MigrationInterface) {
            throw new RuntimeException("Migration $class does not implement MigrationInterface.");
        }

        return $migration;
    }

    /**
     * @param string[] $classes
     *
     * @psalm-param list<class-string> $classes
     *
     * @return MigrationInterface[]
     *
     * @psalm-return list<MigrationInterface>
     */
    public function makeMigrations(array $classes): array
    {
        return array_map(
            $this->makeMigration(...),
            $classes
        );
    }

    public function makeRevertibleMigration(string $class): RevertibleMigrationInterface
    {
        $migration = $this->makeMigrationInstance($class);

        if (!$migration instanceof RevertibleMigrationInterface) {
            throw new RuntimeException("Migration $class does not implement RevertibleMigrationInterface.");
        }

        return $migration;
    }

    /**
     * @param string[] $classes
     *
     * @psalm-param list<class-string> $classes
     *
     * @return RevertibleMigrationInterface[]
     *
     * @psalm-return list<RevertibleMigrationInterface>
     */
    public function makeRevertibleMigrations(array $classes): array
    {
        return array_map(
            $this->makeRevertibleMigration(...),
            $classes
        );
    }

    public function setNewMigrationNamespace(string $value): void
    {
        $this->newMigrationNamespace = $value;
    }

    public function setNewMigrationPath(string $value): void
    {
        $this->newMigrationPath = $value;
    }

    /**
     * Returns namespace to create migration
     *
     * @return string
     */
    public function getNewMigrationNamespace(): string
    {
        return $this->newMigrationNamespace;
    }

    /**
     * Generates class base name from migration name from user input.
     *
     * @param string $name Migration name from the user input.
     *
     * @return string The class base name.
     */
    public function generateClassName(string $name): string
    {
        return 'M' . gmdate('ymdHis')
            . str_replace(' ', '', ucwords(preg_replace('/[^a-z0-9]+/i', ' ', $name)));
    }

    /**
     * Finds the file path for migration namespace or path.
     *
     * @return string The migration file path.
     */
    public function findMigrationPath(): string
    {
        return empty($this->newMigrationNamespace)
            ? $this->newMigrationPath
            : $this->getNamespacePath($this->newMigrationNamespace);
    }

    /**
     * Filters migrations by namespaces and paths.
     *
     * @param string[] $classes Migration classes to be filtered.
     * @param string[] $namespaces Namespaces to filter by.
     * @param string[] $paths Paths to filter by.
     *
     * @return string[] Filtered migration classes.
     *
     * @psalm-param list<class-string> $classes
     *
     * @psalm-return list<class-string>
     */
    public function filterMigrations(array $classes, array $namespaces = [], array $paths = []): array
    {
        $result = [];
        $pathNamespaces = [];

        foreach ($paths as $path) {
            $pathNamespaceList = $this->getNamespacesFromPath($path);

            if (!empty($pathNamespaceList)) {
                $pathNamespaces[$path] = $pathNamespaceList;
            }
        }

        $namespaces = array_map(static fn ($namespace) => trim($namespace, '\\'), $namespaces);
        $namespaces = array_unique($namespaces);

        foreach ($classes as $class) {
            $classNamespace = substr($class, 0, strrpos($class, '\\') ?: 0);

            if ($classNamespace === '') {
                continue;
            }

            if (in_array($classNamespace, $namespaces, true)) {
                $result[] = $class;
                continue;
            }

            foreach ($pathNamespaces as $path => $pathNamespaceList) {
                /** @psalm-suppress RedundantCondition */
                if (!in_array($classNamespace, $pathNamespaceList, true)) {
                    continue;
                }

                $className = substr(strrchr($class, '\\'), 1);
                $file = $path . DIRECTORY_SEPARATOR . $className . '.php';

                if (is_file($file)) {
                    $result[] = $class;
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Returns the file path matching the give namespace.
     *
     * @param string $namespace Namespace.
     *
     * @return string File path.
     */
    private function getNamespacePath(string $namespace): string
    {
        /**
         * @psalm-suppress UnresolvableInclude
         * @psalm-var array<string, list<string>> $map
         */
        $map = require $this->getVendorDir() . '/composer/autoload_psr4.php';

        foreach ($map as $mapNamespace => $mapDirectories) {
            if (str_starts_with($namespace, trim($mapNamespace, '\\'))) {
                return reset($mapDirectories) . '/' . str_replace('\\', '/', substr($namespace, strlen($mapNamespace)));
            }
        }

        throw new LogicException("Invalid namespace: \"$namespace\".");
    }

    /**
     * Returns the namespaces matching the give file path.
     *
     * @param string $path File path.
     *
     * @return string[] Namespaces.
     */
    private function getNamespacesFromPath(string $path): array
    {
        $namespaces = [];
        $path = realpath($path) . DIRECTORY_SEPARATOR;

        /**
         * @psalm-suppress UnresolvableInclude
         * @psalm-var array<string, list<string>> $map
         */
        $map = require $this->getVendorDir() . '/composer/autoload_psr4.php';

        foreach ($map as $namespace => $directories) {
            foreach ($directories as $directory) {
                $directory = realpath($directory) . DIRECTORY_SEPARATOR;

                if (str_starts_with($path, $directory)) {
                    $length = strlen($directory);
                    $pathNamespace = $namespace . str_replace('/', '\\', substr($path, $length));
                    $namespaces[$length][$namespace] = rtrim($pathNamespace, '\\');
                }
            }
        }

        if (empty($namespaces)) {
            return [];
        }

        krsort($namespaces);

        return array_values(reset($namespaces));
    }

    private function getVendorDir(): string
    {
        $class = new ReflectionClass(ClassLoader::class);
        return dirname($class->getFileName(), 2);
    }
}
