<?php

declare(strict_types=1);

namespace App\Common\Log;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

final class FileLogger implements LoggerInterface
{
    use LoggerTrait;

    private string $logDir;

    public function __construct(?string $logDir = null)
    {
        $rootDir = dirname(__DIR__, 3);
        $this->logDir = $logDir ??   $rootDir . '/var/log';
    }

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0o777, true);
        }

        $logType = (strtolower((string) $level) === 'error') ? 'error.log' : 'app.log';
        $logFile = $this->logDir . '/' . $logType;


        $timestamp = new \DateTimeImmutable()->format('Y-m-d H:i:s.u');
        $contextString = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $logLine = sprintf("[%s] [%s]: %s%s\n", $timestamp, strtoupper((string) $level), $message, $contextString);

        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
    }
}
