<?php

namespace App\Common\Security;

class JwtService
{
    private string $secretKey = 'BRO_SECRET_KEY_NE_TERYAY_EGO_123!'; // Из .env
    private int $expiration = 3600;

    public function generateToken(array $payload): string
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload['exp'] = time() + $this->expiration;
        $payload['iat'] = time();

        $base64UrlHeader = $this->base64UrlEncode($header);
        $base64UrlPayload = $this->base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, $this->secretKey, true);
        $base64UrlSignature = $this->base64UrlEncode($signature);

        return $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
    }

    public function validateToken(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$header, $payload, $signature] = $parts;

        $validSignature = hash_hmac('sha256', $header . "." . $payload, $this->secretKey, true);
        if (!hash_equals($this->base64UrlEncode($validSignature), $signature)) {
            return null;
        }

        $payloadData = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payload), true), true);

        if (isset($payloadData['exp']) && $payloadData['exp'] < time()) {
            return null;
        }

        return $payloadData;
    }

    private function base64UrlEncode(string $data): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }
}
