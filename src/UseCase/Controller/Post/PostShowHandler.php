<?php

namespace App\UseCase\Controller\Post;

use App\Application\Dto\BreadcrumbItemDto;
use App\Application\Dto\PostDto;
use App\Application\Service\PostDtoFactory;
use App\Common\Event\EventDispatcher;
use App\Common\Router\UrlGenerator;
use App\Common\Tracking\PageViewTracker;
use App\Controller\IndexController;
use App\Exceptions\ResourceNotFoundException;
use App\Repository\PostRepositoryInterface;
use App\Traits\PostMapper;
use App\UseCase\Controller\Post\Dto\PostShowDto;
use App\UseCase\Event\PostUpdatedEvent;
use Exception;

readonly class PostShowHandler
{
    use PostMapper;

    public function __construct(
        private PostRepositoryInterface $postRepository,
        private UrlGenerator            $urlGenerator,
        private PageViewTracker         $pageViewTracker,
        private EventDispatcher         $dispatcher,
        private PostDtoFactory        $postDtoFactory,
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
        if ($this->pageViewTracker->trackCurrentPage()) {
            $this->postRepository->incrementViewsCount($id);
            $postDto->viewsCount++;

            $this->dispatcher->dispatch(new PostUpdatedEvent($id));
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

    private function buildBreadcrumbs(PostDto $postDto): array
    {
        return [
            new BreadcrumbItemDto(
                'Главная',
                $this->urlGenerator->generate(IndexController::class, 'index')
            ),
            new BreadcrumbItemDto($postDto->title),
        ];
    }

    /**
     * Возвращает список похожих постов.
     *
     * Критерии в ТЗ не уточнены, поэтому берутся последние посты по дате
     * из тех же категорий, исключая текущий просматриваемый пост.
     */
    private function getSimilarPosts(string $currentPostId, array $categoryIds, int $limit): array
    {
        $rawSimilar = $this->postRepository->findRelatedPostsByCategories($categoryIds, $limit + 1, 0);

        $filtered = array_filter($rawSimilar, static fn(array $row) => $row['id'] !== $currentPostId);
        $sliced = array_slice($filtered, 0, $limit);

        return $this->mapPosts($sliced);
    }
}
