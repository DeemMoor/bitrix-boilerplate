<?php

use Bitrix\Main\Routing\RoutingConfigurator;
use Vendor\Engine\Controller\ApiDocController;

return static function (RoutingConfigurator $configurator) {
    $configurator->get('/api/doc', [ApiDocController::class, 'indexAction']);
};
