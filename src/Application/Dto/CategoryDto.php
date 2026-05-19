<?php

namespace App\Application\Dto;

class CategoryDto
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
}
