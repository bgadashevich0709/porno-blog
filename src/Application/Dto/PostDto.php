<?php

namespace App\Application\Dto;

use App\Traits\DateTimeParserTrait;

class PostDto
{
    use DateTimeParserTrait;

    /* Временно отключено до PHP 8.4
public int $viewsCount {
    set {
        if ($value < 0) {
            throw new \InvalidArgumentException("Просмотры не могут быть отрицательными");
        }
        $this->viewsCount = $value;
    }
}
*/

    /**
     * @param array<string> $categoryIds Массив строк (UUID) категорий
     */
    public function __construct(
        public string $id,
        public string $title,
        public string $description,
        public string $content,
        public string $image,
        public array  $categoryIds,
        public int    $viewsCount,
        public \DateTimeImmutable $createdAt // Дата публикации
    )
    {
        // Старый вариант валидации до PHP 8.4
        if ($this->viewsCount < 0) {
            throw new \InvalidArgumentException("Просмотры не могут быть отрицательными");
        }
    }

    /**
     * @throws \Exception
     */
    public static function fromArray(array $data): self
    {
        $categoryIds = is_string($data['category_ids'])
            ? json_decode($data['category_ids'], true)
            : ($data['category_ids'] ?? []);

        return new self(
            id: (string)$data['id'],
            title: (string)$data['title'],
            description: (string)$data['description'],
            content: (string)$data['content'],
            image: (string)$data['image'],
            categoryIds: $categoryIds,
            viewsCount: (int)$data['views'],
            createdAt: self::parseRequiredDateTime($data, 'createdAt')
        );
    }
}
