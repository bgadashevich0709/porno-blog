<?php

/**
 * @deprecated Этот конфигурационный файл больше не используется.
 * Роутер теперь автоматически сканирует директорию src/ и находит контроллеры по атрибуту #[AsController].
 *
 * Оставлено для обратной совместимости. Если автоматическое сканирование сломается,
 * можно раскомментировать строки ниже и временно вернуть ручную регистрацию через старый метод.
 */

use App\Controller\CategoryController;
use App\Controller\IndexController;
use App\Controller\PostController;

return [
    // IndexController::class,
    // CategoryController::class,
    // PostController::class,
];
