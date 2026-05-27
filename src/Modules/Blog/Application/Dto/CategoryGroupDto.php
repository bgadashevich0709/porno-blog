<?php

namespace App\Modules\Blog\Application\Dto;

class CategoryGroupDto
{
    /**
     * @param array<PostListItemDto> $latestPosts
     */
    public function __construct(
        public string $id,
        public string $title,
        public array  $latestPosts,
        public string $link
    ) {}

    /**
     * @param array<PostListItemDto> $latestPosts
     */
    public static function fromArray(array $data, array $latestPosts, string $link): self
    {
        return new self(
            id: (string) $data['id'],
            title: (string) $data['name'],
            latestPosts: $latestPosts,
            link: $link
        );
    }
}
