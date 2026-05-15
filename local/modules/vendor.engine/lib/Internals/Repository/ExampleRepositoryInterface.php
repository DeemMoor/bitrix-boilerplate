<?php

declare(strict_types=1);

namespace Vendor\Engine\Internals\Repository;

interface ExampleRepositoryInterface
{
    public function findById(int $id): ?array;

    public function findAllActive(): array;

    public function create(array $data): int;

    public function update(int $id, array $data): void;

    public function delete(int $id): void;
}
