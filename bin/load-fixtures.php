<?php

use App\DataFixtures\PostFixtures;
use App\DataFixtures\CategoryFixtures;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

require_once dirname(__FILE__,2) . '/cli-config.php';

$entityManager = getEntityManager();

$loader = new Loader();
$loader->addFixture(new CategoryFixtures());
$loader->addFixture(new PostFixtures());

$purger = new ORMPurger();
$executor = new ORMExecutor($entityManager, $purger);

echo "Загрузка фикстур в базу данных...\n";

$executor->execute($loader->getFixtures());

echo "Готово! Тестовые данные успешно загружены.\n";
