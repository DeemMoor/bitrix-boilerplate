<?php

namespace Vendor\Engine\Controller;

use BitrixOA\UiPage;
use RuntimeException;
use Bitrix\Main\HttpResponse;
use Bitrix\Main\ArgumentTypeException;

class ApiDocController extends BaseController
{
    private const string YAML_PATH = 'local/bitrixoa.yaml';

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
