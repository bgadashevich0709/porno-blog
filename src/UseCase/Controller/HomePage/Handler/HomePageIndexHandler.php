<?php

namespace App\UseCase\Controller\HomePage\Handler;

use App\Application\Dto\CategoryGroupDto;
use App\Application\Dto\PostListItemDto;
use App\Application\Service\PostDtoFactory;
use App\Common\Router\UrlGenerator;
use App\Controller\CategoryController;
use App\Repository\CategoryRepositoryInterface;
use App\Repository\PostRepositoryInterface;
use App\Traits\PostMapper;
use App\UseCase\Controller\HomePage\Dto\HomepageDataDto;

readonly class HomePageIndexHandler implements HomePageIndexHandlerInterface
{
    use PostMapper;

    public function __construct(
        private CategoryRepositoryInterface $categoryRepository,
        private PostRepositoryInterface     $postRepository,
        private UrlGenerator                $urlGenerator,
        protected PostDtoFactory $postDtoFactory
    ) {}

    public function getHomepageData(int $postsLimit = 3): HomepageDataDto
    {
        $categoriesRaw = $this->categoryRepository->findNonEmptyCategories();
        if (empty($categoriesRaw)) {
            return new HomepageDataDto([]);
        }

        // 1. Забираем пул самых свежих постов (всего 1 быстрый запрос к БД)
        // Запас в 300 элементов гарантирует, что мы заполним все категории по 3 поста
        $rawPosts = $this->postRepository->findLatestPostsWithCategories();

        /** @var array<PostListItemDto> $allPostDtos */
        $allPostDtos = $this->mapPosts($rawPosts);

        $categoryPostsMap = [];
        $usedPostIds = [];

        // 2. Распределяем посты по категориям прямо в PHP
        foreach ($allPostDtos as $postDto) {
            // Если пост уже попал в какую-то секцию, исключаем дублирование на главной
            if (in_array($postDto->id, $usedPostIds, true)) {
                continue;
            }

            foreach ($postDto->categoryIds as $catId) {
                $catId = (string) $catId;

                // Инициализируем массив для категории, если его еще нет
                if (!isset($categoryPostsMap[$catId])) {
                    $categoryPostsMap[$catId] = [];
                }

                // Если лимит для этой категории еще не достигнут — добавляем пост
                if (count($categoryPostsMap[$catId]) < $postsLimit) {
                    $categoryPostsMap[$catId][] = $postDto;
                    $usedPostIds[] = $postDto->id;

                    // Так как мы зафиксировали пост за этой категорией и не хотим дублей,
                    // выходим из внутреннего цикла по категориям для этого поста
                    break;
                }
            }
        }

        // 3. Собираем финальные DTO групп для вывода на экран
        $categoryGroups = [];
        foreach ($categoriesRaw as $catRow) {
            $catId = (string) $catRow['id'];
            $categoryPosts = $categoryPostsMap[$catId] ?? [];

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
