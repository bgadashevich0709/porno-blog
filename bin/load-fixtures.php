<?php

use App\DataFixtures\CategoryFixtures;
use App\DataFixtures\CategoryRelationFixtures;
use App\DataFixtures\PostFixtures;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

require_once dirname(__FILE__, 2) . '/cli-config.php';

$entityManager = getEntityManager();

$loader = new Loader();
$loader->addFixture(new CategoryFixtures());
$loader->addFixture(new PostFixtures());
$loader->addFixture(new CategoryRelationFixtures());

$purger = new ORMPurger();
$executor = new ORMExecutor($entityManager, $purger);

echo "Загрузка фикстур в базу данных...\n";

$executor->execute($loader->getFixtures());

// Флаг true вторым аргументом переключает Doctrine в режим append (дозапись)
//$executor->execute($loader->getFixtures(), true);

echo "Готово! Тестовые данные успешно загружены.\n";
