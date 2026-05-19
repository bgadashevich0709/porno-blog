<?php

namespace App\Common\Tracking;

use App\Common\Http\IpDetector;

class PageViewTracker
{
    public function __construct(
        private readonly VisitStorageInterface $storage,
        private readonly IpDetector $ipDetector
    ) {}

    public function trackCurrentPage(): bool
    {
        $currentUrl = $this->getCurrentUrl();
        $userIp = $this->ipDetector->detect();

        if ($this->storage->hasVisit($currentUrl, $userIp)) {
            return false;
        }

        $this->storage->logVisit($currentUrl, $userIp);
        return true;
    }

    private function getCurrentUrl(): string
    {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        return $host . $uri;
    }
}
