<?php

declare(strict_types=1);

namespace Vendor\Engine\Controller;

use Bitrix\Main\Engine\Controller;

/**
 * Базовый контроллер модуля. Глобальные метаданные OpenAPI вынесены в
 * {@see \Vendor\Engine\OpenApi\ApiSpec} (атрибуты), отдельные эндпоинты
 * описываются атрибутами/аннотациями на контроллерах-наследниках.
 *
 * Штатные префильтры Битрикса (Authentication, HttpMethod, Csrf) НЕ отключены:
 * ваши экшены по умолчанию защищены. Публичность задавайте точечно через
 * configureActions() с '-prefilters' — образец в контроллерах демо.
 */
class BaseController extends Controller
{
    protected array $phrases = [
        200 => 'OK',
        401 => 'Unauthorized',
        400 => 'Bad Request',
        403 => 'Forbidden',
        404 => 'Not Found',
        500 => 'Internal Server Error',
    ];
}
