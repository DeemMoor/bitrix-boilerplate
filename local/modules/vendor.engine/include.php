<?php

declare(strict_types=1);

defined('B_PROLOG_INCLUDED') || die();

use Vendor\Engine\ServiceProvider;
use Bitrix\Main\DI\Exception\RegistrationException;

try {
    ServiceProvider::register();
} catch (RegistrationException $e) {
    // Не валим страницу, но и не молчим: без регистрации DI не работают
    // автовайринг use case'ов и роуты модуля.
    trigger_error('vendor.engine ServiceProvider: ' . $e->getMessage(), E_USER_WARNING);
}
