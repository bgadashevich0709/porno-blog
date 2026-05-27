<?php

declare(strict_types=1);

namespace App\Common\Pagination\Dto;

final class PaginationContext
{
    /**
     * @param string $routeController Полное имя класса контроллера (например, CategoryController::class)
     * @param string $routeMethod Название метода экшена контроллера
     * @param array<string, mixed> $routeParams Параметры маршрута для генератора URL (например, ['id' => $id])
     * @param string|null $cachePrefix Уникальный префикс для формирования ключа кэша
     * @param array<string> $cacheTags Теги кэша для массовой инвалидации
     * @param int $cacheTtl Время жизни кэша в секундах
     * @param array<string, mixed> $additionalParams Любые кастомные параметры домена (например, categoryId)
     */
    public function __construct(
        public readonly string  $routeController,
        public readonly string  $routeMethod = 'show',
        public readonly array   $routeParams = [],
        public readonly ?string $cachePrefix = null,
        public readonly array   $cacheTags = ['posts_list'],
        public readonly int     $cacheTtl = 300,
        private readonly array  $additionalParams = []
    ) {}

    public function getParam(string $key, mixed $default = null): mixed
    {
        return $this->additionalParams[$key] ?? $default;
    }
}
