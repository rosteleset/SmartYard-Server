<?php

namespace hw\ip\domophone\ufanet\HttpClient;

use JsonException;
use RuntimeException;

/**
 * Low-level HTTP client for Ufanet Secret Mole API.
 */
class HttpClient
{
    private readonly string $url;

    public function __construct(
        string                  $url,
        private string          $password,
        private readonly string $login = 'admin',
    )
    {
        $this->url = rtrim($url, '/');
    }

    /**
     * Sends a JSON request and returns decoded response.
     *
     * @param string $resource API path relative to the device base URL.
     * @param string $method HTTP method.
     * @param array|null $payload JSON payload. Null means no request body.
     * @param int $timeout Request timeout in seconds.
     * @return array Decoded JSON response.
     */
    public function request(
        string $resource,
        string $method = 'GET',
        ?array $payload = null,
        int    $timeout = 0,
    ): array
    {
        $res = $this->send($resource, $method, $payload, $timeout);

        if ($res === '') {
            return [];
        }

        try {
            $decoded = json_decode($res, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException("Invalid JSON response for $method $resource", 0, $e);
        }

        if (!is_array($decoded)) {
            throw new RuntimeException("Unexpected non-array JSON response for $method $resource");
        }

        return $decoded;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    private function send(
        string $resource,
        string $method,
        ?array $payload,
        int    $timeout,
    ): string
    {
        if ($method === 'GET' && is_array($payload)) {
            $resource .= '?' . http_build_query($payload);
            $payload = null;
        }

        $ch = curl_init($this->url . $resource);
        curl_setopt_array($ch, [
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_USERPWD => "$this->login:$this->password",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
        ]);

        if ($payload !== null && $method !== 'GET') {
            $body = json_encode($payload, $payload === [] ? JSON_FORCE_OBJECT : JSON_UNESCAPED_UNICODE);

            if ($body === false) {
                throw new RuntimeException("Failed to encode JSON payload for $method $resource");
            }

            curl_setopt_array($ch, [
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
            ]);
        }

        $res = curl_exec($ch);
        $error = curl_error($ch);
        $statusCode = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($res === false) {
            throw new RuntimeException("Request failed: $error");
        }

        if ($statusCode >= 400) {
            throw new RuntimeException("Request failed for $method $resource with HTTP $statusCode");
        }

        return $res;
    }
}
