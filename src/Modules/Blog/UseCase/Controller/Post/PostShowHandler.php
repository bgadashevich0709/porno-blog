<?php

declare(strict_types=1);

namespace App\Modules\Blog\UseCase\Controller\Post;

use App\Common\Event\EventDispatcher;
use App\Common\Http\RefererProvider;
use App\Common\Router\UrlGenerator;
use App\Common\Tracking\PageViewTracker;
use App\Exceptions\ResourceNotFoundException;
use App\Modules\Blog\Application\Dto\BreadcrumbItemDto;
use App\Modules\Blog\Application\Dto\PostDto;
use App\Modules\Blog\Application\Service\PostDtoFactory;
use App\Modules\Blog\Controller\CategoryController;
use App\Modules\Blog\Controller\IndexController;
use App\Modules\Blog\Repository\CategoryRepositoryInterface;
use App\Modules\Blog\Repository\PostRepositoryInterface;
use App\Modules\Blog\Traits\PostMapper;
use App\Modules\Blog\UseCase\Controller\Post\Dto\PostShowDto;
use App\Modules\Blog\UseCase\Event\PostUpdatedEvent;
use Exception;
use Psr\Log\LoggerInterface;

// Добавили импорт логгера

final readonly class PostShowHandler
{
    use PostMapper;

    public function __construct(
        private PostRepositoryInterface     $postRepository,
        private CategoryRepositoryInterface $categoryRepository,
        private RefererProvider             $refererProvider,
        private UrlGenerator                $urlGenerator,
        private PageViewTracker             $pageViewTracker,
        private EventDispatcher             $dispatcher,
        private PostDtoFactory              $postDtoFactory,
        private LoggerInterface             $logger, // Внедряем логгер через DI
    ) {}

    /**
     * @throws Exception
     */
    public function getPostShowData(string $id, int $similarLimit = 3): PostShowDto
    {
        $postDto = $this->getPostOrThrow($id);

        $this->trackAndNotify($id, $postDto);

        $breadcrumbs = $this->buildBreadcrumbs($postDto);

        if (empty($postDto->categoryIds)) {
            return new PostShowDto($postDto, [], $breadcrumbs);
        }

        $similarPosts = $this->getSimilarPosts($id, $postDto->categoryIds, $similarLimit);

        return new PostShowDto($postDto, $similarPosts, $breadcrumbs);
    }

    private function trackAndNotify(string $id, PostDto $postDto): void
    {
        try {
            if ($this->pageViewTracker->trackCurrentPage()) {
                $this->postRepository->incrementViewsCount($id);
                $postDto->viewsCount++;

                $this->dispatcher->dispatch(new PostUpdatedEvent($id));
            }
        } catch (Exception $e) {
            $this->logger->error('Ошибка трекера посещений при просмотре поста: ' . $e->getMessage(), [
                'post_id' => $id,
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * @throws ResourceNotFoundException
     * @throws Exception
     */
    private function getPostOrThrow(string $id): PostDto
    {
        $rawPost = $this->postRepository->findPostById($id);

        if ($rawPost === null) {
            throw new ResourceNotFoundException("Пост с ID '{$id}' не найден.");
        }

        return $this->postDtoFactory->createPostDto($rawPost);
    }

    /**
     * @return BreadcrumbItemDto[]
     */
    private function buildBreadcrumbs(PostDto $postDto): array
    {
        $breadcrumbs = [
            new BreadcrumbItemDto(
                'Главная',
                $this->urlGenerator->generate(IndexController::class, 'index')
            ),
        ];

        $refererUrl = $this->refererProvider->getReferer();

        $targetCategoryId = $this->detectCategoryFromReferer($postDto->categoryIds, $refererUrl);

        if ($targetCategoryId !== null) {
            $category = $this->categoryRepository->getById($targetCategoryId);

            if ($category !== null) {
                $breadcrumbs[] = new BreadcrumbItemDto(
                    (string) $category['name'],
                    $this->urlGenerator->generate(CategoryController::class, 'show', ['id' => (string) $category['id']])
                );
            }
        }

        $breadcrumbs[] = new BreadcrumbItemDto($postDto->title);

        return $breadcrumbs;
    }

    /**
     * @param array<string|int> $categoryIds
     */
    private function detectCategoryFromReferer(array $categoryIds, ?string $refererUrl): ?string
    {
        if (empty($refererUrl) || empty($categoryIds)) {
            return null;
        }

        foreach ($categoryIds as $categoryId) {
            $categoryUrl = $this->urlGenerator->generate(CategoryController::class, 'show', ['id' => (string) $categoryId]);

            if (str_contains($refererUrl, $categoryUrl)) {
                return (string) $categoryId;
            }
        }

        return null;
    }

    /**
     * Возвращает список похожих постов.
     */
    private function getSimilarPosts(string $currentPostId, array $categoryIds, int $limit): array
    {
        $rawSimilar = $this->postRepository->findRelatedPostsByCategories($categoryIds, $limit + 1, 0);

        $filtered = array_filter($rawSimilar, static fn(array $row) => $row['id'] !== $currentPostId);
        $sliced = array_slice($filtered, 0, $limit);

        return $this->mapPosts($sliced);
    }
}
