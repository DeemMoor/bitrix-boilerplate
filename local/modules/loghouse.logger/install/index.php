<?php

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;

if (class_exists('loghouse_logger')) {
    return;
}

class loghouse_logger extends CModule
{
    public $MODULE_ID = 'loghouse.logger';

    public function __construct()
    {
        $arModuleVersion = [];
        include __DIR__ . '/version.php';

        $this->MODULE_VERSION      = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->MODULE_NAME         = 'Loghouse Logger';
        $this->MODULE_DESCRIPTION  = 'Модуль логирования на базе Monolog';
        $this->MODULE_GROUP_RIGHTS = 'N';
        $this->PARTNER_NAME        = 'Loghouse';
        $this->PARTNER_URI         = 'https://github.com/DeemMoor/loghouse.git';
    }

    public function doInstall(): void
    {
        ModuleManager::registerModule($this->MODULE_ID);
        Loader::includeModule($this->MODULE_ID);
        $this->installDB();
        $this->installFiles();
        $this->setDefaultOptions();
    }

    public function doUninstall(): void
    {
        $request = Application::getInstance()->getContext()->getRequest();
        if ($request->get('savedata') !== 'Y') {
            $this->uninstallDB();
        }
        $this->unInstallFiles();
        Option::delete($this->MODULE_ID);
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    public function installFiles(): void
    {
        CopyDirFiles(
            __DIR__ . '/admin',
            $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin',
            true,
            true
        );
    }

    public function unInstallFiles(): void
    {
        $adminDir = $_SERVER['DOCUMENT_ROOT'] . '/bitrix/admin';
        foreach ((array)glob(__DIR__ . '/admin/*.php') as $file) {
            $target = $adminDir . '/' . basename((string)$file);
            if (is_file($target)) {
                @unlink($target);
            }
        }
    }

    public function installDB(): void
    {
        $connection = Application::getConnection();
        if (!$connection->isTableExists('loghouse_logger_log')) {
            $connection->queryExecute(
                "CREATE TABLE loghouse_logger_log (
                    ID INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    CHANNEL VARCHAR(64) NOT NULL,
                    LEVEL SMALLINT UNSIGNED NOT NULL,
                    LEVEL_NAME VARCHAR(16) NOT NULL,
                    MESSAGE TEXT NOT NULL,
                    CONTEXT MEDIUMTEXT NULL,
                    EXTRA MEDIUMTEXT NULL,
                    CREATED_AT DATETIME NOT NULL,
                    PRIMARY KEY (ID),
                    INDEX ix_loghouse_logger_log_channel (CHANNEL),
                    INDEX ix_loghouse_logger_log_level (LEVEL),
                    INDEX ix_loghouse_logger_log_created (CREATED_AT)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );
        }
    }

    public function uninstallDB(): void
    {
        $connection = Application::getConnection();
        if ($connection->isTableExists('loghouse_logger_log')) {
            $connection->queryExecute('DROP TABLE loghouse_logger_log');
        }
    }

    private function setDefaultOptions(): void
    {
        $defaults = include __DIR__ . '/../default_option.php';
        foreach ($defaults as $name => $value) {
            if (Option::get($this->MODULE_ID, $name, null) === null) {
                Option::set($this->MODULE_ID, $name, is_array($value) ? serialize($value) : (string)$value);
            }
        }
    }
}
