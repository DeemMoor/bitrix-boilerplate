<?php

use Bitrix\Main\ModuleManager;
use Bitrix\Main\Routing\RoutingConfigurator;

if (function_exists('project_require_composer_autoload')) {
    project_require_composer_autoload(__DIR__);
}

$getRoutePaths = static function (): array {
    foreach (ModuleManager::getInstalledModules() as $module) {
        $route = $_SERVER['DOCUMENT_ROOT'] . '/local/modules/' . $module['ID'] . '/routes.php';
        if (file_exists($route)) {
            $routes[] = $route;
        }
    }

    return $routes ?? [];
};

return static function (RoutingConfigurator $routingConfigurator) use ($getRoutePaths) {
    foreach ($getRoutePaths() as $route) {
        $callback = include $route;
        if ($callback instanceof Closure) {
            $callback($routingConfigurator);
        }
    }
};
