<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../cli-config.php';

use App\Application\Enum\CategorySort;
use App\Application\Enum\SortWay;
use App\Application\Service\PostDtoFactory;
use App\Common\Cache\CacheFactory;
use App\Common\Cache\CacheInterface;
use App\Common\Container\Container;
use App\Common\Router\UrlGenerator;
use App\Repository\CategoryRepositoryInterface;
use App\Repository\PostRepositoryInterface;
use App\UseCase\Controller\Category\CategoryShowHandler;
use App\UseCase\Controller\Category\Dto\CategoryRequestDto;
use App\UseCase\Controller\HomePage\Handler\CachedHomePageIndexHandler;
use App\UseCase\Controller\HomePage\Handler\HomePageIndexHandler;
use App\UseCase\Controller\HomePage\Handler\HomePageIndexHandlerInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;

$container = new Container();

$container->set(CacheInterface::class, static fn() => CacheFactory::create());

$emFactory = static fn() => getEntityManager();
$container->set(EntityManagerInterface::class, $emFactory);
$container->set(EntityManager::class, $emFactory);

$controllersConfiguration = require __DIR__ . '/../config/routes.php';
$router = new \App\Common\Router\Router($container);
$router->registerControllers($controllersConfiguration);
$compiledRoutes = $router->getRoutes();
$container->set(UrlGenerator::class, static fn() => new UrlGenerator($compiledRoutes));

$container->set(HomePageIndexHandlerInterface::class, static function () use ($container) {
    $originalHandler = new HomePageIndexHandler(
        $container->get(CategoryRepositoryInterface::class),
        $container->get(PostRepositoryInterface::class),
        $container->get(UrlGenerator::class),
        $container->get(PostDtoFactory::class)
    );

    return new CachedHomePageIndexHandler(
        $originalHandler,
        $container->get(CacheInterface::class)
    );
});

$container->set(CategoryShowHandler::class, static function () use ($container) {
    return new CategoryShowHandler(
        $container->get(CategoryRepositoryInterface::class),
        $container->get(PostRepositoryInterface::class),
        $container->get(PostDtoFactory::class),
        $container->get(UrlGenerator::class),
        $container->get(CacheInterface::class)
    );
});


function warmHomePageCache(Container $container, CacheInterface $cache): void
{
    echo "Прогрев главной страницы... ";
    try {
        $postsLimit = 3;
        $homeCacheKey = "homepage_data_limit_{$postsLimit}";

        if (method_exists($cache, 'delete')) {
            $cache->delete($homeCacheKey);
        } elseif (method_exists($cache, 'remove')) {
            $cache->remove($homeCacheKey);
        }

        $homePageHandler = $container->get(HomePageIndexHandlerInterface::class);
        $homePageHandler->getHomepageData($postsLimit);
        echo "Готово!\n";
    } catch (\Exception $e) {
        echo "Ошибка главной страницы: " . $e->getMessage() . "\n";
    }
}


function warmCategoriesCache(Container $container, CacheInterface $cache): void
{
    echo "Получение списка категорий...\n";
    try {
        $categoryRepository = $container->get(CategoryRepositoryInterface::class);
        $categoryShowHandler = $container->get(CategoryShowHandler::class);

        $categories = $categoryRepository->findNonEmptyCategories();
        echo "Найдено активных категорий: " . count($categories) . "\n";

        foreach ($categories as $categoryData) {
            $categoryId = (string) $categoryData['id'];
            $categoryName = $categoryData['name'] ?? "ID {$categoryId}";

            echo "-> Прогрев категории: {$categoryName} (ID: {$categoryId})\n";

            $categoryTag = "category_{$categoryId}_posts";
            if (method_exists($cache, 'invalidateTags')) {
                $cache->invalidateTags([$categoryTag]);
            } elseif (method_exists($cache, 'deleteByTags')) {
                $cache->deleteByTags([$categoryTag]);
            }

            for ($page = 1; $page <= 10; $page++) {
                try {
                    $requestDto = new CategoryRequestDto(
                        CategorySort: CategorySort::views,
                        sortWay: SortWay::desc,
                        page: $page,
                        perPage: 12
                    );

                    $categoryShowHandler->getCategoryShowData($categoryId, $requestDto);
                    echo "   Страница {$page} успешно прогрета\n";
                } catch (\Exception $e) {
                    echo "   Страница {$page} завершена или недоступна: " . $e->getMessage() . "\n";
                    if ($page > 1) {
                        break;
                    }
                }
            }
        }
        echo "Прогрев страниц категорий завершен!\n";
    } catch (\Exception $e) {
        echo "Ошибка прогрева категорий: " . $e->getMessage() . "\n";
    }
}

$cache = $container->get(CacheInterface::class);

echo "Начало прогрева кэша...\n";

warmHomePageCache($container, $cache);
warmCategoriesCache($container, $cache);

echo "Все этапы прогрева успешно завершены!\n";
