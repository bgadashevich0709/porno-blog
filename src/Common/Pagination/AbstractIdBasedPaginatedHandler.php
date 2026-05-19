<?php

declare(strict_types=1);

namespace App\Common\Pagination;

use App\Common\Pagination\Dto\PaginateDto;
use App\Common\Pagination\Pager;

/**
 * Архитектурный паттерн: Шаблонный метод (Template Method).
 *
 * ЭТОТ КЛАСС ОТВЕЧАЕТ ЗА СВЕРХБЫСТРУЮ ПАГИНАЦИЮ НА МИЛЛИОНАХ СТРОК (Паттерн Late Row Lookup).
 */
abstract class AbstractIdBasedPaginatedHandler
{
    /**
     * Основной скелет алгоритма высокопроизводительной пагинации.
     * Теперь принимает строго PaginationRequestInterface и полностью независим от категорий.
     */
    protected function paginate(PaginationRequestInterface $requestDto, array $context = []): array
    {
        // 1. Получаем общее количество записей
        $totalItems = $this->getTotalCount($context);
        $totalPages = (int) ceil($totalItems / $requestDto->getPerPage());

        if ($totalItems === 0) {
            return [
                'postsData' => new PaginateDto([], $requestDto->getPage(), $requestDto->getPerPage(), 0, 0),
                'pager' => new Pager($this->createUrlGenerator($context), $requestDto->getPerPage())
            ];
        }

        // 2. ШАГ А: Извлекаем плоский список только ID постов/продуктов/пользователей
        $offset = max(0, ($requestDto->getPage() - 1) * $requestDto->getPerPage());

        // Передаем интерфейс в fetchIds
        $idList = $this->fetchIds($offset, $requestDto->getPerPage(), $requestDto, $context);

        // 3. ШАГ Б: Вытягиваем полные строки только по найденным ID
        $rows = $this->fetchFullRowsByIds($idList, $context);

        // 4. Гидрируем сырые строки в конечные DTO
        $mappedItems = $this->mapRowsToDto($rows);

        // 5. Собираем структуры пагинации
        $postsData = new PaginateDto($mappedItems, $requestDto->getPage(), $requestDto->getPerPage(), $totalItems, $totalPages);
        $pager = new Pager($this->createUrlGenerator($context), $requestDto->getPerPage());

        return [
            'postsData' => $postsData,
            'pager' => $pager
        ];
    }

    protected function createUrlGenerator(array $context): \Closure
    {
        /** @var PaginationRequestInterface $requestDto */
        $requestDto = $context['requestDto'] ?? null;

        if (!$requestDto instanceof PaginationRequestInterface) {
            throw new \InvalidArgumentException('Context must contain an instance of PaginationRequestInterface.');
        }

        $controller = $context['route_controller'] ?? throw new \InvalidArgumentException('Missing "route_controller" in context.');
        $method = $context['route_method'] ?? 'show';
        $routeParams = $context['route_params'] ?? [];

        $queryParams = $requestDto->toArray();

        foreach ($queryParams as $key => $value) {
            if ($value instanceof \BackedEnum) {
                $queryParams[$key] = $value->value;
            } elseif (is_object($value)) {
                $queryParams[$key] = $value->name ?? (string)$value;
            }
        }

        $urlGenerator = $this->urlGenerator;

        return static function (int $pageNumber) use ($urlGenerator, $controller, $method, $routeParams, $queryParams): string {
            $queryParams['page'] = $pageNumber;

            return $urlGenerator->generate($controller, $method, array_merge($routeParams, $queryParams));
        };
    }

    // --- Абстрактные методы, которые каждая доменная область реализует по-своему ---
    abstract public function getTotalCount(array $context): int;

    // ИСПРАВЛЕНИЕ: Метод fetchIds теперь требует интерфейс, а не конкретный DTO категорий
    abstract public function fetchIds(int $offset, int $perPage, PaginationRequestInterface $requestDto, array $context): array;

    abstract public function fetchFullRowsByIds(array $idList, array $context): array;
    abstract public function mapRowsToDto(array $rows): array;
}
