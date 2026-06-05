<?php

declare(strict_types=1);

namespace Vendor\Engine\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Глобальные метаданные OpenAPI-спеки на PHP-атрибутах.
 */
#[OA\Info(
    version: '1.0.0',
    description: 'API документация проекта',
    title: 'Project API',
)]
#[OA\Server(url: '/', description: 'Текущий хост')]
#[OA\Tag(name: 'Example', description: 'Демонстрационная сущность Example')]
#[OA\Tag(name: 'Test', description: 'Проверка работоспособности API')]
final class ApiSpec
{
}
