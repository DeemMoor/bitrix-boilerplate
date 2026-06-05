<?php

declare(strict_types=1);

namespace Vendor\Engine\Internals\Repository;

use Vendor\Engine\DTO\ExampleReadModel;

interface ExampleRepositoryInterface
{
    public function findById(int $id): ?ExampleReadModel;

    /**
     * @return ExampleReadModel[]
     */
    public function findAllActive(): array;

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): int;

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): void;

    public function delete(int $id): void;
}
