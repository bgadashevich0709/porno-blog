<?php

declare(strict_types=1);

namespace App\Controller;

use App\Common\Controller\AbstractController;
use App\Common\Middleware\AuthMiddleware;
use App\Common\Router\Attribute\AsController;
use App\Common\Router\Route\Get;

#[AsController]
class TestSecurityController extends AbstractController
{
    #[Get('/secret-club', middleware: [AuthMiddleware::class])]
    public function privatePage(): void
    {
        $currentUser = $_REQUEST['user'] ?? null;

        $this->render('secret_club.tpl', [
            'title' => 'Закрытый клуб для своих 🚀',
            'user'  => $currentUser,
        ]);
    }
}
