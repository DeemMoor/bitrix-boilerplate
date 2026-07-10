<?php

declare(strict_types=1);

use Bitrix\Main\Routing\RoutingConfigurator;
use Vendor\Engine\Controller\TestController;
use Vendor\Engine\Controller\ApiDocController;
use Vendor\Engine\Controller\ExampleController;

return static function (RoutingConfigurator $configurator) {
    $configurator->get('/api/doc', [ApiDocController::class, 'indexAction']);
    $configurator->get('/api/test', [TestController::class, 'indexAction']);
    $configurator->get('/api/example', [ExampleController::class, 'listAction']);
    // Констрейнт обязателен: без него /api/example/foo уезжает в биндер
    // и отвечает 200 с ошибкой вместо честного 404.
    $configurator->get('/api/example/{id}', [ExampleController::class, 'getAction'])
        ->where('id', '[0-9]+');
};
