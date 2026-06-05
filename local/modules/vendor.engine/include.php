<?php

defined('B_PROLOG_INCLUDED') || die();

use Vendor\Engine\ServiceProvider;
use Bitrix\Main\DI\Exception\RegistrationException;

try {
    ServiceProvider::register();
} catch (RegistrationException $e) {

}
