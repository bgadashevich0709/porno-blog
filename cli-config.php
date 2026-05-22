<?php

declare(strict_types=1);

use App\Common\Config\DbConfig;
use App\Common\Debug\ProfilingConnection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Types\Exception\TypesException;
use Doctrine\DBAL\Types\Type;
use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\Migrations\Configuration\Migration\PhpFile;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Ramsey\Uuid\Doctrine\UuidType;

require_once __DIR__ . '/vendor/autoload.php';

/**
 * @throws \Doctrine\DBAL\Exception
 * @throws TypesException
 */
function getEntityManager(): EntityManager
{
    $paths = [__DIR__ . '/src/Entity'];

    // TODO: Из-за багов автоконфигурации Doctrine ORM при $isDevMode = false (когда APP_DEBUG=false),
    // она принудительно пытается подключиться к локальному Redis по адресу 127.0.0.1:6379, полностью
    // игнорируя переменные окружения REDIS_HOST из Docker-сети. Временно захардкожено true.
    // Для полноценного продакшена нужно переписать инициализацию через ручной вызов класса \Doctrine\ORM\Configuration.
    $isDevMode = true;
    $ormConfig = ORMSetup::createAttributeMetadataConfiguration($paths, $isDevMode);

    if (!Type::hasType('uuid')) {
        Type::addType('uuid', UuidType::class);
    }

    $connectionParams = DbConfig::getConnectionParams();

    $connection = DriverManager::getConnection($connectionParams, $ormConfig);
    $connection->getDatabasePlatform()->registerDoctrineTypeMapping('uuid', 'uuid');

    try {
        // Профайлер нужен только для веб-запросов. В CLI (миграции, фикстуры) отключаем его,
        // чтобы избежать блокировок таблиц и конфликтов с вложенными транзакциями.
        if (PHP_SAPI !== 'cli') {
            $profilingConnection = new ProfilingConnection(
                $connection->getParams(),
                $connection->getDriver(),
                $connection->getConfiguration()
            );

            $profilingConnection->setOriginalConnection($connection);
            $connection = $profilingConnection;
        }
    } catch (\Throwable $e) {
        // Если при инициализации профайлера возникли проблемы, безопасно откатываемся к оригиналу
    }

    return new EntityManager($connection, $ormConfig);
}

$entityManager = getEntityManager();
$migrationConfig = new PhpFile(__DIR__ . '/config/migrations.php');

return DependencyFactory::fromEntityManager($migrationConfig, new ExistingEntityManager($entityManager));
