<?php

use Bitrix\Main\Routing\RoutingConfigurator;
use Vendor\Engine\Controller\TestController;
use Vendor\Engine\Controller\ApiDocController;
use Vendor\Engine\Controller\ExampleController;

return static function (RoutingConfigurator $configurator) {
    $configurator->get('/api/doc', [ApiDocController::class, 'indexAction']);
    $configurator->get('/api/test', [TestController::class, 'indexAction']);
    $configurator->get('/api/example', [ExampleController::class, 'listAction']);
    $configurator->get('/api/example/{id}', [ExampleController::class, 'getAction']);
};
