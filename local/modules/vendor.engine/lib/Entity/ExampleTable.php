<?php

declare(strict_types=1);

namespace Vendor\Engine\Entity;

use Bitrix\Main\Type\DateTime;
use Bitrix\Main\ORM\Fields\BooleanField;
use Bitrix\Main\ORM\Fields\DatetimeField;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\StringField;

class ExampleTable extends \Bitrix\Main\ORM\Data\DataManager
{
    public static function getTableName(): string
    {
        return 'vendor_engine_example';
    }

    public static function getMap(): array
    {
        return [
            new IntegerField('ID')->configurePrimary()->configureAutocomplete(),
            new StringField('TITLE')->configureRequired()->configureSize(255),
            new StringField('CODE')->configureSize(64),
            new BooleanField('ACTIVE')->configureValues('N', 'Y')->configureDefaultValue('Y'),
            new DatetimeField('CREATED_AT')->configureDefaultValue(static fn(): DateTime => new DateTime()),
            new DatetimeField('UPDATED_AT')->configureNullable(),
        ];
    }
}
