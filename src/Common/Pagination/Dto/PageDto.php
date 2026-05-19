<?php

namespace App\Common\Pagination\Dto;

class PageDto
{
    public function __construct(
        public int|string $label, // Номер страницы (например, 1, 2) или текст/иконка (например, '...')
        public ?int $pageNumber,  // Реальный номер страницы или null (если это разделитель '...')
        public ?string $url,      // Ссылка на страницу или null (если текущая или разделитель)
        public bool $isCurrent,   // Является ли страница текущей
        public bool $isSeparator  // Является ли элемент разделителем '...'
    ) {}

}
