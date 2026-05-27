<?php

namespace App\Modules\Blog\Application\Service;

use App\Modules\Blog\Application\Dto\PostDto;
use App\Modules\Blog\Application\Dto\PostListItemDto;
use App\Modules\Blog\Application\Enum\ImageFormat;

readonly class PostDtoFactory
{
    public function __construct(
        private ImageService $imageService
    ) {}

    /**
     * @throws \Exception
     */
    public function createPostDto(array $data): PostDto
    {
        $imageUrl = $this->imageService->getUrl($data['image'] ?? null, ImageFormat::Page);
        $data['image'] = $imageUrl;

        return PostDto::fromArray($data);
    }

    /**
     * @throws \Exception
     */
    public function createPostListItemDto(array $data, string $link): PostListItemDto
    {
        $imageUrl = $this->imageService->getUrl($data['image'] ?? null, ImageFormat::List);
        $data['image'] = $imageUrl;

        return PostListItemDto::fromArray($data, $link);
    }
}
