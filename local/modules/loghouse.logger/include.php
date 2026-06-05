<?php

use Bitrix\Main\Loader;

$docRoot = (string)($_SERVER['DOCUMENT_ROOT'] ?? '');
$vendorCandidates = [
    $docRoot . '/../vendor/autoload.php',
    $docRoot . '/vendor/autoload.php',
    $docRoot . '/local/vendor/autoload.php',
];
foreach ($vendorCandidates as $vendorAutoload) {
    if (is_file($vendorAutoload)) {
        require_once $vendorAutoload;
        break;
    }
}

Loader::registerAutoLoadClasses('loghouse.logger', [
    'Loghouse\\Logger\\Logger'                       => 'lib/Logger.php',
    'Loghouse\\Logger\\Options'                      => 'lib/Options.php',
    'Loghouse\\Logger\\Handler\\DatabaseHandler'     => 'lib/Handler/DatabaseHandler.php',
    'Loghouse\\Logger\\Orm\\LogTable'                => 'lib/Orm/LogTable.php',
    'Loghouse\\Logger\\Formatter\\TelegramFormatter' => 'lib/Formatter/TelegramFormatter.php',
    'Loghouse\\Logger\\Cli\\TestLogCommand'          => 'lib/Cli/TestLogCommand.php',
    'Loghouse\\Logger\\Bridge\\BitrixExceptionLog'   => 'lib/Bridge/BitrixExceptionLog.php',
]);
