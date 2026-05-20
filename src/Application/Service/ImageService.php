<?php

namespace App\Application\Service;

use App\Application\Enum\ImageFormat;

class ImageService
{
    /**
     * Возвращает проверенный путь к изображению или заглушку для заданного формата
     *
     * @param string|null $imageUrl Ссылка на картинку или относительный путь
     * @param ImageFormat $format Формат отображения
     * @return string Итоговый URL/путь для фронтенда
     */
    public function getUrl(?string $imageUrl, ImageFormat $format): string
    {
        if (empty($imageUrl)) {
            return $format->getDefaultPlaceholder();
        }

        // 2. Если это внешняя ссылка (http:// или https://) — возвращаем как есть
        if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            //            return 'https://cdn.lifehacker.ru/wp-content/uploads/2025/01/milaya_kartinka_syurpriz_kotoraya_podnimaet_nastroenie_1735231354.png';
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
