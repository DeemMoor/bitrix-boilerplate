<?php

defined('B_PROLOG_INCLUDED') || die();

class vendor_engine extends CModule
{
    public function __construct()
    {
        $this->MODULE_ID = 'vendor.engine';
        $this->MODULE_VERSION = '0.0.1';
        $this->MODULE_VERSION_DATE = '2026-05-15';
        $this->MODULE_NAME = 'vendor.engine';
        $this->MODULE_DESCRIPTION = 'Project module vendor.engine';
        $this->PARTNER_NAME = 'vendor';
        $this->PARTNER_URI = '';
    }

    public function DoInstall(): void
    {
        RegisterModule($this->MODULE_ID);
    }

    public function DoUninstall(): void
    {
        UnRegisterModule($this->MODULE_ID);
    }
}
