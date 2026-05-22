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

        $output->writeln('<info>[Profiler] Этап 2: Выборка постов для генерации массивов...</info>');
        $posts = $db->createQueryBuilder()
            ->select('id', 'createdAt', 'views')
            ->from('posts')
            ->fetchAllAssociative();

        $output->writeln('<info>[Profiler] Этап 3: Быстрая генерация связей на стороне PHP...</info>');
        $phpStart = microtime(true);

        $values = [];
        foreach ($posts as $post) {
            $requiredCatsCount = rand(2, 4);
            $randomKeys = (array) array_rand($categoryIds, $requiredCatsCount);

            foreach ($randomKeys as $key) {
                $values[] = sprintf(
                    "(%s, %s, %s, %d)",
                    $db->quote($post['id']),
                    $db->quote($categoryIds[$key]),
                    $db->quote($post['createdAt']),
                    (int) $post['views']
                );
            }
        }
        $phpTime = microtime(true) - $phpStart;

        $output->writeln('<info>[Profiler] Этап 4: Пакетная запись готовых строк в СУБД...</info>');
        $dbStart = microtime(true);

        $chunks = array_chunk($values, 5000);
        $totalChunks = count($chunks);
        $insertedCount = 0;

        foreach ($chunks as $index => $chunk) {
            $chunkSize = count($chunk);
            $insertedCount += $chunkSize;
            $currentIteration = $index + 1;

            $output->writeln(sprintf(
                ' <comment>[Bulk Insert]</comment> Итерация <info>%d/%d</info>: добавляем <fg=yellow>%d</> записей (Всего вставлено: <fg=green>%d</>)',
                $currentIteration,
                $totalChunks,
                $chunkSize,
                $insertedCount
            ));

            $sql = "INSERT IGNORE INTO post_category (post_id, category_id, createdAt, views) VALUES " . implode(',', $chunk);
            $db->executeStatement($sql);
        }

        $db->executeStatement('SET UNIQUE_CHECKS=1;');
        $db->executeStatement('SET FOREIGN_KEY_CHECKS=1;');

        $totalDbTime = microtime(true) - $dbStart;
        $globalTime = microtime(true) - $globalStartTime;

        $output->writeln("\n<options=bold;fg=cyan>================ ПОДРОБНЫЙ ОТЧЕТ ПРОФАЙЛЕРА СВЯЗЕЙ ===============</>");
        $output->writeln(sprintf("• Сгенерировано итоговых связей:          <fg=green>%d шт.</>", $insertedCount));
        $output->writeln(sprintf("• Очистка старой таблицы (DELETE):        <fg=red>%.4f сек.</>", $deleteTime));
        $output->writeln(sprintf("• Работа PHP (Подготовка строк):          <fg=yellow>%.4f сек.</>", $phpTime));
        $output->writeln(sprintf("• Сборка и запись в СУБД (Bulk Insert):   <fg=yellow>%.4f сек.</>", $totalDbTime));
        $options = 'options=bold;fg=cyan';
        $output->writeln("<$options>-----------------------------------------------------------</>");
        $output->writeln(sprintf("• ОБЩЕЕ ВРЕМЯ ВЫПОЛНЕНИЯ ФИКСТУРЫ СВЯЗЕЙ: <options=bold;fg=green>%.4f сек.</>", $globalTime));
        $output->writeln("<$options>===========================================================</>\n");
    }
}
