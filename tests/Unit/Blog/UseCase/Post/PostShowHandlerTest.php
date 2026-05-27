<?php

declare(strict_types=1);

namespace Tests\Unit\Blog\UseCase\Post;

use App\Common\Event\EventDispatcher;
use App\Common\Http\RefererProvider;
use App\Common\Router\UrlGenerator;
use App\Common\Tracking\PageViewTracker;
use App\Exceptions\ResourceNotFoundException;
use App\Modules\Blog\Application\Service\PostDtoFactory;
use App\Modules\Blog\Repository\CategoryRepositoryInterface;
use App\Modules\Blog\Repository\PostRepositoryInterface;
use App\Modules\Blog\UseCase\Controller\Post\Dto\PostShowDto;
use App\Modules\Blog\UseCase\Controller\Post\PostShowHandler;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class PostShowHandlerTest extends TestCase
{
    private PostRepositoryInterface $postRepositoryMock;
    private UrlGenerator $urlGeneratorMock;
    private PageViewTracker $pageViewTrackerMock;
    private PostShowHandler $handler;

    protected function setUp(): void
    {
        $this->postRepositoryMock = $this->createMock(PostRepositoryInterface::class);
        $categoryRepositoryMock = $this->createMock(CategoryRepositoryInterface::class);

        // 1. Обходим final у RefererProvider
        $refererReflection = new \ReflectionClass(RefererProvider::class);
        $refererProviderMock = $refererReflection->newInstanceWithoutConstructor();

        $this->urlGeneratorMock = $this->createMock(UrlGenerator::class);
        $this->pageViewTrackerMock = $this->createMock(PageViewTracker::class);

        // 2. Обходим final у EventDispatcher через Reflection
        $dispatcherReflection = new \ReflectionClass(EventDispatcher::class);
        $dispatcherMock = $dispatcherReflection->newInstanceWithoutConstructor();

        $loggerMock = $this->createMock(LoggerInterface::class);

        // 3. Вламываемся в readonly-фабрику PostDtoFactory
        $reflectionClass = new \ReflectionClass(PostDtoFactory::class);
        $postDtoFactoryMock = $reflectionClass->newInstanceWithoutConstructor();

        if ($reflectionClass->hasProperty('imageService')) {
            $property = $reflectionClass->getProperty('imageService');
            $property->setAccessible(true);
            $imageServiceType = $property->getType()?->getName() ?? 'stdClass';
            $property->setValue($postDtoFactoryMock, $this->createMock($imageServiceType));
        }

        // Собираем хендлер
        $this->handler = new PostShowHandler(
            $this->postRepositoryMock,
            $categoryRepositoryMock,
            $refererProviderMock,
            $this->urlGeneratorMock,
            $this->pageViewTrackerMock,
            $dispatcherMock,
            $postDtoFactoryMock,
            $loggerMock
        );
    }

    public function testGetPostShowDataSuccessWithSimilarPosts(): void
    {
        $postId = 'post-uuid-111';

        // Имитируем ответ БД со всеми вариациями ключей для просмотров и даты
        $rawPost = [
            'id'           => $postId,
            'title'        => 'Новый хит от бэкендера',
            'slug'         => 'novyj-hit-ot-bekendera',
            'description'  => 'Описание поста',
            'content'      => 'Контент',
            'category_ids' => ['cat-1'],
            'views_count'  => 10,
            'views'        => 10,
            'createdAt'    => '2026-05-25 12:00:00', // Добавили дату для DateTimeParserTrait
            'created_at'   => '2026-05-25 12:00:00',
            'author'       => 'Админ',
            'author_id'    => '1',
        ];

        $this->postRepositoryMock->expects($this->once())
            ->method('findPostById')
            ->with($postId)
            ->willReturn($rawPost);

        // Фейковые похожие посты из репозитория
        $rawSimilarPosts = [
            [
                'id'           => $postId,
                'title'        => 'Тот же пост (должен отфильтроваться)',
                'slug'         => 'slug-1',
                'description'  => 'desc',
                'category_ids' => ['cat-1'],
                'views_count'  => 5,
                'views'        => 5,
                'createdAt'    => '2026-05-25 12:00:00', // Добавили сюда
                'created_at'   => '2026-05-25 12:00:00',
                'author'       => 'Админ',
                'author_id'    => '1',
            ],
            [
                'id'           => 'post-uuid-222',
                'title'        => 'Похожий пост №1',
                'slug'         => 'slug-2',
                'description'  => 'desc',
                'category_ids' => ['cat-1'],
                'views_count'  => 15,
                'views'        => 15,
                'createdAt'    => '2026-05-25 12:00:00', // И сюда
                'created_at'   => '2026-05-25 12:00:00',
                'author'       => 'Админ',
                'author_id'    => '1',
            ],
        ];

        $this->postRepositoryMock->method('findRelatedPostsByCategories')
            ->willReturn($rawSimilarPosts);

        // Трекер просмотров
        $this->pageViewTrackerMock->method('trackCurrentPage')->willReturn(true);
        $this->postRepositoryMock->expects($this->once())->method('incrementViewsCount')->with($postId);

        // Роутер
        $this->urlGeneratorMock->method('generate')->willReturn('/mock-url');

        // Запуск
        $result = $this->handler->getPostShowData($postId, 3);

        // Проверки структуры
        $this->assertInstanceOf(PostShowDto::class, $result);
    }

    public function testGetPostShowDataThrowsExceptionIfPostNotFound(): void
    {
        $postId = 'missing-id';

        $this->postRepositoryMock->method('findPostById')->with($postId)->willReturn(null);

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage("Пост с ID 'missing-id' не найден.");

        $this->handler->getPostShowData($postId);
    }
}
