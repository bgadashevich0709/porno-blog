<?php

namespace App\Common\Response;

use App\Common\Response\Startegy\JsonStrategy;
use App\Common\Response\Startegy\ResponseStrategyInterface;
use App\Common\Response\Startegy\SmartyStrategy;
use App\Common\Response\Startegy\XmlStrategy;

class ResponseStrategyFactory
{
    public static function createFromCurrentRequest(): ResponseStrategyInterface
    {
        // Строго проверяем формат, который зафиксировал роутер для текущего адреса
        $routeFormat = $_SERVER['ROUTE_FORMAT'] ?? 'html';

        if ($routeFormat === 'json') {
            return new JsonStrategy();
        }

        if ($routeFormat === 'xml') {
            return new XmlStrategy();
        }

        // Для всех остальных случаев (включая 'html') возвращаем дефолтную стратегию шаблонизатора
        return new SmartyStrategy();
    }
}
