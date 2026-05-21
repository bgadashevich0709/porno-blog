<?php

namespace App\DataFixtures;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\Console\Output\ConsoleOutput;

class PostFixtures implements FixtureInterface
{
    private const int TOTAL_POSTS = 500000;
    //    private const int TOTAL_POSTS = 2000;
    private const int BATCH_SIZE = 5000;

    public function load(ObjectManager $manager): void
    {
        $globalStartTime = microtime(true);
        $output = new ConsoleOutput();

        $stage1Start = microtime(true);
        $output->writeln('<info>[Profiler] Этап 1: Генерация пулов данных через Faker...</info>');

        $faker = Factory::create();
        $db = $manager->getConnection();

        $db->executeStatement('SET FOREIGN_KEY_CHECKS=0;');
        $db->executeStatement('DELETE FROM posts;');

        $contentPool = [];
        $descriptionPool = [];
        $imagePool = [];
        $datePool = [];
        $titlePool = [];

        for ($k = 0; $k < 10; $k++) {
            $contentPool[] = $db->quote($faker->text(1500));
            $descriptionPool[] = $db->quote($faker->text(350));
            $imagePool[] = $db->quote($faker->imageUrl(640, 480, 'cats', true, 'Faker'));
            $datePool[] = $db->quote($faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d H:i:s'));
            $titlePool[] = $db->quote($faker->sentence(rand(4, 8)));
        }
        $stage1Time = microtime(true) - $stage1Start;

        $output->writeln(sprintf('<info>[Profiler] Этап 2 и 3: Обработка %d записей пакетами по %d...</info>', self::TOTAL_POSTS, self::BATCH_SIZE));

        $totalPhpTime = 0.0;
        $totalDbTime = 0.0;
        $postsBatch = [];

        for ($i = 1; $i <= self::TOTAL_POSTS; $i++) {
            $phpStart = microtime(true);
            $postsBatch[] = sprintf(
                "(UUID(), %s, %s, %s, %s, %s, %d)",
                $titlePool[rand(0, 9)],
                $imagePool[rand(0, 9)],
                $descriptionPool[rand(0, 9)],
                $contentPool[rand(0, 9)],
                $datePool[rand(0, 9)],
                rand(2, 100)
            );
            $totalPhpTime += (microtime(true) - $phpStart);

            if ($i % self::BATCH_SIZE === 0) {
                $dbStart = microtime(true);
                $db->executeStatement("INSERT INTO posts (id, title, image, description, content, createdAt, views) VALUES " . implode(',', $postsBatch));
                $totalDbTime += (microtime(true) - $dbStart);

                $postsBatch = [];

                $percent = round(($i / self::TOTAL_POSTS) * 100);
                $output->writeln(sprintf(
                    "<comment>-> Импортировано %d из %d постов (%d%%)</comment>",
                    $i,
                    self::TOTAL_POSTS,
                    $percent
                ));
            }
        }

        if (!empty($postsBatch)) {
            $dbStart = microtime(true);
            $db->executeStatement("INSERT INTO posts (id, title, image, description, content, createdAt, views) VALUES " . implode(',', $postsBatch));
            $totalDbTime += (microtime(true) - $dbStart);

            $output->writeln(sprintf(
                "<comment>-> Импортировано %d из %d постов (100%%)</comment>",
                self::TOTAL_POSTS,
                self::TOTAL_POSTS
            ));
        }

        $db->executeStatement('SET FOREIGN_KEY_CHECKS=1;');
        $globalTime = microtime(true) - $globalStartTime;

        $output->writeln("\n<options=bold;fg=cyan>================ ПОДРОБНЫЙ ОТЧЕТ ПРОФАЙЛЕРА ===============</>");
        $output->writeln(sprintf("• Подготовка пулов (Faker + Очистка БД): <fg=yellow>%.4f сек.</>", $stage1Time));
        $output->writeln(sprintf("• Сборка SQL-строк в памяти (Чистый PHP): <fg=yellow>%.4f сек.</>", $totalPhpTime));
        $output->writeln(sprintf("• Запись пакетов на диск (Чистый MySQL):  <fg=yellow>%.4f сек.</>", $totalDbTime));
        $output->writeln("<options=bold;fg=cyan>-----------------------------------------------------------</>");
        $output->writeln(sprintf("• ОБЩЕЕ ВРЕМЯ ВЫПОЛНЕНИЯ ФИКСТУРЫ:      <options=bold;fg=green>%.4f сек.</>", $globalTime));
        $output->writeln("<options=bold;fg=cyan>===========================================================</>\n");
    }
}
