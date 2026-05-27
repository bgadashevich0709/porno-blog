<?php

namespace App\Modules\Blog\Traits;

use App\Modules\Blog\Controller\PostController;

trait PostMapper
{
    private function mapPosts(array $rawPosts): array
    {
        return array_map(function (array $row) {
            $postLink = $this->urlGenerator->generate(PostController::class, 'show', [
                'id' => $row['id'],
            ]);

            if (!isset($row['category_ids'])) {
                $row['category_ids'] = [];
            }

            return $this->postDtoFactory->createPostListItemDto($row, $postLink);
        }, $rawPosts);
    }
}
