<?php

if (!function_exists('project_require_composer_autoload')) {
    function project_require_composer_autoload(?string $startPath = null): bool
    {
        $path = realpath($startPath ?: (string) ($_SERVER['DOCUMENT_ROOT'] ?? __DIR__));

        while ($path !== false && $path !== dirname($path)) {
            $autoload = $path . '/vendor/autoload.php';
            if (is_file($autoload)) {
                require_once $autoload;
                return true;
            }

            $path = dirname($path);
        }

        return false;
    }
}

project_require_composer_autoload(__DIR__);
