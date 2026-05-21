<?php

declare(strict_types=1);

namespace App\Common\Debug;

use Doctrine\DBAL\Cache\QueryCacheProfile;
use Doctrine\DBAL\Result;

class ProfilingConnection extends \Doctrine\DBAL\Connection
{
    private \Doctrine\DBAL\Connection $originalConnection;

    public function setOriginalConnection(\Doctrine\DBAL\Connection $connection): void
    {
        $this->originalConnection = $connection;
    }

    public function executeQuery(string $sql, array $params = [], array $types = [], ?QueryCacheProfile $qcp = null): Result
    {
        $start = microtime(true);

        $result = $this->originalConnection->executeQuery($sql, $params, $types, $qcp);

        $durationMs = (microtime(true) - $start) * 1000;
        SqlProfiler::logQuery($sql, $params, $durationMs);

        return $result;
    }

    public function executeStatement(string $sql, array $params = [], array $types = []): int|string
    {
        $start = microtime(true);

        $result = $this->originalConnection->executeStatement($sql, $params, $types);

        $durationMs = (microtime(true) - $start) * 1000;
        SqlProfiler::logQuery($sql, $params, $durationMs);

        return $result;
    }

    public function getDatabasePlatform(): \Doctrine\DBAL\Platforms\AbstractPlatform
    {
        return $this->originalConnection->getDatabasePlatform();
    }

    public function getDriver(): \Doctrine\DBAL\Driver
    {
        return $this->originalConnection->getDriver();
    }

    public function getParams(): array
    {
        return $this->originalConnection->getParams();
    }

    public function getConfiguration(): \Doctrine\DBAL\Configuration
    {
        return $this->originalConnection->getConfiguration();
    }

    public function isConnected(): bool
    {
        return $this->originalConnection->isConnected();
    }

    public function __call(string $method, array $args): mixed
    {
        return $this->originalConnection->{$method}(...$args);
    }
}
