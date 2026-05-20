<?php

declare(strict_types=1);

namespace App\Common\Cache;

use App\Common\Cache\Driver\DatabaseCache;
use App\Common\Cache\Driver\MemcachedCache;
use App\Common\Cache\Driver\RedisCache;
use App\Common\Config\CacheConfig;
use App\Common\Config\RedisConfig;
use InvalidArgumentException;
use Predis\Client;

/**
 * 💡 Информация о реализации:
 * • Рабочий драйвер: Единственной боевой реализацией на данный момент является 'redis'.
 * Она полностью протестирована, использует клиент Predis под капотом и поддерживает атомарные транзакции с тэгированием.
 * • Драйверы-заглушки: Реализации 'memcached' и 'database' сейчас представляют собой пустые классы-заглушки (Stubs).
 * Они возвращают дефолтные значения и не выполняют реальных операций.
 * • Гибкость: Переключение между механизмами кэширования осуществляется одной строкой в конфигурации .env (переменная CACHE_DRIVER).
 */
final class CacheFactory
{
    public static function create(): CacheInterface
    {
        $driver = strtolower(CacheConfig::getDriver());

        return match ($driver) {
            'redis'     => self::createRedis(),
            'memcached' => new MemcachedCache(),
            'database'  => new DatabaseCache(),
            default     => throw new InvalidArgumentException("Unsupported cache driver: {$driver}"),
        };
    }

    private static function createRedis(): CacheInterface
    {
        $params = RedisConfig::getConnectionParams();

        $client = new Client([
            'scheme' => $params['scheme'],
            'host'   => $params['host'],
            'port'   => $params['port'],
        ]);

        return new RedisCache($client, $params['prefix'], $params['tag_prefix']);
    }
}
