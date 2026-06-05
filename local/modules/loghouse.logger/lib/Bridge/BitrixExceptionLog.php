<?php

declare(strict_types=1);

namespace Loghouse\Logger\Bridge;

use Throwable;
use Loghouse\Logger\Logger;
use Bitrix\Main\Diag\ExceptionHandlerLog;

/**
 * Мост штатной обработки ошибок Bitrix в loghouse.logger.
 *
 * Подключается в .settings.php:
 *   'exception_handling' => ['value' => ['log' => [
 *       'class_name' => '\\Loghouse\\Logger\\Bridge\\BitrixExceptionLog',
 *       'settings'   => ['channel' => 'bitrix'],
 *   ]]]
 *
 * Все необработанные исключения и ошибки ядра уходят в канал loghouse
 * (по умолчанию "bitrix"), а оттуда — в файл/БД/Telegram согласно настройкам модуля.
 */
final class BitrixExceptionLog extends ExceptionHandlerLog
{
    private string $channel = 'bitrix';

    /**
     * @param array<string, mixed> $options
     */
    public function initialize(array $options): void
    {
        if (!empty($options['channel']) && is_string($options['channel'])) {
            $this->channel = $options['channel'];
        }
    }

    /**
     * @param Throwable $exception
     * @param int       $logType Одна из констант ExceptionHandlerLog (UNCAUGHT_EXCEPTION и т.д.)
     */
    public function write($exception, $logType): void
    {
        // Логгер может быть недоступен на самой ранней стадии запуска —
        // не даём обработчику ошибок упасть из-за этого.
        if (!class_exists(Logger::class)) {
            return;
        }

        try {
            // logTypeToLevel()/logTypeToString() — штатные хелперы ядра: маппят
            // тип ошибки Bitrix в PSR-3 уровень и читаемую строку.
            Logger::channel($this->channel)->log(
                static::logTypeToLevel($logType),
                $exception->getMessage(),
                [
                    'type'  => static::logTypeToString($logType),
                    'class' => $exception::class,
                    'file'  => $exception->getFile(),
                    'line'  => $exception->getLine(),
                ],
            );
        } catch (Throwable) {
            // Падение логгера не должно прерывать штатный обработчик ошибок ядра.
        }
    }
}
