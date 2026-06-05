<?php

declare(strict_types=1);

namespace Loghouse\Logger\Orm;

use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;
use Bitrix\Main\ORM\Fields\TextField;

final class LogTable extends DataManager
{
    public static function getTableName(): string
    {
        return 'loghouse_logger_log';
    }

    public static function getMap(): array
    {
        return [
            (new IntegerField('ID'))->configurePrimary(true)->configureAutocomplete(true),
            (new StringField('CHANNEL'))->configureRequired(true)->configureSize(64),
            (new IntegerField('LEVEL'))->configureRequired(true),
            (new StringField('LEVEL_NAME'))->configureRequired(true)->configureSize(16),
            (new TextField('MESSAGE'))->configureRequired(true),
            new TextField('CONTEXT'),
            new TextField('EXTRA'),
            (new DatetimeField('CREATED_AT'))->configureRequired(true),
        ];
    }
}