<?php

declare(strict_types=1);

namespace Vendor\Engine\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'vendor:engine:info', description: 'Показывает информацию о модуле vendor.engine')]
final class ModuleInfoCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $moduleInfo = $this->loadModuleInfo();

        $io->title('vendor.engine');
        $io->table(
            ['Parameter', 'Value'],
            [
                ['Module ID', $moduleInfo['id']],
                ['Version', $moduleInfo['version']],
                ['Version date', $moduleInfo['versionDate']],
                ['Installed', $this->getInstalledStatus()],
            ],
        );

        return Command::SUCCESS;
    }

    /**
     * @return array{id: string, version: string, versionDate: string}
     */
    private function loadModuleInfo(): array
    {
        $moduleFile = dirname(__DIR__, 2) . '/install/index.php';

        if (!is_file($moduleFile)) {
            return [
                'id' => 'unknown',
                'version' => 'unknown',
                'versionDate' => 'unknown',
            ];
        }

        if (!defined('B_PROLOG_INCLUDED')) {
            define('B_PROLOG_INCLUDED', true);
        }

        if (!class_exists(\CModule::class, false)) {
            eval('class CModule {}');
        }

        require_once $moduleFile;

        if (!class_exists(\vendor_engine::class, false)) {
            return [
                'id' => 'unknown',
                'version' => 'unknown',
                'versionDate' => 'unknown',
            ];
        }

        $module = new \vendor_engine();

        return [
            'id' => (string) ($module->MODULE_ID ?? 'unknown'),
            'version' => (string) ($module->MODULE_VERSION ?? 'unknown'),
            'versionDate' => (string) ($module->MODULE_VERSION_DATE ?? 'unknown'),
        ];
    }

    private function getInstalledStatus(): string
    {
        if (!class_exists(\Bitrix\Main\ModuleManager::class)) {
            return 'unknown (Bitrix not bootstrapped)';
        }

        return \Bitrix\Main\ModuleManager::isModuleInstalled('vendor.engine') ? 'installed' : 'not installed';
    }
}
