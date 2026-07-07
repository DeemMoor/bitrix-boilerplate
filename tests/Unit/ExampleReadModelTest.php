<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use Vendor\Engine\DTO\ExampleReadModel;
use Vendor\Engine\Presenter\ExamplePresenter;

#[CoversClass(ExampleReadModel::class)]
#[CoversClass(ExamplePresenter::class)]
final class ExampleReadModelTest extends TestCase
{
    public function testFromArrayMapsOrmRow(): void
    {
        $model = ExampleReadModel::fromArray([
            'ID'         => '42',
            'TITLE'      => 'Пример записи',
            'CODE'       => 'example-code',
            'ACTIVE'     => 'Y',
            'CREATED_AT' => '2026-06-04 12:00:00',
            'UPDATED_AT' => '2026-06-04 12:30:00',
        ]);

        self::assertSame(42, $model->id);
        self::assertSame('Пример записи', $model->title);
        self::assertSame('example-code', $model->code);
        self::assertTrue($model->active);
        self::assertSame('2026-06-04', $model->createdAt?->format('Y-m-d'));
        self::assertSame('12:30:00', $model->updatedAt?->format('H:i:s'));
    }

    public function testFromArrayNormalizesEmptyAndMissingValues(): void
    {
        $model = ExampleReadModel::fromArray([
            'TITLE'  => '',
            'CODE'   => '',
            'ACTIVE' => 'N',
        ]);

        self::assertSame(0, $model->id);
        self::assertSame('', $model->title);
        self::assertNull($model->code, 'Пустой CODE должен нормализоваться в null');
        self::assertFalse($model->active, 'ACTIVE != "Y" должен давать false');
        self::assertNull($model->createdAt);
        self::assertNull($model->updatedAt);
    }

    public function testPresenterFormatsItemAsAtomDates(): void
    {
        $model = ExampleReadModel::fromArray([
            'ID'         => 1,
            'TITLE'      => 'T',
            'CODE'       => null,
            'ACTIVE'     => 'Y',
            'CREATED_AT' => '2026-06-04 12:00:00',
            'UPDATED_AT' => null,
        ]);

        $item = new ExamplePresenter()->formatItem($model);

        self::assertSame(
            ['id', 'title', 'code', 'active', 'createdAt', 'updatedAt'],
            array_keys($item),
        );
        self::assertNull($item['code']);
        self::assertSame($model->createdAt?->format(DATE_ATOM), $item['createdAt']);
        self::assertNull($item['updatedAt']);
    }

    public function testPresenterWrapsListUnderItemsKey(): void
    {
        $models = [
            ExampleReadModel::fromArray(['ID' => 1, 'TITLE' => 'A', 'ACTIVE' => 'Y']),
            ExampleReadModel::fromArray(['ID' => 2, 'TITLE' => 'B', 'ACTIVE' => 'Y']),
        ];

        $result = new ExamplePresenter()->formatList($models);

        self::assertArrayHasKey('items', $result);
        self::assertCount(2, $result['items']);
        self::assertSame(1, $result['items'][0]['id']);
        self::assertSame('B', $result['items'][1]['title']);
    }
}
