<?php

declare(strict_types=1);

namespace Yiisoft\Config\Command;

use Composer\Plugin\Capability\CommandProvider;

/**
 * @internal
 */
final class ConfigCommandProvider implements CommandProvider
{
    public function getCommands(): array
    {
        return [
            new CopyCommand(),
            new RebuildCommand(),
            new InfoCommand(),
        ];
    }
}
