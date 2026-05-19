<?php

namespace App\UseCase\Post;

use App\Application\Dto\BreadcrumbItemDto;
use App\Application\Dto\PostDto;
use App\Common\Router\UrlGenerator;
use App\Common\Tracking\PageViewTracker;
use App\Controller\IndexController;
use App\Exceptions\ResourceNotFoundException;
use App\Repository\PostRepositoryInterface;
use App\UseCase\Post\Dto\PostShowDto;
use App\Traits\PostMapper;
use Exception;

readonly class PostShowHandler
{
    use PostMapper;

    public function __construct(
        private PostRepositoryInterface $postRepository,
        private UrlGenerator            $urlGenerator,
        private PageViewTracker         $pageViewTracker
    ) {}

    /**
     * @throws Exception
     */
    public function getPostShowData(string $id, int $similarLimit = 3): PostShowDto
    {
        $postDto = $this->getPostOrThrow($id);

        if ($this->pageViewTracker->trackCurrentPage()) {
            $this->postRepository->incrementViewsCount($id);
            $postDto->viewsCount++;
        }

        $breadcrumbs = $this->buildBreadcrumbs($postDto);

        if (empty($postDto->categoryIds)) {
            return new PostShowDto($postDto, [], $breadcrumbs);
        }

        $similarPosts = $this->getSimilarPosts($id, $postDto->categoryIds, $similarLimit);

        return new PostShowDto($postDto, $similarPosts, $breadcrumbs);
    }

    /**
     * @throws ResourceNotFoundException
     */
    private function getPostOrThrow(string $id): PostDto
    {
        $rawPost = $this->postRepository->findPostById($id);

        if ($rawPost === null) {
            throw new ResourceNotFoundException("Пост с ID '{$id}' не найден.");
        }

        return PostDto::fromArray($rawPost);
    }

    private function buildBreadcrumbs(PostDto $postDto): array
    {
        return [
            new BreadcrumbItemDto(
                'Главная',
                $this->urlGenerator->generate(IndexController::class, 'index')
            ),
            new BreadcrumbItemDto($postDto->title)
        ];
    }

    private function getSimilarPosts(string $currentPostId, array $categoryIds, int $limit): array
    {
        $rawSimilar = $this->postRepository->findLatestPostsForCategories($categoryIds, $limit + 1, 0);

        $filtered = array_filter($rawSimilar, static fn(array $row) => $row['id'] !== $currentPostId);
        $sliced = array_slice($filtered, 0, $limit);

        return $this->mapPosts($sliced);
    }
}
