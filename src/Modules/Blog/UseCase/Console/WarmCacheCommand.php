<?php

declare(strict_types=1);

namespace App\Modules\Blog\UseCase\Console;

use App\Common\Cache\CacheInterface;
use App\Common\Console\Attribute\AsCommand;
use App\Common\Console\CommandInterface;
use App\Common\Console\ConsoleOutput;
use App\Modules\Blog\Application\Enum\CategorySort;
use App\Modules\Blog\Application\Enum\SortWay;
use App\Modules\Blog\Repository\CategoryRepositoryInterface;
use App\Modules\Blog\UseCase\Controller\Category\CategoryShowHandler;
use App\Modules\Blog\UseCase\Controller\Category\Dto\CategoryRequestDto;
use App\Modules\Blog\UseCase\Controller\HomePage\Handler\HomePageIndexHandlerInterface;
use Exception;

#[AsCommand(name: 'cache:warm', description: 'Прогревает кэш приложения для постов и категорий')]
final readonly class WarmCacheCommand implements CommandInterface
{
    public function __construct(
        private CacheInterface                  $cache,
        private HomePageIndexHandlerInterface  $homePageHandler,
        private CategoryRepositoryInterface     $categoryRepository,
        private CategoryShowHandler            $categoryShowHandler
    ) {}

    public function execute(array $arguments): int
    {
        ConsoleOutput::line("Начало прогрева кэша...");

        $this->warmHomePageCache();
        $this->warmCategoriesCache();

        ConsoleOutput::line("Все этапы прогрева успешно завершены!");

        return CommandInterface::SUCCESS;
    }

    private function warmHomePageCache(): void
    {
        ConsoleOutput::line("Прогрев главной страницы... ");
        try {
            $postsLimit = 3;

            $this->cache->invalidateTags(['posts_list', 'categories_list']);

            $this->homePageHandler->getHomepageData($postsLimit);
            ConsoleOutput::line("Готово!");
        } catch (Exception $e) {
            ConsoleOutput::line("Ошибка главной страницы: " . $e->getMessage());
        }
    }

    private function warmCategoriesCache(): void
    {
        ConsoleOutput::line("Получение списка категорий...");
        try {
            $categories = $this->categoryRepository->findNonEmptyCategories();
            ConsoleOutput::line("Найдено активных категорий: " . count($categories));

            foreach ($categories as $categoryData) {
                $categoryId = (string) $categoryData['id'];
                $categoryName = $categoryData['name'] ?? "ID {$categoryId}";

                ConsoleOutput::line("-> Прогрев категории: {$categoryName} (ID: {$categoryId})");

                $categoryTag = "category_{$categoryId}_posts";
                $this->cache->invalidateTags([$categoryTag]);

                for ($page = 1; $page <= 10; $page++) {
                    try {
                        $requestDto = new CategoryRequestDto(
                            CategorySort: CategorySort::views,
                            sortWay: SortWay::desc,
                            page: $page,
                            perPage: 12
                        );

                        $this->categoryShowHandler->getCategoryShowData($categoryId, $requestDto);
                        ConsoleOutput::line("   Страница {$page} успешно прогрета");
                    } catch (Exception $e) {
                        ConsoleOutput::line("   Страница {$page} завершена или недоступна: " . $e->getMessage());
                        if ($page > 1) {
                            break;
                        }
                    }
                }
            }
            ConsoleOutput::line("Прогрев страниц категорий завершен!");
        } catch (Exception $e) {
            ConsoleOutput::line("Ошибка прогрева категорий: " . $e->getMessage());
        }
    }
}
