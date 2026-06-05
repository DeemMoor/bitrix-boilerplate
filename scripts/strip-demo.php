<?php

/**
 * Удаляет демонстрационный срез (Example + Ping + Test) из модуля vendor.engine,
 * оставляя чистый рабочий каркас слоёной архитектуры.
 *
 * Запуск из корня проекта:
 *   php scripts/strip-demo.php
 *
 * ВНИМАНИЕ: скрипт переписывает структурные файлы (routes.php, ServiceProvider.php,
 * install/index.php, OpenApi/ApiSpec.php) на чистые версии. Запускайте на свежей
 * болванке ДО кастомизации модуля.
 */

declare(strict_types=1);

$root   = dirname(__DIR__);
$module = $root . '/local/modules/vendor.engine';

if (!is_dir($module)) {
    fwrite(STDERR, "Модуль не найден: $module\n");
    exit(1);
}

$removed = [];
$written = [];
$kept    = [];

/** Удаляет файл, если существует. */
$deleteFile = static function (string $path) use (&$removed): void {
    if (is_file($path)) {
        unlink($path);
        $removed[] = $path;
    }
};

/** Перезаписывает файл содержимым. */
$writeFile = static function (string $path, string $content) use (&$written): void {
    file_put_contents($path, $content);
    $written[] = $path;
};

/** Кладёт .gitkeep, если каталог существует и пуст. */
$gitkeepIfEmpty = static function (string $dir) use (&$kept): void {
    if (!is_dir($dir)) {
        return;
    }
    $entries = array_diff(scandir($dir) ?: [], ['.', '..']);
    if ($entries === []) {
        file_put_contents($dir . '/.gitkeep', '');
        $kept[] = $dir . '/.gitkeep';
    }
};

// 1. Удаляем чисто демонстрационные файлы.
foreach ([
    '/lib/Controller/ExampleController.php',
    '/lib/Controller/TestController.php',
    '/lib/UseCase/ListExamplesUseCase.php',
    '/lib/UseCase/GetExampleUseCase.php',
    '/lib/DTO/ExampleReadModel.php',
    '/lib/Presenter/ExamplePresenter.php',
    '/lib/Internals/Repository/ExampleRepository.php',
    '/lib/Internals/Repository/ExampleRepositoryInterface.php',
    '/lib/Entity/ExampleTable.php',
    '/lib/Command/PingCommand.php',
] as $rel) {
    $deleteFile($module . $rel);
}

// 2. Переписываем структурные файлы на чистые версии.

$writeFile($module . '/routes.php', <<<'PHP'
<?php

use Bitrix\Main\Routing\RoutingConfigurator;
use Vendor\Engine\Controller\ApiDocController;

return static function (RoutingConfigurator $configurator) {
    $configurator->get('/api/doc', [ApiDocController::class, 'indexAction']);
};

PHP);

$writeFile($module . '/lib/ServiceProvider.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace Vendor\Engine;

use Bitrix\Main\DI\Exception\RegistrationException;

/**
 * Регистрация зависимостей модуля в ServiceLocator и автовайринг
 * UseCase'ов в экшены контроллеров через AutoWire\Binder.
 *
 * Демонстрационные регистрации удалены — добавляйте свои в register().
 */
final class ServiceProvider
{
    /**
     * @throws RegistrationException
     */
    public static function register(): void
    {
        static $registered = false;
        if ($registered) {
            return;
        }

        // TODO: зарегистрировать зависимости модуля
        // (ServiceLocator::addInstanceLazy / Binder::registerGlobalAutoWiredParameter).

        $registered = true;
    }
}

PHP);

$writeFile($module . '/lib/OpenApi/ApiSpec.php', <<<'PHP'
<?php

declare(strict_types=1);

namespace Vendor\Engine\OpenApi;

use OpenApi\Attributes as OA;

/**
 * Глобальные метаданные OpenAPI-спеки на PHP-атрибутах.
 */
#[OA\Info(
    version: '1.0.0',
    description: 'API документация проекта',
    title: 'Project API',
)]
#[OA\Server(url: '/', description: 'Текущий хост')]
final class ApiSpec
{
}

PHP);

$writeFile($module . '/install/index.php', <<<'PHP'
<?php

defined('B_PROLOG_INCLUDED') || die();

use Bitrix\Main\ModuleManager;

class vendor_engine extends CModule
{
    public $MODULE_ID = 'vendor.engine';

    public function __construct()
    {
        $arModuleVersion = [];
        include __DIR__ . '/version.php';

        $this->MODULE_VERSION      = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME         = 'Engine';
        $this->MODULE_DESCRIPTION  = 'Модуль содержит общую системную логику';
        $this->MODULE_GROUP_RIGHTS = 'N';
        $this->PARTNER_NAME        = 'vendor';
        $this->PARTNER_URI         = '';
    }

    public function DoInstall(): void
    {
        ModuleManager::registerModule($this->MODULE_ID);
    }

    public function DoUninstall(): void
    {
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }
}

PHP);

// 3. Чистим корневой console от регистрации PingCommand.
$consolePath = $root . '/console';
if (is_file($consolePath)) {
    $lines = file($consolePath, FILE_IGNORE_NEW_LINES);
    $filtered = [];
    foreach ($lines as $line) {
        if (str_contains($line, 'PingCommand')) {
            continue; // import + $app->addCommand(new PingCommand());
        }
        if (trim($line) === '// php console ping') {
            continue;
        }
        $filtered[] = $line;
    }
    // Схлопываем тройные пустые строки в двойные.
    $text = preg_replace("/\n{3,}/", "\n\n", implode("\n", $filtered)) . "\n";
    if ($text !== implode("\n", $lines) . "\n") {
        file_put_contents($consolePath, $text);
        $written[] = $consolePath;
    }
}

// 4. Урезаем README модуля — секцию про образец (от "## ⚠️ Образец" до конца).
$readmePath = $module . '/README.md';
if (is_file($readmePath)) {
    $readme = file_get_contents($readmePath);
    $pos = strpos($readme, '## ⚠️ Образец');
    if ($pos !== false) {
        file_put_contents($readmePath, rtrim(substr($readme, 0, $pos)) . "\n");
        $written[] = $readmePath;
    }
}

// 5. Оставляем слои-папки как каркас (пустые → .gitkeep).
foreach ([
    '/lib/UseCase',
    '/lib/DTO',
    '/lib/Presenter',
    '/lib/Entity',
    '/lib/Internals/Repository',
] as $rel) {
    $gitkeepIfEmpty($module . $rel);
}

// Отчёт.
echo "Удалено демо-файлов: " . count($removed) . "\n";
foreach ($removed as $f) {
    echo "  - " . substr($f, strlen($root) + 1) . "\n";
}
echo "Переписано/почищено: " . count($written) . "\n";
foreach ($written as $f) {
    echo "  ~ " . substr($f, strlen($root) + 1) . "\n";
}
if ($kept !== []) {
    echo "Каркас сохранён (.gitkeep): " . count($kept) . "\n";
    foreach ($kept as $f) {
        echo "  + " . substr($f, strlen($root) + 1) . "\n";
    }
}

echo "\nГотово. Не забудьте:\n";
echo "  • если модуль был установлен — удалить таблицу `vendor_engine_example` из БД\n";
echo "    (или переустановить модуль);\n";
echo "  • пересобрать документацию: php console api:doc:generate\n";
