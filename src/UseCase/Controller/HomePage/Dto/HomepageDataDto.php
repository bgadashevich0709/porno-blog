<?php

namespace App\UseCase\Controller\HomePage\Dto;

use App\Application\Dto\CategoryGroupDto;
use App\Application\Service\Meta\HasMetaInterface;

class HomepageDataDto implements HasMetaInterface
{
    /**
     * @param array<CategoryGroupDto> $categories
     */
    public function __construct(
        public array $categories
    ) {}

    public function getMetaTitle(): string
    {
        return "Главная страница — Мой Кастомный Блог";
    }

    public function getMetaDescription(): string
    {
        return "Добро пожаловать на наш кастомный блог! Читайте самые актуальные статьи, отсортированные по категориям.";
    }

    public function getMetaKeywords(): string
    {
        return "блог, главная страница, статьи, категории, php разработка";
    }
}
