<?php

declare(strict_types=1);

namespace Loghouse\Logger\Handler;

use Monolog\LogRecord;
use Bitrix\Main\Type\DateTime;
use Loghouse\Logger\Orm\LogTable;
use Monolog\Handler\AbstractProcessingHandler;

final class DatabaseHandler extends AbstractProcessingHandler
{
    protected function write(LogRecord $record): void
    {
        try {
            LogTable::add([
                'CHANNEL'    => $record->channel,
                'LEVEL'      => $record->level->value,
                'LEVEL_NAME' => $record->level->getName(),
                'MESSAGE'    => $record->message,
                'CONTEXT'    => $record->context === [] ? null : json_encode($record->context, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR),
                'EXTRA'      => $record->extra === [] ? null : json_encode($record->extra, JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR),
                'CREATED_AT' => DateTime::createFromPhp(\DateTime::createFromImmutable($record->datetime)),
            ]);
        } catch (\Throwable $e) {
            error_log('loghouse.logger: failed to write log record to DB: ' . $e->getMessage());
        }
    }
}
