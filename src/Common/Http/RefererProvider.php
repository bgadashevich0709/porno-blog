<?php

declare(strict_types=1);

namespace App\Common\Http;

final readonly class RefererProvider
{
    public function getReferer(): ?string
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? null;

        if (empty($referer)) {
            return null;
        }

        $filtered = filter_var($referer, FILTER_SANITIZE_URL);

        return $filtered ?: null;
    }
}
