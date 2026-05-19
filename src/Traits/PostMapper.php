<?php

namespace App\Traits;

use App\Application\Dto\PostListItemDto;
use App\Controller\PostController;

trait PostMapper
{
    /**
     * @return array<PostListItemDto>
     */
    private function mapPosts(array $rawPosts): array
    {
        return array_map(function (array $row) {
            $postLink = $this->urlGenerator->generate(PostController::class, 'show', [
                'id' => $row['id']
            ]);

            if (!isset($row['category_ids'])) {
                $row['category_ids'] = [];
            }

            return PostListItemDto::fromArray($row, $postLink);
        }, $rawPosts);
    }

}