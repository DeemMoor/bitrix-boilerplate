<?php

declare(strict_types=1);

namespace Loghouse\Logger\Cli;

use Loghouse\Logger\Logger;
use Loghouse\Logger\Options;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'logger:test',
    description: 'Отправить тестовые сообщения в логгер согласно настройкам модуля.',
)]
final class TestLogCommand extends Command
{
    private const LEVELS = ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'];

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $channel = Options::get('channel', 'app');
        $logger  = Logger::channel($channel);

        $io->section('Канал: ' . $channel);

        foreach (self::LEVELS as $level) {
            $logger->{$level}('test message from CLI [' . $level . ']', [
                'source' => 'logger:test',
                'ts'     => date('c'),
            ]);
        }

        $io->success(count(self::LEVELS) . ' сообщ. отправлено.');

        return Command::SUCCESS;
    }
}
