<?php

namespace App\DataFixtures;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Console\Output\ConsoleOutput;

class CategoryRelationFixtures implements FixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $globalStartTime = microtime(true);
        $output = new ConsoleOutput();

        $output->writeln('<info>[Profiler] Этап 1: Очистка старых связей...</info>');
        $db = $manager->getConnection();

        $db->executeStatement('SET FOREIGN_KEY_CHECKS=0;');
        $db->executeStatement("DELETE FROM post_category WHERE post_id LIKE '%';");

        // Получаем ID всех категорий
        $categoryIds = $db->createQueryBuilder()
            ->select('id')
            ->from('categories')
            ->fetchAllAssociative();
        $categoryIds = array_column($categoryIds, 'id');

        if (empty($categoryIds)) {
            $db->executeStatement('SET FOREIGN_KEY_CHECKS=1;');
            throw new \RuntimeException('Отсутствуют категории в базе данных. Сначала запустите фикстуры категорий.');
        }

        $output->writeln('<info>[Profiler] Этаг 2: Мощный импорт связей через ELT(RAND)...</info>');

        $dbStart = microtime(true);

        // Форматируем ID категорий в строку через запятую и экранируем для SQL
        // Пример: 'uuid-1','uuid-2','uuid-3'
        $escapedCats = implode(',', array_map([$db, 'quote'], $categoryIds));
        $catsCount = count($categoryIds);

        // ИДЕАЛЬНЫЙ SQL: Функция ELT берет случайный порядковый номер от 1 до N
        // и мгновенно подставляет готовое значение из списка. Никаких ORDER BY RAND() и сканирования таблиц!
        $sql = "
            INSERT INTO post_category (post_id, category_id)
            SELECT p.id, ELT(FLOOR(RAND() * {$catsCount}) + 1, {$escapedCats})
            FROM posts p
        ";

        $db->executeStatement($sql);
        $totalDbTime = microtime(true) - $dbStart;

        $db->executeStatement('SET FOREIGN_KEY_CHECKS=1;');
        $globalTime = microtime(true) - $globalStartTime;

        // --- ВЫВОД ПОДРОБНОГО ОТЧЕТА ---
        $output->writeln("\n<options=bold;fg=cyan>================ ПОДРОБНЫЙ ОТЧЕТ ПРОФАЙЛЕРА СВЯЗЕЙ ===============</>");
        $output->writeln(sprintf("• Работа PHP (Подготовка строк):          <fg=yellow>%.4f сек.</>", $globalTime - $totalDbTime));
        $output->writeln(sprintf("• Генерация и запись в СУБД (ELT + RAND): <fg=yellow>%.4f сек.</>", $totalDbTime));
        $output->writeln("<options=bold;fg=cyan>-----------------------------------------------------------</>");
        $output->writeln(sprintf("• ОБЩЕЕ ВРЕМЯ ВЫПОЛНЕНИЯ ФИКСТУРЫ СВЯЗЕЙ: <options=bold;fg=green>%.4f сек.</>", $globalTime));
        $output->writeln("<options=bold;fg=cyan>===========================================================</>\n");
    }
}
