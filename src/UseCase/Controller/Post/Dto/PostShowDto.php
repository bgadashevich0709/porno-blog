<?php

namespace App\UseCase\Controller\Post\Dto;

use App\Application\Dto\PostDto;
use App\Application\Dto\PostListItemDto;
use App\Application\Service\Meta\HasMetaInterface;
use JsonSerializable;

class PostShowDto implements JsonSerializable, HasMetaInterface
{
    /**
     * @param array<PostListItemDto> $similarPosts
     */
    public function __construct(
        public PostDto $post,
        public array $similarPosts,
        public array $breadcrumbs,
    ) {}

    public function jsonSerialize(): mixed
    {
        return [
            'post' => $this->post,
            'similarPosts' => $this->similarPosts,
        ];
    }

    public function getMetaTitle(): string
    {
        return $this->post->getMetaTitle();
    }

    public function getMetaDescription(): string
    {
        return $this->post->getMetaDescription();
    }

    public function getMetaKeywords(): string
    {
        return $this->post->getMetaKeywords();
    }
}
