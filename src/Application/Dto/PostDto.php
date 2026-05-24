<?php

namespace App\Application\Dto;

use App\Application\Service\Meta\HasMetaInterface; // ПОДКЛЮЧИЛИ
use App\Exceptions\InvalidArgumentException;
use App\Traits\DateTimeParserTrait;

class PostDto implements HasMetaInterface // ИМПЛЕМЕНТИРУЕМ ТУТ
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
        $this->viewsCount = $viewsCount;
    }

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

    public function getMetaTitle(): string
    {
        return $this->title;
    }

    public function getMetaDescription(): string
    {
        $textSource = $this->description ?: $this->content ?: '';
        $cleanText = strip_tags($textSource);
        if (mb_strlen($cleanText) > 160) {
            return mb_substr($cleanText, 0, 157) . '...';
        }
        return $cleanText ?: 'Читать статью на нашем кастомном блоге.';
    }

    public function getMetaKeywords(): string
    {
        return "блог, статья, " . mb_strtolower($this->title);
    }
}
