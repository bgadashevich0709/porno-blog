<?php

namespace App\Modules\Blog\Repository;

use App\Modules\Blog\Entity\Post;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

/**
 * @extends EntityRepository<Post>
 */
class PostRepository extends EntityRepository implements PostRepositoryInterface
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, $em->getClassMetadata(Post::class));
    }

    public function findLatestPostsForCategoryExcluding(string $categoryId, array $excludedIds, int $limit = 3): array
    {
        $connection = $this->getEntityManager()->getConnection();

        $qb = $connection->createQueryBuilder()
            ->select('p.*')
            ->from('post_category', 'pc')
            ->innerJoin('pc', 'posts', 'p', 'p.id = pc.post_id')
            ->where('pc.category_id = :category_id')
            ->orderBy('pc.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setParameter('category_id', $categoryId);

        // Если какие-то посты уже выведены в категориях выше, база мгновенно отсечет их по индексу
        if (!empty($excludedIds)) {
            $qb->andWhere('pc.post_id NOT IN (:excluded_ids)')
               ->setParameter('excluded_ids', $excludedIds, \Doctrine\DBAL\ArrayParameterType::STRING);
        }

        return $qb->fetchAllAssociative();
    }



    /**
     * @throws Exception
     */
    public function findRelatedPostsByCategories(array $categoryIds, int $limit): array
    {
        if (empty($categoryIds)) {
            return [];
        }

        $connection = $this->getEntityManager()->getConnection();

        $qbIds = $connection->createQueryBuilder();
        $qbIds->select('pc.post_id')
            ->from('post_category', 'pc')
            ->where('pc.category_id IN (:categoryIds)')
            ->groupBy('pc.post_id')
            ->orderBy('pc.post_id', 'DESC')
            ->setMaxResults($limit); // Безопасный лимит через параметры СУБД

        $targetRows = $connection->fetchAllAssociative(
            $qbIds->getSQL(),
            ['categoryIds' => $categoryIds],
            ['categoryIds' => \Doctrine\DBAL\ArrayParameterType::STRING]
        );

        if (empty($targetRows)) {
            return [];
        }

        $postIds = array_column($targetRows, 'post_id');

        $qbFinal = $connection->createQueryBuilder();
        $qbFinal->select(
            'p.id',
            'p.title',
            'p.description',
            'p.image',
            'p.views',
            'p.createdAt',
            'GROUP_CONCAT(pc.category_id) as category_ids'
        )
            ->from('posts', 'p')
            ->innerJoin('p', 'post_category', 'pc', 'p.id = pc.post_id')
            ->where('p.id IN (:postIds)')
            ->groupBy('p.id', 'p.title', 'p.description', 'p.image', 'p.views', 'p.createdAt')
            ->orderBy('p.createdAt', 'DESC');

        $rows = $connection->fetchAllAssociative(
            $qbFinal->getSQL(),
            ['postIds' => $postIds],
            ['postIds' => \Doctrine\DBAL\ArrayParameterType::STRING]
        );

        return array_map(function (array $row) {
            $row['category_ids'] = !empty($row['category_ids'])
                ? explode(',', $row['category_ids'])
                : [];

            return $row;
        }, $rows);
    }

    /**
     * @throws Exception
     */
    public function findPostById(string $id): ?array
    {
        $sql = "
            SELECT p.*,
                   (
                       SELECT GROUP_CONCAT(sub_pc.category_id) 
                       FROM post_category sub_pc 
                       WHERE sub_pc.post_id = p.id
                   ) as category_ids
            FROM posts p
            WHERE p.id = :id
            LIMIT 1
        ";

        $row = $this->getEntityManager()->getConnection()->fetchAssociative($sql, [
            'id' => $id,
        ]);

        if (!$row) {
            return null;
        }

        $row['category_ids'] = !empty($row['category_ids'])
            ? explode(',', $row['category_ids'])
            : [];

        return $row;
    }

    public function getIdSubQueryBuilder(string $categoryId, string $sortField, string $sortWay): QueryBuilder
    {
        return $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('pc.post_id AS id')
            ->from('post_category', 'pc')
            ->where('pc.category_id = :category_id')
            ->orderBy('pc.' . $sortField, $sortWay) // Сортируем по полю внутри этой же таблицы!
            ->setParameter('category_id', $categoryId);
    }

    public function getCountQueryBuilder(string $categoryId): QueryBuilder
    {
        return $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('COUNT(*)')
            ->from('post_category', 'pc')
            ->where('pc.category_id = :category_id')
            ->setParameter('category_id', $categoryId);
    }

    public function getPostsByDataQueryBuilder(): QueryBuilder
    {
        return $this->getEntityManager()->getConnection()->createQueryBuilder()
            // Перечисляем только необходимые для списков поля, убирая content
            ->select(
                'p.id',
                'p.title',
                'p.image',
                'p.description',
                'p.createdAt',
                'p.views'
            )
            ->from('posts', 'p');
    }

    public function incrementViewsCount(string $id): void
    {
        $connection = $this->getEntityManager()->getConnection();

        $connection->beginTransaction();

        try {
            $connection->executeStatement('
            UPDATE posts 
            SET views = views + 1 
            WHERE id = :id
        ', ['id' => $id]);

            $connection->executeStatement('
            UPDATE post_category 
            SET views = views + 1 
            WHERE post_id = :id
        ', ['id' => $id]);

            $connection->commit();
        } catch (\Throwable $e) {

            $connection->rollBack();

            throw $e;
        }
    }
}
