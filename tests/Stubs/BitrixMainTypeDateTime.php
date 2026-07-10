<?php

declare(strict_types=1);

namespace Bitrix\Main\Type;

// Мини-стаб ядра для юнит-тестов без Битрикса: только то, что использует
// ExampleReadModel. В окружении с ядром класс уже определён — стаб не грузится.
if (!class_exists(DateTime::class, false)) {
    class DateTime
    {
        public function __construct(private readonly int $timestamp)
        {
        }

        public function getTimestamp(): int
        {
            return $this->timestamp;
        }
    }
}
