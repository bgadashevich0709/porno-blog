<?php

namespace Tests\Functional\Common\Router;

use PHPUnit\Framework\TestCase;

class HomepageE2ETest extends TestCase
{
    /**
     * ТЕСТ-БОМБА: Стучимся на реальный роут главной страницы через HTTP,
     * используя динамические настройки окружения без хардкода.
     */
    public function testHomepageReturnsSuccessHttpResponse(): void
    {
        // Вытаскиваем настройки из phpunit.xml (если их нет, берём безопасные дефолты)
        $appHost    = getenv('TEST_APP_HOST') ?: 'blog-nginx-1';
        $appPort    = getenv('TEST_APP_PORT') ?: '80';
        $hostHeader = getenv('TEST_HOST_HEADER') ?: 'localhost:8080';

        // Собираем внутренний URL для cURL
        $url = "http://{$appHost}:{$appPort}/";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Передаем динамический заголовок Host, чтобы веб-сервер не запутался
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Host: {$hostHeader}"]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // 1. Проверяем, что сетевой мост сработал и сервер вообще ответил
        $this->assertNotEquals(
            0,
            $httpCode,
            "Веб-сервер недоступен из контейнера по адресу {$url}. Проверь настройки в phpunit.xml!"
        );

        // 2. Главная проверка: роутер успешно обработал запрос главной страницы и вернул 200 OK
        $this->assertSame(
            200,
            $httpCode,
            sprintf("Роутер или код сломался! Вместо 200 OK получили код %d. Ответ сервера: %s", $httpCode, $response)
        );

        // 3. Проверяем, что вернулся именно наш сайт
        $this->assertStringContainsString(
            'Главная страница блога',
            $response,
            'Текст "Главная страница блога" не найден в ответе веб-сервера.'
        );
    }
}
