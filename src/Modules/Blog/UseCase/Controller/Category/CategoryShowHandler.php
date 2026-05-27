<?php

declare(strict_types=1);

namespace App\Modules\Blog\UseCase\Controller\Category;

use App\Common\Cache\CacheInterface;
use App\Common\Pagination\AbstractIdBasedPaginatedHandler;
use App\Common\Pagination\Dto\PaginationContext;
use App\Common\Pagination\PaginationRequestInterface;
use App\Common\Router\UrlGenerator;
use App\Exceptions\ResourceNotFoundException;
use App\Modules\Blog\Application\Dto\BreadcrumbItemDto;
use App\Modules\Blog\Application\Dto\CategoryDto;
use App\Modules\Blog\Application\Dto\LimitControlDto;
use App\Modules\Blog\Application\Dto\SortPanelDto;
use App\Modules\Blog\Application\Enum\CategorySort;
use App\Modules\Blog\Application\Enum\SortWay;
use App\Modules\Blog\Application\Service\PostDtoFactory;
use App\Modules\Blog\Controller\CategoryController;
use App\Modules\Blog\Controller\IndexController;
use App\Modules\Blog\Repository\CategoryRepositoryInterface;
use App\Modules\Blog\Repository\PostRepositoryInterface;
use App\Modules\Blog\Traits\PostMapper;
use App\Modules\Blog\UseCase\Controller\Category\Dto\CategoryDataDto;
use App\Modules\Blog\UseCase\Controller\Category\Dto\CategoryRequestDto;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;

class CategoryShowHandler extends AbstractIdBasedPaginatedHandler
{
    use PostMapper;

    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly PostRepositoryInterface     $postRepository,
        protected readonly PostDtoFactory            $postDtoFactory,
        UrlGenerator                                 $urlGenerator,
        CacheInterface                               $cache
    ) {
        parent::__construct($urlGenerator, $cache);
    }

    /**
     * @throws ResourceNotFoundException
     */
    public function getCategoryShowData(string $categoryId, CategoryRequestDto $requestDto): CategoryDataDto
    {
        $category = $this->loadCategory($categoryId);
        $paginationResult = $this->getPaginatedPosts($categoryId, $requestDto);

        return $this->buildCategoryDataDto($category, $paginationResult, $requestDto);
    }

    protected function getCacheTags(PaginationContext $context): array
    {
        $categoryId = $context->getParam('categoryId') ?? '';
        return ['posts_list', "category_{$categoryId}_posts"];
    }

    protected function isCacheEnabled(PaginationContext $context): bool
    {
        return true;
    }

    protected function getTotalCount(PaginationContext $context): int
    {
        $categoryId = $context->getParam('categoryId') ?? '';
        $countQb = $this->postRepository->getCountQueryBuilder($categoryId);
        return (int) $countQb->executeQuery()->fetchOne();
    }

    protected function fetchIds(int $offset, int $perPage, PaginationRequestInterface $requestDto, PaginationContext $context): array
    {
        $categoryId = $context->getParam('categoryId') ?? '';
        $idQb = $this->postRepository->getIdSubQueryBuilder(
            categoryId: $categoryId,
            sortField: $requestDto->getSortField(),
            sortWay: $requestDto->getSortWay()
        );

        return $idQb->setFirstResult($offset)->setMaxResults($perPage)->executeQuery()->fetchFirstColumn();
    }

    /**
     * @throws Exception
     */
    protected function fetchFullRowsByIds(array $idList, PaginationContext $context): array
    {
        if (empty($idList)) {
            return [];
        }

        $dataQb = $this->postRepository->getPostsByDataQueryBuilder();
        $placeholders = [];
        $cases = [];

        foreach ($idList as $index => $id) {
            $paramName = 'id_' . $index;
            $placeholders[] = ':' . $paramName;
            $dataQb->setParameter($paramName, $id, ParameterType::STRING);
            $cases[] = "WHEN p.id = :{$paramName} THEN {$index}";
        }

        return $dataQb->where("p.id IN (" . implode(',', $placeholders) . ")")
            ->orderBy("CASE " . implode(' ', $cases) . " END")
            ->executeQuery()
            ->fetchAllAssociative();
    }

    protected function mapRowsToDto(array $rows): array
    {
        return $this->mapPosts($rows);
    }

    /**
     * @throws ResourceNotFoundException
     */
    private function loadCategory(string $categoryId): CategoryDto
    {
        $categoryArr = $this->categoryRepository->getById($categoryId);
        if (!$categoryArr) {
            throw new ResourceNotFoundException('Категория не найдена');
        }
        return CategoryDto::fromArray($categoryArr);
    }

    private function buildBreadcrumbs(string $categoryTitle): array
    {
        return [
            new BreadcrumbItemDto('Главная', $this->urlGenerator->generate(IndexController::class, 'index')),
            new BreadcrumbItemDto($categoryTitle),
        ];
    }

    private function buildSortPanel(CategoryRequestDto $requestDto): SortPanelDto
    {
        return new SortPanelDto(
            sortOptions: CategorySort::labels(),
            wayOptions: SortWay::labels(),
            currentSort: $requestDto->CategorySort->name,
            currentWay: $requestDto->sortWay->name,
            sortKeyName: 'CategorySort'
        );
    }

    private function getPaginatedPosts(string $categoryId, CategoryRequestDto $requestDto): array
    {
        $context = new PaginationContext(
            routeController: CategoryController::class,
            routeMethod: 'show',
            routeParams: ['id' => $categoryId],
            cachePrefix: "category_{$categoryId}",
            cacheTags: ['posts_list', "category_{$categoryId}_posts"],
            cacheTtl: 300,
            additionalParams: ['categoryId' => $categoryId]
        );

        return $this->paginate($requestDto, $context);
    }

    private function buildCategoryDataDto(CategoryDto $category, array $paginationResult, CategoryRequestDto $requestDto): CategoryDataDto
    {
        return new CategoryDataDto(
            category: $category,
            postsData: $paginationResult['postsData'],
            pages: $paginationResult['pages'],
            breadcrumbs: $this->buildBreadcrumbs($category->title),
            sortPanel: $this->buildSortPanel($requestDto),
            limitControl: $this->buildLimitControl($requestDto->perPage)
        );
    }

    private function buildLimitControl(int $perPage): LimitControlDto
    {
        return new LimitControlDto(options: [12, 24, 36], current: $perPage);
    }
}
