<?php

namespace App\Modules\Blog\Repository;

use Doctrine\DBAL\Query\QueryBuilder;

interface PostRepositoryInterface
{
    /**
     * Возвращает список свежих постов для конкретной категории с исключением дубликатов.
     * Используется на главной странице в цикле обхода категорий.
     *
     * @param string $categoryId ID категории, для которой ищем посты
     * @param array $excludedIds Массив ID постов, которые уже выведены выше и должны быть исключены
     * @param int $limit Максимальное количество возвращаемых постов
     * @return array Массив сырых строк постов из БД
     */
    public function findLatestPostsForCategoryExcluding(string $categoryId, array $excludedIds, int $limit = 3): array;

    /**
     * Находит похожие посты на основе пересечения категорий.
     * Используется на детальной странице публикации в блоке "Похожие материалы".
     *
     * @param array $categoryIds Массив ID категорий текущего поста
     * @param int $limit Количество похожих постов для вывода
     * @return array Массив сырых строк похожих постов
     */
    public function findRelatedPostsByCategories(array $categoryIds, int $limit): array;

    /**
     * Получает полную информацию об одном посте по его идентификатору.
     * Используется на детальной странице публикации.
     *
     * @param string $id ID искомого поста
     * @return array|null Данные поста в виде массива или null, если пост не найден
     */
    public function findPostById(string $id): ?array;

    /**
     * Атомарно увеличивает счетчик просмотров публикации на +1.
     * Синхронно обновляет данные в таблице posts и денормализованной таблице post_category.
     *
     * @param string $id ID просмотренного поста
     * @return void
     */
    public function incrementViewsCount(string $id): void;

    /**
     * Конструктор подзапроса для высокопроизводительной постраничной навигации.
     * Выбирает только идентификаторы (id) постов для конкретной категории с сортировкой.
     *
     * @param string $categoryId ID категории для фильтрации
     * @param string $sortField Поле для сортировки (например, 'createdAt' или 'views')
     * @param string $sortWay Направление сортировки ('ASC' или 'DESC')
     * @return QueryBuilder Объект конструктора запроса для пагинатора
     */
    public function getIdSubQueryBuilder(string $categoryId, string $sortField, string $sortWay): QueryBuilder;

    /**
     * Конструктор быстрого запроса для подсчета общего количества постов в категории.
     * Использует COUNT(*) по покрывающему индексу таблицы связей.
     *
     * @param string $categoryId ID категории
     * @return QueryBuilder Объект конструктора запроса для получения общего числа строк
     */
    public function getCountQueryBuilder(string $categoryId): QueryBuilder;

    /**
     * Конструктор базового запроса для пагинации общего списка постов.
     * Используется для вывода всей ленты публикаций без привязки к конкретной категории.
     *
     * @return QueryBuilder Объект конструктора запроса для общей ленты
     */
    public function getPostsByDataQueryBuilder(): QueryBuilder;
}
