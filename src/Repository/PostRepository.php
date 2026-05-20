<?php

namespace App\Repository;

use App\Entity\Post;
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

    public function findLatestPostsWithCategories(int $globalLimit = 300): array
    {
        $db = $this->getEntityManager()->getConnection();

        $sql = "
        SELECT p.id, p.title, p.description, p.image, p.views, p.createdAt
        FROM posts p
        INNER JOIN (
            SELECT id 
            FROM posts 
            ORDER BY createdAt DESC, id DESC 
            LIMIT {$globalLimit}
        ) as fast_ids ON p.id = fast_ids.id
        ORDER BY p.createdAt DESC, p.id DESC
        ";

        $rawRows = $db->fetchAllAssociative($sql);

        if (empty($rawRows)) {
            return [];
        }

        $postIds = array_column($rawRows, 'id');
        $escapedPostIds = implode(',', array_map([$db, 'quote'], $postIds));

        $relationsSql = "SELECT post_id, category_id FROM post_category WHERE post_id IN ({$escapedPostIds})";
        $relations = $db->fetchAllAssociative($relationsSql);

        $categoriesMap = [];
        foreach ($relations as $rel) {
            $categoriesMap[$rel['post_id']][] = $rel['category_id'];
        }

        return array_map(function (array $row) use ($categoriesMap) {
            $row['category_ids'] = $categoriesMap[$row['id']] ?? [];
            return $row;
        }, $rawRows);
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
        $safeLimit = (int) $limit;

        $sqlIds = "
            SELECT DISTINCT pc.post_id
            FROM post_category pc
            WHERE pc.category_id IN (:categoryIds)
            LIMIT {$safeLimit}
        ";

        $targetRows = $connection->fetchAllAssociative($sqlIds, [
            'categoryIds' => $categoryIds,
        ], [
            'categoryIds' => \Doctrine\DBAL\ArrayParameterType::STRING,
        ]);

        if (empty($targetRows)) {
            return [];
        }

        $postIds = array_column($targetRows, 'post_id');

        $finalSql = "
            SELECT 
                p.id, 
                p.title, 
                p.description, 
                p.image, 
                p.views,
                p.createdAt,
                GROUP_CONCAT(pc.category_id) as category_ids
            FROM posts p
            INNER JOIN post_category pc ON p.id = pc.post_id
            WHERE p.id IN (:postIds)
            GROUP BY p.id, p.title, p.description, p.image, p.views, p.createdAt
        ";

        $rows = $connection->fetchAllAssociative($finalSql, [
            'postIds' => $postIds,
        ], [
            'postIds' => \Doctrine\DBAL\ArrayParameterType::STRING,
        ]);

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
            ->select('p.id')
            ->from('posts', 'p')
            ->innerJoin('p', 'post_category', 'pc', 'pc.post_id = p.id')
            ->where('pc.category_id = :category_id')
            ->orderBy('p.' . $sortField, $sortWay)
            ->setParameter('category_id', $categoryId);
    }

    public function getCountQueryBuilder(string $categoryId): QueryBuilder
    {
        return $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('COUNT(*)')
            ->from('posts', 'p')
            ->innerJoin('p', 'post_category', 'pc', 'pc.post_id = p.id')
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
        $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->update('posts')
            ->set('views', 'views + 1')
            ->where('id = :id')
            ->setParameter('id', $id)
            ->executeStatement();
    }
}
