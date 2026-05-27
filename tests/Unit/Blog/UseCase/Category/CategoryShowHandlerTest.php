<?php

declare(strict_types=1);

namespace Tests\Unit\Blog\UseCase\Category;

use App\Common\Cache\CacheInterface;
use App\Common\Router\UrlGenerator;
use App\Exceptions\ResourceNotFoundException;
use App\Modules\Blog\Application\Enum\CategorySort;
use App\Modules\Blog\Application\Enum\SortWay;
use App\Modules\Blog\Application\Service\PostDtoFactory;
use App\Modules\Blog\Repository\CategoryRepositoryInterface;
use App\Modules\Blog\Repository\PostRepositoryInterface;
use App\Modules\Blog\UseCase\Controller\Category\CategoryShowHandler;
use App\Modules\Blog\UseCase\Controller\Category\Dto\CategoryDataDto;
use App\Modules\Blog\UseCase\Controller\Category\Dto\CategoryRequestDto;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Result;
use PHPUnit\Framework\TestCase;

class CategoryShowHandlerTest extends TestCase
{
    private CategoryRepositoryInterface $categoryRepositoryMock;
    private PostRepositoryInterface $postRepositoryMock;
    private UrlGenerator $urlGeneratorMock;
    private CategoryShowHandler $handler;

    /**
     * @throws \ReflectionException
     */
    protected function setUp(): void
    {
        $this->categoryRepositoryMock = $this->createMock(CategoryRepositoryInterface::class);
        $this->postRepositoryMock = $this->createMock(PostRepositoryInterface::class);

        // 1. Создаем объект readonly-фабрики в обход конструктора через Reflection
        $reflectionClass = new \ReflectionClass(PostDtoFactory::class);
        $postDtoFactoryMock = $reflectionClass->newInstanceWithoutConstructor();

        // 2. Находим свойство $imageService и насильно инициализируем его, чтобы не было ошибки
        if ($reflectionClass->hasProperty('imageService')) {
            $property = $reflectionClass->getProperty('imageService');
            $property->setAccessible(true);

            // Динамически создаем пустышку для любого класса сервиса картинок
            $imageServiceType = $property->getType()?->getName() ?? 'stdClass';
            $imageServiceMock = $this->createMock($imageServiceType);

            $property->setValue($postDtoFactoryMock, $imageServiceMock);
        }

        $this->urlGeneratorMock = $this->createMock(UrlGenerator::class);
        $cacheMock = $this->createMock(CacheInterface::class);

        // Сборка хендлера
        $this->handler = new CategoryShowHandler(
            $this->categoryRepositoryMock,
            $this->postRepositoryMock,
            $postDtoFactoryMock,
            $this->urlGeneratorMock,
            $cacheMock
        );
    }

    public function testGetCategoryShowDataSuccess(): void
    {
        $categoryId = 'test-uuid-123';

        $categoryData = [
            'id'          => $categoryId,
            'title'       => 'PHP и пиво',
            'name'        => 'PHP и пиво',
            'slug'        => 'php-i-pivo',
            'description' => 'Отличная категория для душевных разговоров про бэкенд',
        ];

        $this->categoryRepositoryMock->expects($this->once())
            ->method('getById')
            ->with($categoryId)
            ->willReturn($categoryData);

        $requestDto = new CategoryRequestDto();
        $requestDto->page = 1;
        $requestDto->perPage = 12;
        $requestDto->CategorySort = CategorySort::createdAt;
        $requestDto->sortWay = SortWay::desc;

        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $resultMock = $this->createMock(Result::class);

        $this->postRepositoryMock->method('getCountQueryBuilder')->willReturn($queryBuilderMock);
        $queryBuilderMock->method('executeQuery')->willReturn($resultMock);
        $resultMock->method('fetchOne')->willReturn(0);

        $this->urlGeneratorMock->method('generate')->willReturn('/mock-url');

        $result = $this->handler->getCategoryShowData($categoryId, $requestDto);

        $this->assertInstanceOf(CategoryDataDto::class, $result);
        $this->assertSame('PHP и пиво', $result->category->title);
    }

    public function testGetCategoryShowDataWithPostsSuccess(): void
    {
        $categoryId = 'test-uuid-123';

        $categoryData = [
            'id'          => $categoryId,
            'title'       => 'PHP и пиво',
            'name'        => 'PHP и пиво',
            'slug'        => 'php-i-pivo',
            'description' => 'Отличная категория для душевных разговоров про бэкенд',
        ];
        $this->categoryRepositoryMock->method('getById')->willReturn($categoryData);

        $requestDto = new CategoryRequestDto();
        $requestDto->page = 1;
        $requestDto->perPage = 12;
        $requestDto->CategorySort = CategorySort::createdAt;
        $requestDto->sortWay = SortWay::desc;

        // Мокаем каунт (2 поста в категории)
        $countQbMock = $this->createMock(QueryBuilder::class);
        $countResultMock = $this->createMock(Result::class);
        $this->postRepositoryMock->method('getCountQueryBuilder')->willReturn($countQbMock);
        $countQbMock->method('executeQuery')->willReturn($countResultMock);
        $countResultMock->method('fetchOne')->willReturn(2);

        // Мокаем подзапрос получения ID постов
        $idQbMock = $this->createMock(QueryBuilder::class);
        $idResultMock = $this->createMock(Result::class);
        $this->postRepositoryMock->method('getIdSubQueryBuilder')->willReturn($idQbMock);
        $idQbMock->method('setFirstResult')->willReturn($idQbMock);
        $idQbMock->method('setMaxResults')->willReturn($idQbMock);
        $idQbMock->method('executeQuery')->willReturn($idResultMock);
        $idResultMock->method('fetchFirstColumn')->willReturn(['post-id-1', 'post-id-2']);

        // Мокаем получение полных строк данных для PostListItemDto (ФУЛЛ-ПАК ПОЛЕЙ)
        $dataQbMock = $this->createMock(QueryBuilder::class);
        $dataResultMock = $this->createMock(Result::class);
        $this->postRepositoryMock->method('getPostsByDataQueryBuilder')->willReturn($dataQbMock);
        $dataQbMock->method('setParameter')->willReturn($dataQbMock);
        $dataQbMock->method('where')->willReturn($dataQbMock);
        $dataQbMock->method('orderBy')->willReturn($dataQbMock);
        $dataQbMock->method('executeQuery')->willReturn($dataResultMock);

        $rawPostRows = [
            [
                'id'          => 'post-id-1',
                'title'       => 'Первый пост про PHP',
                'description' => 'Короткое описание первого крутого поста про бэкенд',
                'slug'        => 'pervyj-post-pro-php',
                'content'     => 'Полный контент поста',
                'image'       => 'preview.jpg',
                'preview'     => 'preview.jpg',
                'author'      => 'Админ',
                'author_id'   => '1',
                'views'       => 10,
                'created_at'  => '2026-05-25 12:00:00',
                'createdAt'   => '2026-05-25 12:00:00',
            ],
            [
                'id'          => 'post-id-2',
                'title'       => 'Второй пост про пиво',
                'description' => 'Тут мы подробно разбираем сорта светлого фильтрованного',
                'slug'        => 'vtoroj-post-pro-pivo',
                'content'     => 'Полный контент второго поста',
                'image'       => 'beer.jpg',
                'preview'     => 'beer.jpg',
                'author'      => 'Админ',
                'author_id'   => '1',
                'views'       => 42,
                'created_at'  => '2026-05-25 13:00:00',
                'createdAt'   => '2026-05-25 13:00:00',
            ],
        ];
        $dataResultMock->method('fetchAllAssociative')->willReturn($rawPostRows);

        $this->urlGeneratorMock->method('generate')->willReturn('/mock-url');

        $result = $this->handler->getCategoryShowData($categoryId, $requestDto);

        $this->assertInstanceOf(CategoryDataDto::class, $result);
    }

    public function testGetCategoryShowDataThrowsExceptionIfCategoryNotFound(): void
    {
        $categoryId = 'wrong-id';

        $this->categoryRepositoryMock->method('getById')
            ->with($categoryId)
            ->willReturn(null);

        $requestDto = new CategoryRequestDto();

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('Категория не найдена');

        $this->handler->getCategoryShowData($categoryId, $requestDto);
    }
}
