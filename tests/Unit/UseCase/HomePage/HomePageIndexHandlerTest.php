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

        $imageService = $this->createMock(\App\Application\Service\ImageService::class);
        $imageService->method('getUrl')->willReturnCallback(function (?string $imageUrl) {
            return $imageUrl ?? '/images/placeholders/blog-list-default.jpg';
        });
        $postDtoFactory = new \App\Application\Service\PostDtoFactory($imageService);

        $this->handler = new HomePageIndexHandler(
            $this->categoryRepository,
            $this->postRepository,
            $urlGenerator,
            $postDtoFactory
        );
    }

    public function testGetHomepageDataDistributesPostsWithoutDuplicates(): void
    {
        // 1. Мокаем категории
        $categoriesRaw = [
            ['id' => '1', 'name' => 'Tech'],
            ['id' => '2', 'name' => 'Design'],
        ];

        $this->categoryRepository->method('findNonEmptyCategories')
            ->willReturn($categoriesRaw);

        // 2. Мокаем новый метод репозитория findLatestPostsForCategoryExcluding последовательными вызовами (willReturnCallback или willReturnOnConsecutiveCalls)
        // Для первой категории (Tech, id=1) без исключений
        $techPosts = [
            [
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
        ];

        // Для второй категории (Design, id=2) с уже исключенным постом '100'
        $designPosts = [
            [
                'id' => '103', 'category_ids' => ['2'], 'title' => 'Design Post 2',
                'description' => 'Desc 103', 'image' => 'img.jpg', 'views' => 10, 'createdAt' => '2026-01-01 09:59:00',
            ],
            [
                'id' => '104', 'category_ids' => ['2'], 'title' => 'Design Post 3',
                'description' => 'Desc 104', 'image' => 'img.jpg', 'views' => 10, 'createdAt' => '2026-01-01 09:58:00',
            ],
        ];

        // Настраиваем маппинг аргументов нового метода
        $this->postRepository->method('findLatestPostsForCategoryExcluding')
            ->willReturnCallback(function (string $categoryId, array $excludedIds, int $limit) use ($techPosts, $designPosts) {
                if ($categoryId === '1') {
                    return $techPosts;
                }
                if ($categoryId === '2') {
                    // Проверяем, что пост '100' был передан в исключения, чтобы не дублироваться
                    $this->assertContains('100', $excludedIds);
                    return $designPosts;
                }
                return [];
            });

        // 3. Выполняем код обработчика главной страницы
        $result = $this->handler->getHomepageData(3);

        // 4. Проверяем результаты сборки Dto
        $this->assertInstanceOf(HomepageDataDto::class, $result);

        $categories = $result->categories;
        $this->assertCount(2, $categories);

        // Проверка первой категории (Tech)
        $this->assertEquals('1', $categories[0]->id);
        $this->assertCount(3, $categories[0]->latestPosts);
        $this->assertEquals('100', $categories[0]->latestPosts[0]->id);
        $this->assertEquals('101', $categories[0]->latestPosts[1]->id);
        $this->assertEquals('102', $categories[0]->latestPosts[2]->id);

        // Проверка второй категории (Design)
        $this->assertEquals('2', $categories[1]->id);
        $this->assertCount(2, $categories[1]->latestPosts);
        $this->assertEquals('103', $categories[1]->latestPosts[0]->id);
        $this->assertEquals('104', $categories[1]->latestPosts[1]->id);
    }
}
