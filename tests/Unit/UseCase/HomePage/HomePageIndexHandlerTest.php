<?php

namespace Tests\Unit\UseCase\HomePage;

use App\Common\Router\UrlGenerator;
use App\Repository\CategoryRepositoryInterface;
use App\Repository\PostRepositoryInterface;
use App\UseCase\Controller\HomePage\Dto\HomepageDataDto;
use App\UseCase\Controller\HomePage\Handler\HomePageIndexHandler;
use PHPUnit\Framework\TestCase;

class HomePageIndexHandlerTest extends TestCase
{
    private CategoryRepositoryInterface $categoryRepository;
    private PostRepositoryInterface $postRepository;
    private HomePageIndexHandler $handler;

    protected function setUp(): void
    {
        $this->categoryRepository = $this->createMock(CategoryRepositoryInterface::class);
        $this->postRepository = $this->createMock(PostRepositoryInterface::class);
        $urlGenerator = $this->createMock(UrlGenerator::class);

        $urlGenerator->method($this->anything())->willReturn('/some-url');

        $this->handler = new HomePageIndexHandler(
            $this->categoryRepository,
            $this->postRepository,
            $urlGenerator
        );
    }

    public function testGetHomepageDataDistributesPostsWithoutDuplicates(): void
    {
        // 1. Настраиваем категории
        $categoriesRaw = [
            ['id' => '1', 'name' => 'Tech'],
            ['id' => '2', 'name' => 'Design'],
        ];

        $this->categoryRepository->method('findNonEmptyCategories')
            ->willReturn($categoriesRaw);

        // 2. Имитируем единый пакет данных из БД (отсортированный по дате)
        $mockedLatestPosts = [
            [
                // Пост принадлежит обеим категориям. Должен уйти в Tech (так как она первая в цикле),
                // а в Design продублироваться НЕ должен.
                'id' => '100', 'category_ids' => ['1', '2'], 'title' => 'Cross-category Post',
                'description' => 'Desc 100', 'image' => 'img.jpg', 'views' => 10, 'createdAt' => '2026-01-01 10:03:00',
            ],
            [
                'id' => '101', 'category_ids' => ['1'], 'title' => 'Tech Post 2',
                'description' => 'Desc 101', 'image' => 'img.jpg', 'views' => 10, 'createdAt' => '2026-01-01 10:02:00',
            ],
            [
                'id' => '102', 'category_ids' => ['1'], 'title' => 'Tech Post 3',
                'description' => 'Desc 102', 'image' => 'img.jpg', 'views' => 10, 'createdAt' => '2026-01-01 10:01:00',
            ],
            [
                'id' => '103', 'category_ids' => ['2'], 'title' => 'Design Post 2',
                'description' => 'Desc 103', 'image' => 'img.jpg', 'views' => 10, 'createdAt' => '2026-01-01 09:59:00',
            ],
            [
                'id' => '104', 'category_ids' => ['2'], 'title' => 'Design Post 3',
                'description' => 'Desc 104', 'image' => 'img.jpg', 'views' => 10, 'createdAt' => '2026-01-01 09:58:00',
            ],
        ];

        // Мокаем новый метод репозитория (передаем 300 как дефолтный лимит пула)
        $this->postRepository->method('findLatestPostsWithCategories')
            ->with(300)
            ->willReturn($mockedLatestPosts);

        // 3. Вызываем хэндлер с лимитом 3 поста на категорию
        $result = $this->handler->getHomepageData(3);

        // 4. Проверки результирующего DTO
        $this->assertInstanceOf(HomepageDataDto::class, $result);

        $categories = $result->categories;
        $this->assertCount(2, $categories);

        // Проверяем категорию Tech (ID: 1) — должна забрать посты 100, 101, 102
        $this->assertEquals('1', $categories[0]->id);
        $this->assertCount(3, $categories[0]->latestPosts);
        $this->assertEquals('100', $categories[0]->latestPosts[0]->id);
        $this->assertEquals('101', $categories[0]->latestPosts[1]->id);
        $this->assertEquals('102', $categories[0]->latestPosts[2]->id);

        // Проверяем категорию Design (ID: 2) — пост 100 должен быть исключен (дубликат),
        // поэтому категория должна набрать посты 103 и 104.
        $this->assertEquals('2', $categories[1]->id);
        $this->assertCount(2, $categories[1]->latestPosts); // Всего 2, так как уникальных постов больше не было
        $this->assertEquals('103', $categories[1]->latestPosts[0]->id);
        $this->assertEquals('104', $categories[1]->latestPosts[1]->id);
    }
}
