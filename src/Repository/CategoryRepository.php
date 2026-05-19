<?php

namespace App\Repository;

use App\Entity\Category;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class CategoryRepository extends EntityRepository implements CategoryRepositoryInterface
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, $em->getClassMetadata(Category::class));
    }

    /**
     * @return array
     * @throws Exception
     */
    public function findNonEmptyCategories(): array
    {
        return  $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('DISTINCT c.id', 'c.name')
            ->from('categories', 'c')
            ->innerJoin('c', 'post_category', 'pc', 'c.id = pc.category_id')
            ->orderBy('c.name', 'ASC')
            ->fetchAllAssociative();
    }

    /**
     * Получает данные категории по её ID в виде массива или null, если она не найдена.
     *
     * @param string $id UUID категории.
     * @return array|null Ассоциативный массив с полями категории или null.
     * @throws Exception
     */
    public function getById(string $id): ?array
    {
        $row = $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('c.*')
            ->from('categories', 'c')
            ->where('c.id = :id')
            ->setParameter('id', $id)
            ->executeQuery()
            ->fetchAssociative();

        return $row ?: null;
    }
}