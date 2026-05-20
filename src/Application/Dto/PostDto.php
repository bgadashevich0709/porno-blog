<?php

namespace App\Application\Dto;

use App\Exceptions\InvalidArgumentException;
use App\Traits\DateTimeParserTrait;

/**
 * @noinspection PhpSyntaxErrorInspection
 */
class PostDto
{
    use DateTimeParserTrait;

    // Временно закомментировано для PHP 8.3
    // public int $viewsCount {
    //     set {
    //         if ($value < 0) {
    //             throw new InvalidArgumentException("Просмотры не могут быть отрицательными");
    //         }
    //         $this->viewsCount = $value;
    //     }
    // }

    public int $viewsCount;

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
        int $viewsCount,
        public \DateTimeImmutable $createdAt
    ) {
        // Временная валидация для PHP 8.3 вместо хука свойства
        if ($viewsCount < 0) {
            throw new InvalidArgumentException("Просмотры не могут быть отрицательными");
        }
        $this->viewsCount = $viewsCount;
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
            id: (string) $data['id'],
            title: (string) $data['title'],
            description: (string) $data['description'],
            content: (string) $data['content'],
            image: (string) $data['image'],
            categoryIds: $categoryIds,
            viewsCount: (int) $data['views'],
            createdAt: self::parseRequiredDateTime($data, 'createdAt')
        );
    }
}
