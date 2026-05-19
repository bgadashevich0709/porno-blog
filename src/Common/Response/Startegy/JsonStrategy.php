<?php

namespace App\Common\Response\Startegy;

class JsonStrategy implements ResponseStrategyInterface
{
    /**
     * @throws \JsonException
     */
    public function render(string $target, array $data): void
    {
        header('Content-Type: application/json; charset=utf-8');

        // Inject metadata if necessary based on the target action context
        if ($target === 'error') {
            http_response_code(400);
        }

        echo json_encode($data['data'] ?? $data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
