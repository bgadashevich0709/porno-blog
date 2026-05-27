<?php

namespace App\Modules\Blog\Application\Dto;

use App\Modules\Blog\Application\Service\Meta\HasMetaInterface;

class CategoryDto implements HasMetaInterface
{
    public function __construct(
        public string $id,
        public string $title,
        public string $description,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: (string) $data['id'],
            title: (string) $data['name'],
            description: (string) $data['description'],
        );
    }

    public function getMetaTitle(): string
    {
        return "Категория: " . ($this->title ?: 'Без названия');
    }

    public function getMetaDescription(): string
    {
        $cleanText = strip_tags($this->description);
        if (mb_strlen($cleanText) > 160) {
            return mb_substr($cleanText, 0, 157) . '...';
        }

        return $cleanText ?: "Смотреть все посты в категории {$this->title}.";
    }


    public function getMetaKeywords(): string
    {
        return "блог, категория, " . mb_strtolower($this->title ?: 'общие');
    }
}
