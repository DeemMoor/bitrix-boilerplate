<?php

declare(strict_types=1);

namespace Loghouse\Logger;

use Monolog\Level;
use Bitrix\Main\Config\Option;

final class Options
{
    public const MODULE_ID = 'loghouse.logger';

    public const FORMATTER_TEXT = 'text';
    public const FORMATTER_HTML = 'html';
    public const FORMATTER_JSON = 'json';

    public const ROTATION_NONE  = 'none';
    public const ROTATION_DAY   = 'day';
    public const ROTATION_WEEK  = 'week';
    public const ROTATION_MONTH = 'month';

    public const TARGET_FILE     = 'file';
    public const TARGET_DB       = 'db';
    public const TARGET_TELEGRAM = 'telegram';

    public static function formatters(): array
    {
        return [
            self::FORMATTER_TEXT => 'LOGHOUSE_LOGGER_FMT_TEXT',
            self::FORMATTER_HTML => 'LOGHOUSE_LOGGER_FMT_HTML',
            self::FORMATTER_JSON => 'LOGHOUSE_LOGGER_FMT_JSON',
        ];
    }

    public static function rotations(): array
    {
        return [
            self::ROTATION_NONE  => 'LOGHOUSE_LOGGER_ROT_NONE',
            self::ROTATION_DAY   => 'LOGHOUSE_LOGGER_ROT_DAY',
            self::ROTATION_WEEK  => 'LOGHOUSE_LOGGER_ROT_WEEK',
            self::ROTATION_MONTH => 'LOGHOUSE_LOGGER_ROT_MONTH',
        ];
    }

    public static function levels(): array
    {
        return [
            'debug'     => Level::Debug->value,
            'info'      => Level::Info->value,
            'notice'    => Level::Notice->value,
            'warning'   => Level::Warning->value,
            'error'     => Level::Error->value,
            'critical'  => Level::Critical->value,
            'alert'     => Level::Alert->value,
            'emergency' => Level::Emergency->value,
        ];
    }

    public static function get(string $name, string $default = ''): string
    {
        return (string)Option::get(self::MODULE_ID, $name, $default);
    }

    public static function isYes(string $name, string $default = 'N'): bool
    {
        return self::get($name, $default) === 'Y';
    }

    public static function formatter(): string
    {
        $value = self::get('formatter', self::FORMATTER_TEXT);

        return array_key_exists($value, self::formatters()) ? $value : self::FORMATTER_TEXT;
    }

    public static function rotation(string $name = 'file_rotation', string $default = self::ROTATION_NONE): string
    {
        $value = self::get($name, $default);

        return array_key_exists($value, self::rotations()) ? $value : $default;
    }

    public static function levelValue(Level|string|int|null $value, ?int $default = null): int
    {
        $default ??= Level::Debug->value;

        if ($value === null) {
            return $default;
        }
        if ($value instanceof Level) {
            return $value->value;
        }
        if (is_int($value)) {
            return $value;
        }

        $map = self::levels();

        return $map[strtolower($value)] ?? $default;
    }
}
