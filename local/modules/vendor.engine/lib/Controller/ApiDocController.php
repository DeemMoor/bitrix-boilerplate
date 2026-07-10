<?php

declare(strict_types=1);

namespace Vendor\Engine\Controller;

use BitrixOA\UiPage;
use RuntimeException;
use Bitrix\Main\HttpResponse;
use Bitrix\Main\ArgumentTypeException;
use Bitrix\Main\Engine\ActionFilter\Csrf;
use Bitrix\Main\Engine\ActionFilter\Authentication;

class ApiDocController extends BaseController
{
    private const string YAML_PATH = 'local/bitrixoa.yaml';

    public function configureActions(): array
    {
        return [
            'index' => ['-prefilters' => [Authentication::class, Csrf::class]],
        ];
    }

    /**
     * @throws ArgumentTypeException
     */
    public function indexAction(): HttpResponse
    {
        if (!file_exists($_SERVER['DOCUMENT_ROOT'] . '/' . self::YAML_PATH)) {
            throw new RuntimeException('Файл с разметкой не существует');
        }

        $response = new HttpResponse();
        $response->setContent(new UiPage(self::YAML_PATH)->getHtml());

        return $response;
    }
}
