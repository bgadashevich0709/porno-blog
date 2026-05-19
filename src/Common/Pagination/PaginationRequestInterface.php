<?php

namespace App\Common\Pagination;

interface PaginationRequestInterface
{
    /**
     * Номер текущей страницы.
     */
    public function getPage(): int;

    /**
     * Количество элементов на одну страницу.
     */
    public function getPerPage(): int;

    /**
     * Возвращает имя колонки для SQL секции ORDER BY (например, 'views' или 'createdAt')
     */
    public function getSortField(): string;

    /**
     * Возвращает направление сортировки (ASC или DESC)
     */
    public function getSortWay(): string;

    /**
     * Возвращает все параметры запроса в виде плоского массива для URL
     */
    public function toArray(): array;
}
