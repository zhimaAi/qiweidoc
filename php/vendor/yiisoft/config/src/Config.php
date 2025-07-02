<?php

declare(strict_types=1);

namespace Yiisoft\Config;

use ErrorException;

use Yiisoft\Config\Composer\Options;

use function extract;
use function func_get_arg;
use function restore_error_handler;
use function set_error_handler;
use function sprintf;
use function substr;

/**
 * Config takes merge plan prepared by {@see \Yiisoft\Config\Composer\EventHandler}
 * and executes actual merge for the config group specified.
 */
final class Config implements ConfigInterface
{
    private readonly Merger $merger;
    private readonly FilesExtractor $filesExtractor;
    private bool $isBuildingParams = false;

    /**
     * @psalm-var array<string, array>
     */
    private array $build = [];

    /**
     * @param ConfigPaths $paths The config paths instance.
     * @param string|null $environment The environment name.
     * @param object[] $modifiers Modifiers that affect merge process.
     * @param string|null $paramsGroup Group name for `$params`. If it is `null`, then `$params` will be empty array.
     * @param string $mergePlanFile The merge plan filepath.
     *
     * @throws ErrorException If the environment does not exist.
     */
    public function __construct(
        ConfigPaths $paths,
        ?string $environment = null,
        array $modifiers = [],
        private readonly ?string $paramsGroup = 'params',
        string $mergePlanFile = Options::DEFAULT_MERGE_PLAN_FILE,
    ) {
        $environment = empty($environment) ? Options::DEFAULT_ENVIRONMENT : $environment;

        /** @psalm-suppress UnresolvableInclude, MixedArgument */
        $mergePlan = new MergePlan(require $paths->absolute($mergePlanFile));

        if (!$mergePlan->hasEnvironment($environment)) {
            $this->throwException(sprintf('The "%s" configuration environment does not exist.', $environment));
        }

        $dataModifiers = new DataModifiers($modifiers);
        $this->merger = new Merger($paths, $dataModifiers);
        $this->filesExtractor = new FilesExtractor($paths, $mergePlan, $dataModifiers, $environment);
    }

    /**
     * {@inheritDoc}
     *
     * @throws ErrorException If the group does not exist or an error occurred during the build.
     */
    public function get(string $group): array
    {
        if (isset($this->build[$group])) {
            return $this->build[$group];
        }

        $this->runBuildParams();

        $this->merger->reset();
        $this->build[$group] = $this->buildGroup($group);

        return $this->build[$group];
    }

    public function has(string $group): bool
    {
        return $this->filesExtractor->hasGroup($group);
    }

    /**
     * @throws ErrorException If an error occurred during the build.
     */
    private function runBuildParams(): void
    {
        if ($this->paramsGroup !== null && !isset($this->build[$this->paramsGroup])) {
            $this->isBuildingParams = true;
            $this->build[$this->paramsGroup] = $this->buildGroup($this->paramsGroup);
            $this->isBuildingParams = false;
        }
    }

    /**
     * Builds the configuration of the group.
     *
     * @param string $group The group name.
     *
     * @throws ErrorException If an error occurred during the build.
     */
    private function buildGroup(string $group, array $result = [], ?string $originalGroup = null): array
    {
        foreach ($this->filesExtractor->extract($group) as $file => $context) {
            if ($context->isVariable()) {
                $variable = $this->prepareVariable($file, $group);
                $result = $this->buildGroup($variable, $result, $originalGroup ?? $group);
            } else {
                $result = $this->merger->merge(
                    $context->setOriginalGroup($originalGroup ?? $group),
                    $result,
                    $this->buildFile($file),
                );
            }
        }

        return $result;
    }

    /**
     * Checks the configuration variable and returns its name.
     *
     * @param string $variable The variable.
     * @param string $group The group name.
     *
     * @throws ErrorException If the variable name is not valid.
     *
     * @return string The variable name.
     */
    private function prepareVariable(string $variable, string $group): string
    {
        $name = substr($variable, 1);

        if ($name === $group) {
            $this->throwException(sprintf(
                'The variable "%s" must not be located inside the "%s" config group.',
                "$variable",
                "$name",
            ));
        }

        return $name;
    }

    /**
     * Builds the configuration from the file.
     *
     * @param string $filePath The file path.
     *
     * @throws ErrorException If an error occurred during the build.
     *
     * @return array The configuration from the file.
     */
    private function buildFile(string $filePath): array
    {
        $scopeRequire = static function (): array {
            /** @psalm-suppress InvalidArgument, MissingClosureParamType */
            set_error_handler(static function (int $errorNumber, string $errorString, string $errorFile, int $errorLine) {
                throw new ErrorException($errorString, $errorNumber, 0, $errorFile, $errorLine);
            });

            /** @psalm-suppress MixedArgument, PossiblyFalseArgument */
            extract(func_get_arg(1), EXTR_SKIP);

            /**
             * @psalm-suppress UnresolvableInclude
             * @psalm-var array
             */
            $result = require func_get_arg(0);
            restore_error_handler();
            return $result;
        };

        $scope = [];

        if (!$this->isBuildingParams) {
            $scope['config'] = $this;
            $scope['params'] = $this->paramsGroup === null ? [] : $this->build[$this->paramsGroup];
        }

        /** @psalm-suppress TooManyArguments */
        return $scopeRequire($filePath, $scope);
    }

    /**
     * @throws ErrorException
     */
    private function throwException(string $message): void
    {
        throw new ErrorException($message, 0, E_USER_ERROR);
    }
}
