<?php

declare(strict_types=1);

use Bitrix\Main\Config\Configuration;

define('NOT_CHECK_PERMISSIONS', true);
define('NO_AGENT_CHECK', true);

$projectRoot = __DIR__;
$_SERVER['DOCUMENT_ROOT'] = $projectRoot . '/public';

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

$connections = Configuration::getInstance()->get('connections');
$connection = $connections['default'];

return [
    'paths' => [
        'migrations' => '%%PHINX_CONFIG_DIR%%/database/migrations',
        'seeds' => '%%PHINX_CONFIG_DIR%%/database/seeds',
    ],
    'templates' => [
        'file' => '%%PHINX_CONFIG_DIR%%/database/templates/basic.txt',
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment' => 'production',
        'production' => [
            'adapter' => 'mysql',
            'host' => $connection['host'],
            'name' => $connection['database'],
            'user' => $connection['login'],
            'pass' => $connection['password'],
            'port' => $connection['port'] ?? '3306',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ],
    ],
    'version_order' => 'creation',
];
