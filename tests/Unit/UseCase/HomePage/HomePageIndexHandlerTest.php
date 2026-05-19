<?php

namespace Tests\Unit\UseCase\HomePage;

use App\UseCase\HomePage\HomePageIndexHandler;
use App\Repository\CategoryRepositoryInterface;
use App\Repository\PostRepositoryInterface;
use App\Common\Router\UrlGenerator;
use App\UseCase\HomePage\Dto\HomepageDataDto;
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

    /**
     * Тест критического сценария: дублирующиеся посты триггерят точечный дозапрос с офсетом
     */
    public function testGetHomepageDataTriggersPaginationWhenPostsDuplicate(): void
    {
        $categoriesRaw = [
            ['id' => 1, 'name' => 'Tech'],
            ['id' => 2, 'name' => 'Design']
        ];

        $this->categoryRepository->method('findNonEmptyCategories')
            ->willReturn($categoriesRaw);

        $firstRawPosts = [
            [
                'id' => 100, 'category_ids' => ['1', '2'], 'title' => 'Cross-category Post',
                'description' => 'Desc 100', 'image' => 'img.jpg', 'views' => 10, 'createdAt' => '2026-01-01T10:00:00+00:00',
            ],
            [
                'id' => 101, 'category_ids' => ['1'], 'title' => 'Tech Post 2',
                'description' => 'Desc 101', 'image' => 'img.jpg', 'views' => 10, 'createdAt' => '2026-01-01T10:01:00+00:00',
            ],
            [
                'id' => 102, 'category_ids' => ['1'], 'title' => 'Tech Post 3',
                'description' => 'Desc 102', 'image' => 'img.jpg', 'views' => 10, 'createdAt' => '2026-01-01T10:02:00+00:00',
            ],
            [
                'id' => 103, 'category_ids' => ['2'], 'title' => 'Design Post 2',
                'description' => 'Desc 103', 'image' => 'img.jpg', 'views' => 10, 'createdAt' => '2026-01-01T10:03:00+00:00',
            ],
        ];

        $secondRawPosts = [
            [
                'id' => 200, 'category_ids' => ['2'], 'title' => 'Exclusive Design 3',
                'description' => 'Desc 200', 'image' => 'img.jpg', 'views' => 10, 'createdAt' => '2026-01-01T11:00:00+00:00',
            ],
            [
                'id' => 201, 'category_ids' => ['2'], 'title' => 'Exclusive Design 4',
                'description' => 'Desc 201', 'image' => 'img.jpg', 'views' => 10, 'createdAt' => '2026-01-01T11:01:00+00:00',
            ],
        ];

        // Настраиваем ответы репозитория
        $this->postRepository->method('findLatestPostsForCategories')
            ->willReturnOnConsecutiveCalls($firstRawPosts, $secondRawPosts);

        // ВЫЗЫВАЕМ МЕТОД С ЛИМИТОМ 3
        $result = $this->handler->getHomepageData(3);

        // Проверки результирующего DTO
        $this->assertInstanceOf(HomepageDataDto::class, $result);

        $categories = $result->categories;
        $this->assertCount(2, $categories);

        $this->assertEquals('1', $categories[0]->id);
        $this->assertCount(3, $categories[0]->latestPosts);
        $this->assertEquals('100', $categories[0]->latestPosts[0]->id);
        $this->assertEquals('101', $categories[0]->latestPosts[1]->id);
        $this->assertEquals('102', $categories[0]->latestPosts[2]->id);

        $this->assertEquals('2', $categories[1]->id);
        $this->assertCount(3, $categories[1]->latestPosts);
        $this->assertEquals('103', $categories[1]->latestPosts[0]->id);
        $this->assertEquals('200', $categories[1]->latestPosts[1]->id);
        $this->assertEquals('201', $categories[1]->latestPosts[2]->id);
    }
}
