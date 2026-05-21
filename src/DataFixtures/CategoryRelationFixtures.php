<?php

namespace App\DataFixtures;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Console\Output\ConsoleOutput;

class CategoryRelationFixtures implements FixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        set_time_limit(0);

        $globalStartTime = microtime(true);
        $output = new ConsoleOutput();

        $output->writeln('<info>[Profiler] Этап 1: Очистка старых связей...</info>');
        $db = $manager->getConnection();

        $db->executeStatement('SET FOREIGN_KEY_CHECKS=0;');
        $db->executeStatement("DELETE FROM post_category;");

        $categoryIds = $db->createQueryBuilder()
            ->select('id')
            ->from('categories')
            ->fetchAllAssociative();
        $categoryIds = array_column($categoryIds, 'id');

        if (count($categoryIds) < 4) {
            $db->executeStatement('SET FOREIGN_KEY_CHECKS=1;');
            throw new \RuntimeException('Для генерации связей (от 2 до 4) необходимо минимум 4 категории в таблице.');
        }

        $output->writeln('<info>[Profiler] Этап 2: Мощный импорт связей (от 2 до 4 на пост)...</info>');
        $dbStart = microtime(true);

        $escapedCats = implode(',', array_map([$db, 'quote'], $categoryIds));
        $catsCount = count($categoryIds);

        $sql = "
            INSERT IGNORE INTO post_category (post_id, category_id)
            SELECT post_id, category_id FROM (
                SELECT p.id as post_id, ELT(FLOOR(RAND() * {$catsCount}) + 1, {$escapedCats}) as category_id, 1 as num FROM posts p
                UNION ALL
                SELECT p.id as post_id, ELT(FLOOR(RAND() * {$catsCount}) + 1, {$escapedCats}) as category_id, 2 as num FROM posts p
                UNION ALL
                SELECT p.id as post_id, ELT(FLOOR(RAND() * {$catsCount}) + 1, {$escapedCats}) as category_id, 3 as num FROM posts p
                UNION ALL
                SELECT p.id as post_id, ELT(FLOOR(RAND() * {$catsCount}) + 1, {$escapedCats}) as category_id, 4 as num FROM posts p
            ) as sub_relations
            WHERE num <= FLOOR(2 + RAND() * 3)
        ";

        $db->executeStatement($sql);
        $totalDbTime = microtime(true) - $dbStart;

        $db->executeStatement('SET FOREIGN_KEY_CHECKS=1;');
        $globalTime = microtime(true) - $globalStartTime;

        $output->writeln("\n<options=bold;fg=cyan>================ ПОДРОБНЫЙ ОТЧЕТ ПРОФАЙЛЕРА СВЯЗЕЙ ===============</>");
        $output->writeln(sprintf("• Работа PHP (Подготовка строк):          <fg=yellow>%.4f сек.</>", $globalTime - $totalDbTime));
        $output->writeln(sprintf("• Генерация и запись в СУБД (ELT + RAND): <fg=yellow>%.4f сек.</>", $totalDbTime));
        $options = 'options=bold;fg=cyan';
        $output->writeln("<$options>-----------------------------------------------------------</>");
        $output->writeln(sprintf("• ОБЩЕЕ ВРЕМЯ ВЫПОЛНЕНИЯ ФИКСТУРЫ СВЯЗЕЙ: <options=bold;fg=green>%.4f сек.</>", $globalTime));
        $output->writeln("<$options>===========================================================</>\n");
    }
}
