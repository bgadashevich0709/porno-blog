<?php

namespace App\Repository;

use App\Entity\Post;
use Doctrine\DBAL\ArrayParameterType;
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

    /**
     * @throws Exception
     */
    public function findLatestPostsForCategories(array $categoryIds, int $limit, int $offset = 0): array
    {
        $sql = "
            WITH RankedPosts AS (
                SELECT 
                    p.id, 
                    p.title, 
                    p.description, 
                    p.image, 
                    p.views,
                    p.createdAt, -- Добавили в CTE
                    pc.category_id,
                    (
                        SELECT GROUP_CONCAT(sub_pc.category_id) 
                        FROM post_category sub_pc 
                        WHERE sub_pc.post_id = p.id
                    ) as category_ids,
                    ROW_NUMBER() OVER(PARTITION BY pc.category_id ORDER BY p.createdAt DESC) as rn
                FROM posts p
                INNER JOIN post_category pc ON p.id = pc.post_id
                WHERE pc.category_id IN (:categoryIds)
            )
            SELECT id, title, description, image, views, category_ids, createdAt -- Выбираем для DTO
            FROM RankedPosts 
            WHERE rn > :offset AND rn <= (:offset + :limit)
        ";

        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative($sql, [
            'categoryIds' => $categoryIds,
            'offset' => $offset,
            'limit' => $limit,
        ], [
            'categoryIds' => ArrayParameterType::STRING,
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

    /**
     * Запрос А: Выбирает ТОЛЬКО ID постов (для Late Row Lookup пагинации).
     */
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

    /**
     * НОВЫЙ МЕТОД: Быстро считает количество постов без тяжелых сортировок ORDER BY.
     */
    public function getCountQueryBuilder(string $categoryId): QueryBuilder
    {
        return $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->select('COUNT(*)')
            ->from('posts', 'p')
            ->innerJoin('p', 'post_category', 'pc', 'pc.post_id = p.id')
            ->where('pc.category_id = :category_id')
            ->setParameter('category_id', $categoryId);
    }

    /**
     * Запрос Б: Вытаскивает полные данные по нужным ID, исключая тяжелое поле content.
     */
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
