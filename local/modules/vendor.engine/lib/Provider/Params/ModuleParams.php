<?php

declare(strict_types=1);

namespace Vendor\Engine\Provider\Params;

use Bitrix\Main\Config\Option;

class ModuleParams
{
    private const MODULE_ID = 'vendor.engine';

    /**
     * Returns module option value as string.
     */
    public static function getString(string $name, string $default = ''): string
    {
        return Option::get(self::MODULE_ID, $name, (string)$default);
    }

    /**
     * Returns module option value as integer.
     */
    public static function getInt(string $name, int $default = 0): int
    {
        return (int)Option::get(self::MODULE_ID, $name, (string)$default);
    }

    /**
     * Returns module option value as boolean from Y/N storage format.
     */
    public static function getBool(string $name, bool $default = false): bool
    {
        $defaultValue = $default ? 'Y' : 'N';

        return Option::get(self::MODULE_ID, $name, $defaultValue) === 'Y';
    }

    /**
     * Stores module option value.
     */
    public static function set(string $name, string|int|bool $value): void
    {
        if (is_bool($value)) {
            $value = $value ? 'Y' : 'N';
        }

        Option::set(self::MODULE_ID, $name, (string)$value);
    }
}
