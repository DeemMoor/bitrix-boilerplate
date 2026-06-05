<?php

declare(strict_types=1);

namespace Vendor\Engine\DTO;

use DateTimeImmutable;
use OpenApi\Attributes as OA;
use Bitrix\Main\Type\DateTime;
use DateMalformedStringException;

// Read-model сущности Example. Возвращается репозиторием наружу вместо «голого»
// массива ORM. Схема OpenAPI описана PHP-атрибутами прямо на модели.
// Комментарий намеренно не PHPDoc, чтобы swagger не утащил его в description схемы.
#[OA\Schema(
    schema: 'ExampleItem',
    description: 'Запись демонстрационной сущности Example',
    required: ['id', 'title', 'active'],
    type: 'object',
)]
readonly class ExampleReadModel
{
    public function __construct(
        #[OA\Property(type: 'integer', example: 1)]
        public int $id,
        #[OA\Property(type: 'string', example: 'Пример записи')]
        public string $title,
        #[OA\Property(type: 'string', example: 'example-code', nullable: true)]
        public ?string $code,
        #[OA\Property(type: 'boolean', example: true)]
        public bool $active,
        #[OA\Property(type: 'string', format: 'date-time', example: '2026-06-04T12:00:00+00:00', nullable: true)]
        public ?DateTimeImmutable $createdAt,
        #[OA\Property(type: 'string', format: 'date-time', example: '2026-06-04T12:30:00+00:00', nullable: true)]
        public ?DateTimeImmutable $updatedAt,
    ) {
    }

    /**
     * @param array<string, mixed> $row Строка выборки ORM (ExampleTable).
     */
    public static function fromArray(array $row): self
    {
        return new self(
            id:        (int)($row['ID'] ?? 0),
            title:     (string)($row['TITLE'] ?? ''),
            code:      isset($row['CODE']) && $row['CODE'] !== '' ? (string)$row['CODE'] : null,
            active:    ($row['ACTIVE'] ?? 'N') === 'Y',
            createdAt: self::toDateTime($row['CREATED_AT'] ?? null),
            updatedAt: self::toDateTime($row['UPDATED_AT'] ?? null),
        );
    }

    /**
     * @throws DateMalformedStringException
     */
    private static function toDateTime(mixed $value): ?DateTimeImmutable
    {
        if ($value instanceof DateTime) {
            return DateTimeImmutable::createFromInterface($value->getValue());
        }

        if (is_string($value) && $value !== '') {
            return new DateTimeImmutable($value);
        }

        return null;
    }
}
