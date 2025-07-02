<?php

declare(strict_types=1);

namespace Yiisoft\Config\Composer;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\Capability\CommandProvider;
use Composer\Plugin\Capable;
use Composer\Plugin\PluginInterface;
use Composer\Plugin\CommandEvent;
use Composer\Plugin\PluginEvents;
use Composer\Script\Event;
use Composer\Script\ScriptEvents;
use Yiisoft\Config\Command\ConfigCommandProvider;

/**
 * ComposerEventHandler responds to composer event. In the package,
 * its job is to prepare a merge plan that is later used by {@see Config}.
 */
final class EventHandler implements PluginInterface, EventSubscriberInterface, Capable
{
    private bool $runOnAutoloadDump = false;

    public static function getSubscribedEvents(): array
    {
        return [
            PluginEvents::COMMAND => 'onCommand',
            ScriptEvents::POST_AUTOLOAD_DUMP => 'onPostAutoloadDump',
            ScriptEvents::POST_INSTALL_CMD => 'onPostUpdateCommandDump',
            ScriptEvents::POST_UPDATE_CMD => 'onPostUpdateCommandDump',
        ];
    }

    /**
     * @codeCoverageIgnore This method runs via eval and does not get into coverage.
     */
    public function onCommand(CommandEvent $event): void
    {
        if ($event->getCommandName() === 'dump-autoload') {
            $this->runOnAutoloadDump = true;
        }
    }

    /**
     * @codeCoverageIgnore This method runs via eval and does not get into coverage.
     */
    public function onPostAutoloadDump(Event $event): void
    {
        if ($this->runOnAutoloadDump) {
            $this->processConfigs($event->getComposer());
        }
    }

    public function onPostUpdateCommandDump(Event $event): void
    {
        $this->processConfigs($event->getComposer());
    }

    public function getCapabilities(): array
    {
        return [CommandProvider::class => ConfigCommandProvider::class];
    }

    public function activate(Composer $composer, IOInterface $io): void
    {
        // do nothing
    }

    public function deactivate(Composer $composer, IOInterface $io): void
    {
        // do nothing
    }

    public function uninstall(Composer $composer, IOInterface $io): void
    {
        // do nothing
    }

    private function processConfigs(Composer $composer): void
    {
        new MergePlanProcess($composer);
    }
}
