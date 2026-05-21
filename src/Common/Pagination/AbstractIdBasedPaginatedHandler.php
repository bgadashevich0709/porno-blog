<?php

declare(strict_types=1);

namespace App\Common\Pagination;

use App\Common\Pagination\Dto\PaginateDto;

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
                'pager' => new Pager($this->createUrlGenerator($context), $requestDto->getPerPage()),
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
            'pager' => $pager,
        ];
    }

    /**
     * Создает генератор URL для постраничной навигации.
     *
     * Использование объекта PagerUrlGenerator вместо \Closure необходимо
     * для успешной сериализации данных пагинации при сохранении в кеш (Redis).
     */
    protected function createUrlGenerator(array $context): PagerUrlGenerator
    {
        $requestDto = $context['requestDto'] ?? null;
        if (!$requestDto instanceof PaginationRequestInterface) {
            throw new \InvalidArgumentException('Context must contain an instance of PaginationRequestInterface.');
        }

        $queryParams = $requestDto->toArray();
        foreach ($queryParams as $key => $value) {
            $queryParams[$key] = match (true) {
                $value instanceof \BackedEnum => $value->value,
                is_object($value) => $value->name ?? (string) $value,
                default => $value,
            };
        }

        return new PagerUrlGenerator(
            $this->urlGenerator,
            $context['route_controller'] ?? throw new \InvalidArgumentException('Missing "route_controller"'),
            $context['route_method'] ?? 'show',
            $context['route_params'] ?? [],
            $queryParams
        );
    }

    abstract public function getTotalCount(array $context): int;

    abstract public function fetchIds(int $offset, int $perPage, PaginationRequestInterface $requestDto, array $context): array;

    abstract public function fetchFullRowsByIds(array $idList, array $context): array;
    abstract public function mapRowsToDto(array $rows): array;
}
