<?php

namespace Vendor\Engine\Command;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class PingCommand
 *
 * @package Vendor\Engine\Command
 */
class PingCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('ping')
            ->setDescription('Checking the functionality of the console command');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('PING test');

        try {
            $io->writeln('Try pong...');
            $io->newLine();

            $io->progressStart(10);

            $page = 1;
            do {
                usleep(250000);
                $page++;
                $io->progressAdvance();
            } while ($page < 10);

            $io->progressFinish();
        } catch (Exception $e) {
            $io->error($e->getMessage());

            return 1;
        }

        $io->writeln('PONG!');
        $io->newLine();

        return 0;
    }
}
