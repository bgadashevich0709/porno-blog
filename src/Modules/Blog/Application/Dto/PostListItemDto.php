<?php

namespace App\Modules\Blog\Application\Dto;

use App\Exceptions\InvalidArgumentException;
use App\Modules\Blog\Traits\DateTimeParserTrait;

class PostListItemDto
{
    use DateTimeParserTrait;


    public int $viewsCount {
        set {
            if ($value < 0) {
                throw new InvalidArgumentException("Просмотры не могут быть отрицательными");
            }
            $this->viewsCount = $value;
        }
    }

    /**
     * @param array<string> $categoryIds Массив строк (UUID) категорий
     */
    public function __construct(
        public string $id,
        public string $title,
        public string $description,
        public string $image,
        int $viewsCount,
        public array  $categoryIds,
        public string $link,
        public \DateTimeImmutable $createdAt
    ) {
        $this->viewsCount = $viewsCount;
    }

    /**
     * @throws \Exception
     */
    public static function fromArray(array $data, string $link): self
    {
        $categoryIds = is_string($data['category_ids'])
            ? json_decode($data['category_ids'], true)
            : ($data['category_ids'] ?? []);

        return new self(
            id: (string) $data['id'],
            title: (string) $data['title'],
            description: (string) $data['description'],
            image: (string) $data['image'],
            viewsCount: (int) $data['views'],
            categoryIds: $categoryIds,
            link: $link,
            createdAt: self::parseRequiredDateTime($data, 'createdAt')
        );
    }
}
