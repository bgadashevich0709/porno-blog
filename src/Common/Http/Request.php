<?php

namespace App\Common\Http;

readonly class Request
{
    public function __construct(
        public string $uri,
        public string $method,
        public array  $query,   // Аналог $_GET
        public array  $request, // Аналог $_POST
        public array  $server   // Аналог $_SERVER
    ) {}

    public static function createFromGlobals(): self
    {
        return new self(
            uri: $_SERVER['REQUEST_URI'] ?? '/',
            method: strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            query: $_GET,
            request: $_POST,
            server: $_SERVER
        );
    }

    public function getPathInfo(): string
    {
        return parse_url($this->uri, PHP_URL_PATH) ?? '/';
    }
}
