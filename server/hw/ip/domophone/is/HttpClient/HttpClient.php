<?php

namespace hw\ip\domophone\is\HttpClient;

use JsonException;
use RuntimeException;

/**
 * HTTP client.
 */
class HttpClient
{
    /**
     * @param string $url Base device URL.
     * @param string $password Password for authentication.
     * @param string $login Login for authentication.
     */
    public function __construct(
        private readonly string $url,
        private string          $password,
        private readonly string $login = 'root',
    )
    {
    }

    /**
     * Sends request to an endpoint that does not return JSON.
     *
     * @param string $resource API path relative to the device base URL.
     * @param string $method HTTP method.
     * @param array $payload JSON payload. Empty array means no request body.
     * @param int $timeout Request timeout in seconds.
     * @return string Raw response body.
     */
    public function rawRequest(
        string $resource,
        string $method = 'GET',
        array  $payload = [],
        int    $timeout = 0,
    ): string
    {
        return $this->send($resource, $method, $payload, $timeout);
    }

    /**
     * Sends request to the device API.
     *
     * @param string $resource API path relative to the device base URL.
     * @param string $method HTTP method.
     * @param array $payload JSON payload. Empty array means no request body.
     * @param int $timeout Request timeout in seconds.
     * @return array Decoded JSON response as an associative array.
     * @throws RuntimeException If the request fails or the response is not a JSON object/array.
     */
    public function request(
        string $resource,
        string $method = 'GET',
        array  $payload = [],
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

    /**
     * Updates the password used for subsequent authenticated requests.
     *
     * @param string $password New device password.
     * @return void
     */
    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    private function send(
        string $resource,
        string $method = 'GET',
        array  $payload = [],
        int    $timeout = 0,
    ): string
    {
        $req = $this->url . $resource;

        $ch = curl_init($req);
        curl_setopt_array($ch, [
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_USERPWD => "$this->login:$this->password",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
        ]);

        if ($payload) {
            curl_setopt_array($ch, [
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            ]);
        }

        $res = curl_exec($ch);
        $error = curl_error($ch);
        $statusCode = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);

        if ($res === false) {
            throw new RuntimeException("Request failed: $error");
        }

        if ($statusCode === 401) {
            throw new RuntimeException("Authentication failed for $method $resource (HTTP 401)");
        }

        if ($statusCode === 403) {
            throw new RuntimeException("Access denied for $method $resource (HTTP 403)");
        }

        if ($statusCode >= 400) {
            throw new RuntimeException("Request failed for $method $resource with HTTP $statusCode");
        }

        return $res;
    }
}
