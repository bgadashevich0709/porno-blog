<?php

namespace App\Common\Tracking;

interface VisitStorageInterface
{
    /**
     * Проверяет, зафиксирован ли уже визит с этого IP на данный URL
     */
    public function hasVisit(string $url, string $ip): bool;

    /**
     * Сохраняет информацию о визите IP-адреса на конкретный URL
     */
    public function logVisit(string $url, string $ip): void;
}
