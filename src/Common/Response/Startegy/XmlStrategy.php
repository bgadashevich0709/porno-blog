<?php

namespace App\Common\Response\Startegy;

/**
 * Стратегия формирования ответа в формате XML.
 * Конвертирует переданный массив данных в валидный XML-документ.
 * Сейчас не используется нигде, добавил для примера
 * РАБОТОСПОСОБНОСТЬ НЕ ПРОВРЕНА!
 */
class XmlStrategy implements ResponseStrategyInterface
{
    public function render(string $target, array $data): void
    {
        header('Content-Type: application/xml; charset=utf-8');

        if ($target === 'error') {
            http_response_code(400);
        }

        $responseData = $data['data'] ?? $data;

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><response/>');

        $this->arrayToXml($responseData, $xml);

        echo $xml->asXML();
    }

    private function arrayToXml(array $data, \SimpleXMLElement $xml): void
    {
        foreach ($data as $key => $value) {
            if (is_numeric($key)) {
                $key = 'item';
            }

            if (is_array($value)) {
                $subnode = $xml->addChild($key);
                $this->arrayToXml($value, $subnode);
            } else {
                // Защищаем строку от спецсимволов XML (например, &, <, >)
                $xml->addChild($key, htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'));
            }
        }
    }
}
