<?php

defined('B_PROLOG_INCLUDED') || die();

use Bitrix\Main\Application;
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
        $this->installDB();
    }

    public function DoUninstall(): void
    {
        $request = Application::getInstance()->getContext()->getRequest();
        if ($request->get('savedata') !== 'Y') {
            $this->uninstallDB();
        }
        ModuleManager::unRegisterModule($this->MODULE_ID);
    }

    public function installDB(): void
    {
        $connection = Application::getConnection();
        if (!$connection->isTableExists('vendor_engine_example')) {
            $connection->queryExecute(
                "CREATE TABLE vendor_engine_example (
                    ID INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    TITLE VARCHAR(255) NOT NULL,
                    CODE VARCHAR(64) NULL,
                    ACTIVE CHAR(1) NOT NULL DEFAULT 'Y',
                    CREATED_AT DATETIME NOT NULL,
                    UPDATED_AT DATETIME NULL,
                    PRIMARY KEY (ID),
                    INDEX ix_vendor_engine_example_active (ACTIVE),
                    INDEX ix_vendor_engine_example_code (CODE)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
            );
        }
    }

    public function uninstallDB(): void
    {
        $connection = Application::getConnection();
        if ($connection->isTableExists('vendor_engine_example')) {
            $connection->queryExecute('DROP TABLE vendor_engine_example');
        }
    }
}
