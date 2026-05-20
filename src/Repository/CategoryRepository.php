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

    public function findNonEmptyCategories(): array
    {
        $sql = "
            SELECT c.id, c.name 
            FROM categories c
            WHERE EXISTS (
                SELECT 1 
                FROM post_category pc 
                WHERE pc.category_id = c.id
            )
            ORDER BY c.name ASC
        ";

        return $this->getEntityManager()->getConnection()->fetchAllAssociative($sql);
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
