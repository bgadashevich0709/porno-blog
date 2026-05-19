<?php

namespace App\Common\Http;

class IpDetector
{
    /**
     * Безопасно определяет реальный IP-адрес пользователя,
     * учитывая возможные прокси-серверы (Nginx, Cloudflare и др.)
     */
    public function detect(): string
    {
        // 1. Проверяем стандартный заголовок обратного прокси
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Заголовок может содержать цепочку IP через запятую. Берем самый первый (клиентский).
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $clientIp = trim($ips[0]);

            if ($this->isValidIp($clientIp)) {
                return $clientIp;
            }
        }

        // 2. Проверяем заголовок HTTP_CLIENT_IP (используется некоторыми провайдерами)
        if (!empty($_SERVER['HTTP_CLIENT_IP']) && $this->isValidIp($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        }

        // 3. Дефолтный адрес, если прокси-серверы не использовались
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }

    /**
     * Валидирует корректность IP-адреса (поддерживает IPv4 и IPv6)
     */
    private function isValidIp(string $ip): bool
    {
        return (bool) filter_var($ip, FILTER_VALIDATE_IP);
    }
}
