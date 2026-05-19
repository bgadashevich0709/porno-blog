<?php

namespace App\UseCase\HomePage;

use App\Application\Dto\CategoryGroupDto;
use App\Application\Dto\PostListItemDto;
use App\Common\Router\UrlGenerator;
use App\Controller\CategoryController;
use App\Repository\CategoryRepositoryInterface;
use App\Repository\PostRepositoryInterface;
use App\Traits\PostMapper;
use App\UseCase\HomePage\Dto\HomepageDataDto;
use Doctrine\DBAL\Exception;

readonly class HomePageIndexHandler
{
    use PostMapper;

    public function __construct(
        private CategoryRepositoryInterface $categoryRepository,
        private PostRepositoryInterface     $postRepository,
        private UrlGenerator       $urlGenerator
    ) {}

    /**
     * Формирует структурированные и оптимизированные данные для главной страницы сайта.
     *
     * МЕТОДОЛОГИЯ И БИЗНЕС-ЛОГИКА:
     * Метод решает задачу построения блочной структуры главной страницы (Категория -> Список постов).
     * Ключевое бизнес-требование — "Сквозная уникальность контента": один и тот же пост не должен
     * дублироваться на главной странице, даже если он привязан к нескольким выводимым категориям.
     * Метод динамически распределяет статьи, ведет сквозной реестр выведенных публикаций (`$usedPostIds`)
     * и гарантирует заполнение каждой категории до целевого лимита.
     *
     * АЛГОРИТМ РАБОТЫ И КРИТИЧЕСКИЕ СЦЕНАРИИ:
     * 1. Шаг 1 (Инициализация): Запрашиваются только непустые категории (`findNonEmptyCategories`).
     * 2. Шаг 2 (Упреждающий пакетный запрос): База данных опрашивается один раз (`findLatestPostsForCategories`)
     *    для получения стартового пула свежих постов по всем категориям разом (с запасом `max(5, $postsLimit + 2)`).
     * 3. Шаг 3 (Распределение и ленивая дозагрузка):
     *    - Для каждой категории итеративно ищутся подходящие уникальные DTO в оперативной памяти.
     *    - КРИТИЧЕСКИЙ СЦЕНАРИЙ (Дефицит данных): Если из-за удаления сквозных дубликатов в памяти
     *      закончились уникальные публикации для текущей категории, алгоритм смещает скользящее окно оконной
     *      функции (`$offsetsByCategory`) и выполняет точечный изолированный ДОЗАПРОС к БД по конкретной категории.
     *    - Свежие данные подмешиваются в общий пул, минимизируя общее количество обращений к диску.
     * 4. Шаг 4 (Сборка структуры): Сырые данные трансформируются в иммутабельные DTO, обогащенные
     *    валидными ссылками от компонента `UrlGenerator`. Empty-категории без уникального контента отсекаются.
     *
     * АРХИТЕКТУРНАЯ И ВЫЧИСЛИТЕЛЬНАЯ ОПТИМИЗАЦИЯ:
     * - Профилактика N+1 Query Problem: Первичный пул данных выгружается одним SQL-запросом с оператором
     *   `IN (:categoryIds)` вместо выполнения `N` отдельных запросов в цикле по каждой категории.
     * - Память и IO-оптимизация: Тяжеловесное текстовое поле `content`/`text` полностью исключено из
     *   выборки на уровне репозитория. В DTO передаются только метаданные (`title`, `description`, `image`),
     *   что минимизирует объем данных, пересылаемых по сети от СУБД к PHP, и снижает нагрузку на RAM.
     * - Изоляция SQL и маршрутизации: Слой репозиториев возвращает чистые сырые массивы (Raw Data).
     *   Генерация ссылок и сборка DTO инкапсулированы внутри приватных методов UseCase-обработчика,
     *   что гарантирует соблюдение принципа Single Responsibility (SRP).
     * - Безопасный Offset-Scoping: Массив `$offsetsByCategory` является атомарным для каждой категории.
     *   Это гарантирует, что точечная дозагрузка для Категории №4 не сломает пагинацию и не вызовет
     *   пропуск данных (Data Skipping) при последующей обработке Категории №5.
     *
     * @param int $postsLimit Целевое количество уникальных постов, отображаемых в рамках одной категории.
     * @return HomepageDataDto Иммутабельный контейнер со списком структурированных групп категорий и их постов.
     * @throws Exception Если произошла синтаксическая или транспортная ошибка на уровне драйвера базы данных.
     */
    public function getHomepageData(int $postsLimit = 3): HomepageDataDto
    {
        $categoriesRaw = $this->categoryRepository->findNonEmptyCategories();
        if (empty($categoriesRaw)) {
            return new HomepageDataDto([]);
        }

        $categoryIds = array_column($categoriesRaw, 'id');
        $categoryGroups = [];
        $usedPostIds = [];

        $currentFetchLimit = max(5, $postsLimit + 2);
        $offsetsByCategory = array_fill_keys($categoryIds, 0);

        $rawPosts = $this->postRepository->findLatestPostsForCategories($categoryIds, $currentFetchLimit, 0);

        /** @var array<PostListItemDto> $allPostDtos */
        $allPostDtos = $this->mapPosts($rawPosts);


        // Распределяем посты по категориям
        foreach ($categoriesRaw as $catRow) {
            $catId = (string) $catRow['id'];
            $categoryPosts = [];

            while (count($categoryPosts) < $postsLimit) {
                foreach ($allPostDtos as $postDto) {
                    if (count($categoryPosts) >= $postsLimit) {
                        break;
                    }

                    $isBelongsToCategory = in_array($catId, $postDto->categoryIds, true);
                    $isAlreadyShown = in_array($postDto->id, $usedPostIds, true);

                    if ($isBelongsToCategory && !$isAlreadyShown) {
                        $categoryPosts[] = $postDto;
                        $usedPostIds[] = $postDto->id;
                    }
                }

                if (count($categoryPosts) === $postsLimit) {
                    break;
                }

                // КРИТИЧЕСКИЙ СЦЕНАРИЙ: Уникальные посты закончились, делаем точечный дозапрос
                $offsetsByCategory[$catId] += $currentFetchLimit;

                $nextRawBatch = $this->postRepository->findLatestPostsForCategories(
                    [$catId],
                    $currentFetchLimit,
                    $offsetsByCategory[$catId]
                );

                if (empty($nextRawBatch)) {
                    break; // контент в БД исчерпан
                }

                $allPostDtos = array_merge($allPostDtos, $this->mapPosts($nextRawBatch));
            }

            if (!empty($categoryPosts)) {
                $categoryGroups[] = $this->mapCategoryGroup($catRow, $categoryPosts);
            }
        }

        return new HomepageDataDto($categoryGroups);
    }

    private function mapCategoryGroup(array $catRow, array $categoryPosts): CategoryGroupDto
    {
        $categoryUrl = $this->urlGenerator->generate(CategoryController::class, 'show', [
            'id' => $catRow['id'],
        ]);

        return CategoryGroupDto::fromArray($catRow, $categoryPosts, $categoryUrl);
    }
}
