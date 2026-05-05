<?php

// Автозагрузка Composer
try {
    $fileAutoload = (str_contains($_SERVER['DOCUMENT_ROOT'], '/public') ? '/..' : '') . '/vendor/autoload.php';
    require $_SERVER['DOCUMENT_ROOT'] . $fileAutoload;
} catch (Exception $exception) {
    die('Не установлен composer');
}
