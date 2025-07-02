<?php

declare(strict_types=1);

namespace Yiisoft\Config\Command;

use Composer\Command\BaseCommand;
use Composer\Util\Filesystem;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Yiisoft\Config\Composer\PackageFile;
use Yiisoft\Config\Composer\PackageFilesProcess;

use function str_replace;
use function pathinfo;
use function strlen;
use function substr;
use function trim;

/**
 * `CopyCommand` copies the package configuration files from the vendor to the application.
 */
final class CopyCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this
            ->setName('yii-config-copy')
            ->setDescription('Copying package configuration files to the application.')
            ->setHelp('This command copies the package configuration files from the vendor to the application.')
            ->addArgument('package', InputArgument::REQUIRED, 'Package')
            ->addArgument('target', InputArgument::OPTIONAL, 'Target directory', '/')
            ->addArgument('files', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'Files')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $package */
        $package = $input->getArgument('package');

        /** @var string $target */
        $target = $input->getArgument('target');

        /** @var string[] $selectedFileNames */
        $selectedFileNames = $input->getArgument('files');

        $builder = new PackageFilesProcess($this->requireComposer(), [$package]);

        $filesystem = new Filesystem();
        $targetPath = $builder
            ->paths()
            ->absolute(trim($target, '\/'));
        $filesystem->ensureDirectoryExists($targetPath);
        $prefix = str_replace('/', '-', $package);

        foreach ($this->prepareFiles($builder->files(), $selectedFileNames) as $file) {
            $filename = str_replace('/', '-', $file->filename());
            $filesystem->copy($file->absolutePath(), "$targetPath/$prefix-$filename");
        }

        return 0;
    }

    /**
     * @param PackageFile[] $packageFiles
     * @param string[] $selectedFileNames
     *
     * @return PackageFile[]
     */
    private function prepareFiles(array $packageFiles, array $selectedFileNames): array
    {
        if (empty($selectedFileNames)) {
            return $packageFiles;
        }

        $files = [];

        foreach ($selectedFileNames as $selectedFileName) {
            if (pathinfo($selectedFileName, PATHINFO_EXTENSION) === '') {
                $selectedFileName .= '.php';
            }

            foreach ($packageFiles as $file) {
                if (substr($file->relativePath(), 0 - strlen($selectedFileName)) === $selectedFileName) {
                    $files[] = $file;
                }
            }
        }

        return $files;
    }
}
