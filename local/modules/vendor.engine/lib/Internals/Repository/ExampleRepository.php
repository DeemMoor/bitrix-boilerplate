<?php

declare(strict_types=1);

namespace Vendor\Engine\Internals\Repository;

use Vendor\Engine\Entity\ExampleTable;

class ExampleRepository implements ExampleRepositoryInterface
{
    public function findById(int $id): ?array
    {
        return ExampleTable::getByPrimary($id)->fetch() ?: null;
    }

    public function findAllActive(): array
    {
        return ExampleTable::getList([
            'filter' => [
                '=ACTIVE' => 'Y',
            ],
            'order' => [
                'ID' => 'ASC',
            ],
        ])->fetchAll();
    }

    public function create(array $data): int
    {
        $result = ExampleTable::add($data);

        return (int)$result->getId();
    }

    public function update(int $id, array $data): void
    {
        ExampleTable::update($id, $data);
    }

    public function delete(int $id): void
    {
        ExampleTable::delete($id);
    }
}
