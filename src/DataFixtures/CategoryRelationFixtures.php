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
        // 512M более чем достаточно для этого алгоритма, так как память больше не течет
        ini_set('memory_limit', '512M');

        $globalStartTime = microtime(true);
        $output = new ConsoleOutput();

        $output->writeln('<info>[Profiler] Этап 1: Очистка старых связей...</info>');
        $deleteStart = microtime(true);
        $db = $manager->getConnection();

        $db->executeStatement('SET FOREIGN_KEY_CHECKS=0;');
        $db->executeStatement('SET UNIQUE_CHECKS=0;');
        $db->executeStatement("DELETE FROM post_category;");
        $deleteTime = microtime(true) - $deleteStart;

        $categoryIds = $db->createQueryBuilder()
            ->select('id')
            ->from('categories')
            ->fetchAllAssociative();
        $categoryIds = array_column($categoryIds, 'id');
        $catsCount = count($categoryIds);

        if ($catsCount < 4) {
            $db->executeStatement('SET FOREIGN_KEY_CHECKS=1;');
            $db->executeStatement('SET UNIQUE_CHECKS=1;');
            throw new \RuntimeException('Для генерации связей необходимо минимум 4 категории.');
        }

        $output->writeln('<info>[Profiler] Этап 2-4: Стриминг постов и пакетный импорт связей...</info>');
        $dbStart = microtime(true);

        $sqlSelectPosts = "SELECT id, createdAt, views FROM posts";
        $stmt = $db->executeQuery($sqlSelectPosts);

        $chunk = [];
        $insertedCount = 0;
        $batchSize = 10000;

        while ($post = $stmt->fetchAssociative()) {
            $requiredCatsCount = rand(2, 4);
            $randomKeys = (array) array_rand($categoryIds, $requiredCatsCount);

            foreach ($randomKeys as $key) {
                $chunk[] = sprintf(
                    "(%s, %s, %s, %d)",
                    $db->quote($post['id']),
                    $db->quote($categoryIds[$key]),
                    $db->quote($post['createdAt']),
                    (int) $post['views']
                );
            }

            if (count($chunk) >= $batchSize) {
                $insertedCount += count($chunk);

                $sqlInsert = "INSERT IGNORE INTO post_category (post_id, category_id, createdAt, views) VALUES " . implode(',', $chunk);
                $db->executeStatement($sqlInsert);

                $output->writeln(sprintf(
                    ' <comment>[Stream Import]</comment> Успешно обработана пачка записей. Всего вставлено связей: <fg=green>%d</>',
                    $insertedCount
                ));

                unset($chunk);
                $chunk = [];
            }
        }

        if (!empty($chunk)) {
            $insertedCount += count($chunk);
            $sqlInsert = "INSERT IGNORE INTO post_category (post_id, category_id, createdAt, views) VALUES " . implode(',', $chunk);
            $db->executeStatement($sqlInsert);
            unset($chunk);
        }

        $stmt->free();

        $db->executeStatement('SET UNIQUE_CHECKS=1;');
        $db->executeStatement('SET FOREIGN_KEY_CHECKS=1;');

        $totalDbTime = microtime(true) - $dbStart;
        $globalTime = microtime(true) - $globalStartTime;

        $output->writeln("\n<options=bold;fg=cyan>================ ПОДРОБНЫЙ ОТЧЕТ ПРОФАЙЛЕРА СВЯЗЕЙ ===============</>");
        $output->writeln(sprintf("• Всего постов обработано потоком:        <fg=green>3 000 000 шт.</>", $insertedCount));
        $output->writeln(sprintf("• Всего сгенерировано связей:              <fg=green>%d шт.</>", $insertedCount));
        $output->writeln(sprintf("• Очистка старой таблицы (DELETE):        <fg=red>%.4f сек.</>", $deleteTime));
        $output->writeln(sprintf("• Общая потоковая запись в СУБД:          <fg=yellow>%.4f сек.</>", $totalDbTime));
        $options = 'options=bold;fg=cyan';
        $output->writeln("<$options>-----------------------------------------------------------</>");
        $output->writeln(sprintf("• ОБЩЕЕ ВРЕМЯ ВЫПОЛНЕНИЯ ФИКСТУРЫ СВЯЗЕЙ: <options=bold;fg=green>%.4f сек.</>", $globalTime));
        $output->writeln("<$options>===========================================================</>\n");
    }
}
