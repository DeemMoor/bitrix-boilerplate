<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include.php';

$localPath = getLocalPath('modules/loghouse.logger/admin/log_list.php');
if ($localPath !== false) {
    require_once $_SERVER['DOCUMENT_ROOT'] . $localPath;
}
