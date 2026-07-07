<?php

declare(strict_types=1);

namespace Vendor\Engine\Internals\Repository;

use Vendor\Engine\Entity\ExampleTable;
use Vendor\Engine\DTO\ExampleReadModel;
use Vendor\Engine\Internals\Exception\EngineException;

class ExampleRepository implements ExampleRepositoryInterface
{
    public function findById(int $id): ?ExampleReadModel
    {
        $row = ExampleTable::getByPrimary($id)->fetch();

        return $row ? ExampleReadModel::fromArray($row) : null;
    }

    public function findAllActive(): array
    {
        $rows = ExampleTable::getList([
            'filter' => [
                '=ACTIVE' => 'Y',
            ],
            'order' => [
                'ID' => 'ASC',
            ],
        ])->fetchAll();

        return array_map(static fn(array $row): ExampleReadModel => ExampleReadModel::fromArray($row), $rows);
    }

    public function create(array $data): int
    {
        $result = ExampleTable::add($data);
        if (!$result->isSuccess()) {
            throw new EngineException('Не удалось создать Example: ' . implode('; ', $result->getErrorMessages()));
        }

        return (int)$result->getId();
    }

    public function update(int $id, array $data): void
    {
        $result = ExampleTable::update($id, $data);
        if (!$result->isSuccess()) {
            throw new EngineException(sprintf('Не удалось обновить Example #%d: %s', $id, implode('; ', $result->getErrorMessages())));
        }
    }

    public function delete(int $id): void
    {
        $result = ExampleTable::delete($id);
        if (!$result->isSuccess()) {
            throw new EngineException(sprintf('Не удалось удалить Example #%d: %s', $id, implode('; ', $result->getErrorMessages())));
        }
    }
}
