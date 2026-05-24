<?php

declare(strict_types=1);

namespace App\UseCase\Console;

use App\Common\Console\Attribute\AsCommand;
use App\Common\Console\CommandInterface;
use App\Common\Console\ConsoleOutput;
use App\DataFixtures\CategoryFixtures;
use App\DataFixtures\CategoryRelationFixtures;
use App\DataFixtures\PostFixtures;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

#[AsCommand(name: 'fixtures:load', description: 'Загрузка тестовых данных (фикстур) в базу данных с предварительной очисткой')]
final readonly class LoadFixturesCommand implements CommandInterface
{
    public function execute(array $arguments): int
    {
        $entityManager = getEntityManager();

        $loader = new Loader();
        $loader->addFixture(new CategoryFixtures());
        $loader->addFixture(new PostFixtures());
        $loader->addFixture(new CategoryRelationFixtures());

        $purger = new ORMPurger();
        $executor = new ORMExecutor($entityManager, $purger);

        ConsoleOutput::line("🔄 Загрузка фикстур в базу данных...");

        $executor->execute($loader->getFixtures());

        // Флаг true вторым аргументом переключает Doctrine в режим append (дозапись)
        //$executor->execute($loader->getFixtures(), true);

        ConsoleOutput::line("✅ Готово! Тестовые данные успешно загружены.");

        return CommandInterface::SUCCESS;
    }
}
