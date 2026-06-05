<?php

declare(strict_types=1);

namespace Loghouse\Logger\Formatter;

use Monolog\Formatter\NormalizerFormatter;
use Monolog\LogRecord;

/**
 * HTML-форматтер для TelegramBotHandler (parseMode=HTML).
 *
 * Вывод:
 *   🆘 <b>ERROR</b> · <code>channel</code> · 2026-04-17 15:35:04
 *
 *   message
 *
 *   <b>Context</b>
 *   <pre>{ ... json ... }</pre>
 */
final class TelegramFormatter extends NormalizerFormatter
{
    private const EMOJI = [
        'DEBUG'     => '🐛',
        'INFO'      => 'ℹ️',
        'NOTICE'    => '📘',
        'WARNING'   => '⚠️',
        'ERROR'     => '🆘',
        'CRITICAL'  => '🔥',
        'ALERT'     => '🚨',
        'EMERGENCY' => '💀',
    ];

    private const TELEGRAM_MAX_LENGTH = 4096;

    public function __construct()
    {
        parent::__construct('Y-m-d H:i:s');
    }

    public function format(LogRecord $record): string
    {
        $levelName = strtoupper($record->level->getName());
        $emoji     = self::EMOJI[$levelName] ?? '•';
        $datetime  = $record->datetime->format('Y-m-d H:i:s');

        $lines = [
            $emoji . ' <b>' . $this->esc($levelName) . '</b> · <code>' . $this->esc($record->channel) . '</code> · ' . $this->esc($datetime),
            '',
            $this->esc($record->message),
        ];

        if (!empty($record->context)) {
            $lines[] = '';
            $lines[] = '<b>Context</b>';
            $lines[] = '<pre>' . $this->esc($this->toJson($this->normalize($record->context))) . '</pre>';
        }

        if (!empty($record->extra)) {
            $lines[] = '';
            $lines[] = '<b>Extra</b>';
            $lines[] = '<pre>' . $this->esc($this->toJson($this->normalize($record->extra))) . '</pre>';
        }

        $message = implode("\n", $lines);

        if (mb_strlen($message) > self::TELEGRAM_MAX_LENGTH) {
            $message = mb_substr($message, 0, self::TELEGRAM_MAX_LENGTH - 5) . '…';
        }

        return $message;
    }

    private function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
