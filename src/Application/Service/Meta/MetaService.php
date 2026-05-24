<?php

declare(strict_types=1);

namespace App\Application\Service\Meta;

use App\Application\Dto\MetaDto;

final readonly class MetaService
{
    private const DEFAULT_TITLE = 'Мой Кастомный Блог';
    private const DEFAULT_DESCRIPTION = 'Интересные статьи о разработке, архитектуре и коде.';
    private const DEFAULT_KEYWORDS = 'блог, php, программирование, mvp, кастомный движок';

    /**
     * Собирает MetaDto на основе переданного Response DTO страницы.
     */
    public function buildMeta(mixed $responseDto): MetaDto
    {
        if ($responseDto instanceof HasMetaInterface) {
            return new MetaDto(
                title: $responseDto->getMetaTitle() ?: self::DEFAULT_TITLE,
                description: $responseDto->getMetaDescription() ?: self::DEFAULT_DESCRIPTION,
                keywords: $responseDto->getMetaKeywords() ?: self::DEFAULT_KEYWORDS
            );
        }

        return new MetaDto(
            title: self::DEFAULT_TITLE,
            description: self::DEFAULT_DESCRIPTION,
            keywords: self::DEFAULT_KEYWORDS
        );
    }
}
