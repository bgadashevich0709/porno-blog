<?php

namespace App\Application\Service;

use App\Application\Enum\ImageFormat;

class ImageService
{
    public function getUrl(?string $imageUrl, ImageFormat $format): string
    {
        if (empty($imageUrl)) {
            return $format->getDefaultPlaceholder();
        }

        if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            //return $imageUrl;
        }

        $rootProjectDir = dirname(__DIR__, 3);
        $absolutePath = $rootProjectDir . '/public' . '/' . ltrim($imageUrl, '/');

        if (!file_exists($absolutePath)) {
            return $format->getDefaultPlaceholder();
        }

        return $imageUrl;
    }
}
