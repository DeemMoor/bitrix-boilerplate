<?php

declare(strict_types=1);

namespace Vendor\Engine\Command;

use Throwable;
use OpenApi\Context;
use OpenApi\Analysis;
use Psr\Log\LogLevel;
use OpenApi\Generator;
use OpenApi\SourceFinder;
use Psr\Log\AbstractLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Генерация OpenAPI-спеки из аннотаций/атрибутов контроллеров.
 */
final class GenerateApiDocCommand extends Command
{
    private const DEFAULT_OUTPUT = '/local/bitrixoa.yaml';
    private const SCAN_DIR       = '/local/modules';

    protected function configure(): void
    {
        $this
            ->setName('api:doc:generate')
            ->setDescription('Сгенерировать local/bitrixoa.yaml из аннотаций и #[OA\\...] атрибутов')
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Путь к выходному файлу относительно корня проекта', self::DEFAULT_OUTPUT)
            ->addOption('exclude', 'e', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Исключить путь(и) из сканирования', []);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $projectRoot = dirname((string)$_SERVER['DOCUMENT_ROOT']);
        $scanDir     = $projectRoot . self::SCAN_DIR;
        $outputPath  = $projectRoot . '/' . ltrim((string)$input->getOption('output'), '/');

        if (!is_dir($scanDir)) {
            $io->error(sprintf('Каталог модулей не найден: %s', $scanDir));

            return Command::FAILURE;
        }

        $logger = $this->createLogger($io);

        $generator = new class ($logger) extends Generator {
            protected function scanSources(iterable $sources, Analysis $analysis, Context $rootContext): void
            {
                foreach ($sources as $source) {
                    try {
                        parent::scanSources([$source], $analysis, $rootContext);
                    } catch (Throwable $e) {
                        $name = $source instanceof \SplFileInfo ? $source->getPathname() : (string)$source;
                        $rootContext->logger->warning(sprintf('Skipping %s: %s', $name, $e->getMessage()));
                    }
                }
            }
        };

        try {
            $openapi = $generator->generate(
                new SourceFinder($scanDir, (array)$input->getOption('exclude'))
            );
            $openapi->saveAs($outputPath);
        } catch (Throwable $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $io->success(sprintf('OpenAPI-спека записана: %s', $outputPath));

        return Command::SUCCESS;
    }

    private function createLogger(SymfonyStyle $io): AbstractLogger
    {
        return new class ($io) extends AbstractLogger {
            public function __construct(private SymfonyStyle $io)
            {
            }

            public function log($level, $message, array $context = []): void
            {
                if (in_array($level, [LogLevel::DEBUG, LogLevel::INFO], true)) {
                    return;
                }

                $this->io->warning((string)$message);
            }
        };
    }
}
