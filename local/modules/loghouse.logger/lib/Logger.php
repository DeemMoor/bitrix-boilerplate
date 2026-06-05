<?php

declare(strict_types=1);

namespace Loghouse\Logger;

use Psr\Log\LoggerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger as MonologLogger;
use Monolog\Formatter\HtmlFormatter;
use Monolog\Formatter\JsonFormatter;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\TelegramBotHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\FormatterInterface;
use Loghouse\Logger\Handler\DatabaseHandler;
use Loghouse\Logger\Formatter\TelegramFormatter;

/**
 * Фабрика логгеров на базе Monolog.
 *
 * Простое использование (берёт все настройки из админки):
 *   Logger::channel('api')->error('fail');
 *
 * Файл по умолчанию пишется в: <file_dir>/<channel>/app-YYYY-MM-DD.log
 * Папку и дату можно переопределить, передав file_dir / file_path / file_rotation.
 *
 * Inline-переопределение конфигурации:
 *   Logger::channel('integration', [
 *       'targets'   => ['file', 'telegram'],
 *       'formatter' => 'json',
 *       'min_level' => 'warning',
 *       'file_rotation' => 'day',
 *       'telegram_chat_id' => '-100123',
 *   ])->info('hello');
 *
 * Предварительная регистрация канала:
 *   Logger::configure('api', [...]);
 *   Logger::channel('api')->info(...);
 */
final class Logger
{
    private const DEFAULT_FILE_DIR = '/logs';
    private const DEFAULT_FILENAME = 'app';

    /**
     * Кэш собранных логгеров. В per-request (FPM) обнуляется на каждый запрос.
     * В долгоживущих процессах (CLI, воркеры, очереди) состояние переживает задачи —
     * при смене конфигурации канала на лету используйте configure() (сбрасывает кэш
     * по префиксу канала) или reset() (сбрасывает все инстансы).
     *
     * @var array<string, LoggerInterface>
     */
    private static array $instances = [];

    /** @var array<string, array<string, mixed>> */
    private static array $configured = [];

    /**
     * Получить логгер по каналу. $overrides позволяют задать настройки в коде.
     *
     * @param array<string, mixed> $overrides
     */
    public static function channel(string $channel, array $overrides = []): LoggerInterface
    {
        $key = $channel . ':' . md5(serialize($overrides));

        return self::$instances[$key] ??= self::build($channel, self::resolveConfig($channel, $overrides));
    }

    /**
     * Зарегистрировать конфигурацию канала. Будет использована при последующих вызовах channel().
     *
     * @param array<string, mixed> $config
     */
    public static function configure(string $channel, array $config): void
    {
        self::$configured[$channel] = $config;
        foreach (array_keys(self::$instances) as $key) {
            if (str_starts_with($key, $channel . ':')) {
                unset(self::$instances[$key]);
            }
        }
    }

    public static function reset(): void
    {
        self::$instances = [];
    }

    /**
     * @return array<string, mixed>
     */
    private static function resolveConfig(string $channel, array $overrides): array
    {
        $admin = [
            'formatter'          => Options::formatter(),
            'min_level'          => Options::get('min_level', 'debug'),
            'targets'            => array_values(array_filter([
                Options::isYes('target_file', 'Y') ? Options::TARGET_FILE : null,
                Options::isYes('target_db') ? Options::TARGET_DB : null,
                Options::isYes('target_telegram') ? Options::TARGET_TELEGRAM : null,
            ])),
            'file_dir'           => Options::get('file_dir', self::DEFAULT_FILE_DIR),
            'file_min_level'     => Options::get('file_min_level', 'debug'),
            'file_rotation'      => Options::rotation('file_rotation', Options::ROTATION_DAY),
            'file_max_files'     => (int)Options::get('file_max_files', '30'),
            'db_min_level'       => Options::get('db_min_level', 'info'),
            'telegram_token'     => Options::get('telegram_token'),
            'telegram_chat_id'   => Options::get('telegram_chat_id'),
            'telegram_min_level' => Options::get('telegram_min_level', 'error'),
        ];

        return array_replace($admin, self::$configured[$channel] ?? [], $overrides);
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function build(string $channel, array $config): LoggerInterface
    {
        $logger    = new MonologLogger($channel);
        $formatter = self::makeFormatter((string)$config['formatter']);
        $targets   = array_map('strval', (array)$config['targets']);
        $globalMin = $config['min_level'] ?? 'debug';

        if (in_array(Options::TARGET_FILE, $targets, true)) {
            $logger->pushHandler(self::buildFileHandler($channel, $config, $formatter, $globalMin));
        }

        if (in_array(Options::TARGET_DB, $targets, true)) {
            $level   = Options::levelValue($config['db_min_level'] ?? $globalMin);
            $handler = new DatabaseHandler($level);
            $handler->setFormatter($formatter);
            $logger->pushHandler($handler);
        }

        if (in_array(Options::TARGET_TELEGRAM, $targets, true)) {
            $token  = (string)($config['telegram_token'] ?? '');
            $chatId = (string)($config['telegram_chat_id'] ?? '');
            if ($token !== '' && $chatId !== '') {
                $level             = Options::levelValue($config['telegram_min_level'] ?? $globalMin);
                $formatterType     = (string)($config['formatter'] ?? Options::FORMATTER_TEXT);
                [$tgFormatter, $parseMode] = self::makeTelegramFormatter($formatterType);
                $handler = new TelegramBotHandler(
                    $token,
                    $chatId,
                    $level,
                    true,
                    $parseMode,
                    true,
                    false,
                    true,
                );
                $handler->setFormatter($tgFormatter);
                $logger->pushHandler($handler);
            }
        }

        return $logger;
    }

    private static function buildFileHandler(
        string $channel,
        array $config,
        FormatterInterface $formatter,
        mixed $globalMin,
    ): StreamHandler {
        $path = self::resolveFilePath(self::resolveFileTemplate($channel, $config));
        self::ensureDir($path);

        $level    = Options::levelValue($config['file_min_level'] ?? $globalMin);
        $rotation = (string)($config['file_rotation'] ?? Options::ROTATION_DAY);
        $maxFiles = (int)($config['file_max_files'] ?? 0);

        if ($rotation === Options::ROTATION_NONE) {
            $handler = new StreamHandler($path, $level);
        } else {
            $handler = new RotatingFileHandler($path, $maxFiles, $level);
            [$filenameFormat, $dateFormat] = match ($rotation) {
                Options::ROTATION_MONTH => ['{filename}-{date}', 'Y-m'],
                Options::ROTATION_WEEK  => ['{filename}-{date}', 'o-\WW'],
                default                 => ['{filename}-{date}', 'Y-m-d'],
            };
            $handler->setFilenameFormat($filenameFormat, $dateFormat);
        }

        $handler->setFormatter($formatter);

        return $handler;
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function resolveFileTemplate(string $channel, array $config): string
    {
        if (!empty($config['file_path'])) {
            return (string)$config['file_path'];
        }

        $dir      = self::sanitizeDir((string)($config['file_dir'] ?? self::DEFAULT_FILE_DIR));
        $safeName = self::sanitizeSegment($channel);

        $parts = array_filter([$dir, $safeName], static fn(string $p): bool => $p !== '');

        return '/' . implode('/', $parts) . '/' . self::DEFAULT_FILENAME . '.log';
    }

    /**
     * Нормализует пользовательский каталог: разбивает на сегменты, отбрасывает
     * пустые, "." и ".." (защита от path traversal) и санитизирует каждый сегмент.
     */
    private static function sanitizeDir(string $dir): string
    {
        $segments = [];
        foreach (explode('/', $dir) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                continue;
            }
            $segments[] = self::sanitizeSegment($segment);
        }

        return implode('/', $segments);
    }

    private static function sanitizeSegment(string $value): string
    {
        $value = (string)preg_replace('~[^a-zA-Z0-9._-]+~', '_', $value);

        return trim($value, '.') ?: 'default';
    }

    private static function makeFormatter(string $type): FormatterInterface
    {
        return match ($type) {
            Options::FORMATTER_HTML => new HtmlFormatter(),
            Options::FORMATTER_JSON => (new JsonFormatter())->setJsonPrettyPrint(false),
            default                 => new LineFormatter(null, null, true, true),
        };
    }

    /**
     * @return array{0: FormatterInterface, 1: ?string}
     */
    private static function makeTelegramFormatter(string $type): array
    {
        return match ($type) {
            Options::FORMATTER_HTML => [new TelegramFormatter(), 'HTML'],
            Options::FORMATTER_JSON => [(new JsonFormatter())->setJsonPrettyPrint(false), null],
            default                 => [
                new LineFormatter(
                    "[%level_name%] %channel% — %message%\n%context% %extra%",
                    'Y-m-d H:i:s',
                    true,
                    true,
                ),
                null,
            ],
        };
    }

    /**
     * Правила:
     *  - /upload, /bitrix, /local → относительно DOCUMENT_ROOT (внутри public)
     *  - всё остальное начинающееся на / → относительно корня проекта (на уровень выше DOCUMENT_ROOT)
     *  - относительный путь — тоже от корня проекта
     */
    private static function resolveFilePath(string $path): string
    {
        if ($path !== '' && $path[0] !== '/') {
            $path = '/' . $path;
        }

        $docRoot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/');

        if ($docRoot !== ''
            && (str_starts_with($path, '/upload') || str_starts_with($path, '/bitrix') || str_starts_with($path, '/local'))
        ) {
            return $docRoot . $path;
        }

        $projectRoot = $docRoot !== '' ? dirname($docRoot) : '';

        return $projectRoot . $path;
    }

    private static function ensureDir(string $file): void
    {
        $dir = dirname($file);
        if (is_dir($dir)) {
            return;
        }

        // Проверяем результат mkdir и перепроверяем is_dir на случай гонки
        // параллельных процессов. Явная ошибка лучше невнятного сбоя в StreamHandler.
        if (!@mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException(sprintf('loghouse.logger: unable to create log directory "%s".', $dir));
        }
    }
}
