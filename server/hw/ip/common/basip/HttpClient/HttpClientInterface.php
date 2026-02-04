<?php

namespace hw\ip\common\basip\HttpClient;

/**
 * Common HTTP client interface for BasIP API communication.
 */
interface HttpClientInterface
{
    /**
     * Performs an HTTP request to the given API resource.
     *
     * @param string $resource API endpoint (e.g. '/v1/forward/items').
     * @param string $method HTTP method (GET, POST, PUT, DELETE, etc.).
     * @param array $payload Request data to be sent with the request.
     * @param int $timeout Request timeout in seconds (0 = default).
     *
     * @return array|string Decoded JSON response or raw response body.
     */
    public function call(
        string $resource,
        string $method = 'GET',
        array  $payload = [],
        int    $timeout = 0,
    ): array|string;
}
