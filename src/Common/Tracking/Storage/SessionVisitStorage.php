<?php

namespace App\Common\Tracking\Storage;

use App\Common\Tracking\VisitStorageInterface;

class SessionVisitStorage implements VisitStorageInterface
{
    private const SESSION_KEY = 'tracked_user_visits';

    public function __construct()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function hasVisit(string $url, string $ip): bool
    {
        // Храним данные в структуре: [url][ip] = true
        return isset($_SESSION[self::SESSION_KEY][$url][$ip]);
    }

    public function logVisit(string $url, string $ip): void
    {
        $_SESSION[self::SESSION_KEY][$url][$ip] = true;
    }
}
