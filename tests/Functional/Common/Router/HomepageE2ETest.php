<?php

namespace Tests\Functional\Common\Router;

use App\Common\Config\Env;
use PHPUnit\Framework\TestCase;

class HomepageE2ETest extends TestCase
{
    /**
     * ТЕСТ-БОМБА: Стучимся на реальный роут главной страницы через HTTP,
     * используя динамические настройки окружения без хардкода.
     */
    public function testHomepageReturnsSuccessHttpResponse(): void
    {
        $appHost    = Env::get('TEST_APP_HOST', 'blog-nginx-1');
        $appPort    = Env::get('TEST_APP_PORT', '80');
        $hostHeader = Env::get('TEST_HOST_HEADER', 'localhost:8080');

        $url = "http://{$appHost}:{$appPort}/";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Host: {$hostHeader}"]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->assertNotEquals(
            0,
            $httpCode,
            "Веб-сервер недоступен из контейнера по адресу {$url}. Проверь настройки в phpunit.xml!"
        );

        $this->assertSame(
            200,
            $httpCode,
            sprintf("Роутер или код сломался! Вместо 200 OK получили код %d. Ответ сервера: %s", $httpCode, $response)
        );

        $this->assertStringContainsString(
            'Главная страница блога',
            $response,
            'Текст "Главная страница блога" не найден в ответе веб-сервера.'
        );
    }
}
