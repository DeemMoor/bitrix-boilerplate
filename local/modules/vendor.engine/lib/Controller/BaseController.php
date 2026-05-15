<?php

namespace Vendor\Engine\Controller;

use Bitrix\Main\Engine\Controller;

/**
 * @OA\Info(
 *     description="API документация проекта",
 *     version="1.0.0",
 *     title="Project API",
 * )
 * @OA\PathItem(path="/")
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

    public function getDefaultPreFilters(): array
    {
        return [];
    }
}
