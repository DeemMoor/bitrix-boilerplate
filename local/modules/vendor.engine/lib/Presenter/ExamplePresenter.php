<?php

declare(strict_types=1);

namespace Vendor\Engine\Presenter;

use Vendor\Engine\DTO\ExampleReadModel;

final class ExamplePresenter
{
    /**
     * @param ExampleReadModel[] $items
     * @return array{items: array<int, array<string, mixed>>}
     */
    public function formatList(array $items): array
    {
        return [
            'items' => array_map(fn(ExampleReadModel $item): array => $this->formatItem($item), $items),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function formatItem(ExampleReadModel $item): array
    {
        return [
            'id'        => $item->id,
            'title'     => $item->title,
            'code'      => $item->code,
            'active'    => $item->active,
            'createdAt' => $item->createdAt?->format(DATE_ATOM),
            'updatedAt' => $item->updatedAt?->format(DATE_ATOM),
        ];
    }
}
