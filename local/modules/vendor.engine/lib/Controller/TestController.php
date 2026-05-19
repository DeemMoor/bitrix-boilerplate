<?php

declare(strict_types=1);

namespace Vendor\Engine\Controller;

use Bitrix\Main\Engine\Response\Json;

class TestController extends BaseController
{
    /**
     * @OA\Get(
     *     path="/api/test",
     *     tags={"Test"},
     *     summary="Тестовый эндпоинт для проверки работоспособности API",
     *     @OA\Response(
     *         response=200,
     *         description="Эндпоинт работает",
     *         @OA\JsonContent(
     *             type="object",
     *             required={"status", "message", "timestamp"},
     *             @OA\Property(property="status", type="string", example="ok"),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="vendor.engine test endpoint works"
     *             ),
     *             @OA\Property(
     *                 property="timestamp",
     *                 type="string",
     *                 format="date-time",
     *                 example="2026-05-15T12:00:00+00:00"
     *             )
     *         )
     *     )
     * )
     */
    public function indexAction(): Json
    {
        return new Json([
            'status' => 'ok',
            'message' => 'vendor.engine test endpoint works',
            'timestamp' => date('c'),
        ]);
    }
}
