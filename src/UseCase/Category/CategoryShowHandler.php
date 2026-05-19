<?php

declare(strict_types=1);

namespace App\UseCase\Category;

use App\Application\Dto\BreadcrumbItemDto;
use App\Application\Dto\CategoryDto;
use App\Application\Dto\LimitControlDto;
use App\Application\Dto\SortPanelDto;
use App\Application\Enum\CategorySort;
use App\Application\Enum\SortWay;
use App\Common\Pagination\AbstractIdBasedPaginatedHandler;
use App\Common\Pagination\PaginationRequestInterface;
use App\Common\Router\UrlGenerator;
use App\Controller\CategoryController;
use App\Controller\IndexController;
use App\Exceptions\ResourceNotFoundException;
use App\Repository\CategoryRepositoryInterface;
use App\Repository\PostRepositoryInterface;
use App\Traits\PostMapper;
use App\UseCase\Category\Dto\CategoryDataDto;
use App\UseCase\Category\Dto\CategoryRequestDto;
use Doctrine\DBAL\ParameterType;
use Smarty\Exception;

class CategoryShowHandler extends AbstractIdBasedPaginatedHandler
{
    use PostMapper;

    public function __construct(
        private readonly CategoryRepositoryInterface $categoryRepository,
        private readonly PostRepositoryInterface     $postRepository,
        protected readonly UrlGenerator              $urlGenerator
    )
    {
    }

    /**
     * @throws ResourceNotFoundException
     * @throws \Doctrine\DBAL\Exception
     */
    public function getCategoryShowData(string $categoryId, CategoryRequestDto $requestDto): CategoryDataDto
    {
        $category = $this->loadCategory($categoryId);
        $paginationResult = $this->paginateCategoryPosts($categoryId, $requestDto);

        return new CategoryDataDto(
            category: $category,
            postsData: $paginationResult['postsData'],
            pager: $paginationResult['pager'],
            breadcrumbs: $this->buildBreadcrumbs($category->title),
            sortPanel: $this->buildSortPanel($requestDto),
            limitControl: $this->buildLimitControl($requestDto->perPage)
        );
    }

    private function paginateCategoryPosts(string $categoryId, CategoryRequestDto $requestDto): array
    {
        return $this->paginate($requestDto, [
            'categoryId' => $categoryId,
            'requestDto' => $requestDto,
            'route_controller' => CategoryController::class,
            'route_method' => 'show',
            'route_params' => ['id' => $categoryId]
        ]);
    }

    private function buildBreadcrumbs(string $categoryTitle): array
    {
        return [
            new BreadcrumbItemDto(
                'Главная',
                $this->urlGenerator->generate(IndexController::class, 'index')
            ),
            new BreadcrumbItemDto($categoryTitle)
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

    private function buildLimitControl(int $perPage): LimitControlDto
    {
        return new LimitControlDto(
            options: [12, 24, 36],
            current: $perPage
        );
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function getTotalCount(array $context): int
    {
        $countQb = $this->postRepository->getCountQueryBuilder($context['categoryId']);

        return (int)$countQb->executeQuery()->fetchOne();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function fetchIds(int $offset, int $perPage, PaginationRequestInterface $requestDto, array $context): array
    {
        $idQb = $this->postRepository->getIdSubQueryBuilder(
            categoryId: $context['categoryId'],
            sortField: $requestDto->getSortField(),
            sortWay: $requestDto->getSortWay()
        );

        return $idQb->setFirstResult($offset)
            ->setMaxResults($perPage)
            ->executeQuery()
            ->fetchFirstColumn();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    public function fetchFullRowsByIds(array $idList, array $context): array
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

    public function mapRowsToDto(array $rows): array
    {
        return $this->mapPosts($rows);
    }

    /**
     * @throws ResourceNotFoundException
     * @throws \Doctrine\DBAL\Exception
     */
    private function loadCategory(string $categoryId): CategoryDto
    {
        $categoryArr = $this->categoryRepository->getById($categoryId);
        if (!$categoryArr) {
            throw new ResourceNotFoundException('Категория не найдена');
        }
        return CategoryDto::fromArray($categoryArr);
    }
}
