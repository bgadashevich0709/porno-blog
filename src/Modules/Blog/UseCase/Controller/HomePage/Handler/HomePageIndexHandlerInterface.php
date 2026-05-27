<?php

declare(strict_types=1);

namespace App\Modules\Blog\UseCase\Controller\HomePage\Handler;

use App\Modules\Blog\UseCase\Controller\HomePage\Dto\HomepageDataDto;

interface HomePageIndexHandlerInterface
{
    public function getHomepageData(int $postsLimit = 3): HomepageDataDto;
}
