<?php

/**
 * This file is part of RoadRunner package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\RoadRunner\Console\Command;

use Symfony\Component\Console\Command\Command;
use Spiral\RoadRunner\Console\Environment\Architecture;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\StyleInterface;

class ArchitectureOption extends Option
{
    public function __construct(Command $command, string $name = 'arch', string $short = 'a')
    {
        parent::__construct($command, $name, $short);
    }

    public function get(InputInterface $input, StyleInterface $io): string
    {
        $architecture = parent::get($input, $io);

        if (! Architecture::isValid($architecture)) {
            $message = 'Possibly invalid architecture (--%s=%s) option (available: %s)';
            $io->warning(\sprintf($message, $this->name, $architecture, $this->choices()));
        }

        return $architecture;
    }

    protected function getDescription(): string
    {
        return 'Required processor architecture (one of: ' . $this->choices() . ')';
    }

    protected function default(): string
    {
        return Architecture::createFromGlobals();
    }

    private function choices(): string
    {
        return \implode(', ', Architecture::all());
    }
}
