<?php

declare(strict_types=1);

namespace Vendor\Engine\Controller;

use Throwable;
use Loghouse\Logger\Logger;
use OpenApi\Attributes as OA;
use Bitrix\Main\Engine\Response\Json;
use Vendor\Engine\UseCase\GetExampleUseCase;
use Vendor\Engine\Presenter\ExamplePresenter;
use Vendor\Engine\UseCase\ListExamplesUseCase;

/**
 * Эндпоинты сущности Example. Описаны PHP-атрибутами swagger-php (#[OA\...]) —
 * предпочтительный способ начиная с swagger-php 5/6. Аннотационный стиль (@OA\...)
 * см. в {@see TestController}.
 */
class ExampleController extends BaseController
{
    #[OA\Get(
        path: '/api/example',
        summary: 'Список активных записей Example',
        tags: ['Example'],
    )]
    #[OA\Response(
        response: 200,
        description: 'Список записей',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'items',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/ExampleItem'),
                ),
            ],
            type: 'object',
        ),
    )]
    #[OA\Response(response: 500, description: 'Внутренняя ошибка сервера')]
    public function listAction(ListExamplesUseCase $useCase): Json
    {
        try {
            $items = $useCase->execute();
        } catch (Throwable $e) {
            Logger::channel('engine')->error('Не удалось получить список Example', [
                'exception' => $e->getMessage(),
            ]);

            return $this->internalError();
        }

        return new Json(new ExamplePresenter()->formatList($items));
    }

    #[OA\Get(
        path: '/api/example/{id}',
        summary: 'Получение записи Example по ID',
        tags: ['Example'],
        parameters: [
            new OA\PathParameter(
                name: 'id',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 1),
            ),
        ],
    )]
    #[OA\Response(
        response: 200,
        description: 'Запись Example',
        content: new OA\JsonContent(ref: '#/components/schemas/ExampleItem'),
    )]
    #[OA\Response(response: 404, description: 'Запись не найдена')]
    #[OA\Response(response: 500, description: 'Внутренняя ошибка сервера')]
    public function getAction(GetExampleUseCase $useCase, int $id): Json
    {
        try {
            $item = $useCase->execute($id);
        } catch (Throwable $e) {
            Logger::channel('engine')->error('Не удалось получить запись Example', [
                'id'        => $id,
                'exception' => $e->getMessage(),
            ]);

            return $this->internalError();
        }

        if ($item === null) {
            return $this->notFound();
        }

        return new Json(new ExamplePresenter()->formatItem($item));
    }

    private function notFound(): Json
    {
        $response = new Json(['error' => $this->phrases[404]]);
        $response->setStatus('404 Not Found');

        return $response;
    }

    private function internalError(): Json
    {
        $response = new Json(['error' => $this->phrases[500]]);
        $response->setStatus('500 Internal Server Error');

        return $response;
    }
}
