<?php

namespace App\UseCase\Controller\Post\Dto;

use App\Application\Dto\PostDto;
use App\Application\Dto\PostListItemDto;
use JsonSerializable;

class PostShowDto implements JsonSerializable
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
}
