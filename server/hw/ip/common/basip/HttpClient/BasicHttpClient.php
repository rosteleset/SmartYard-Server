<?php

namespace hw\ip\common\basip\HttpClient;

/**
 * HTTP client implementation using Basic Authentication.
 */
final class BasicHttpClient implements HttpClientInterface
{
    /**
     * @param string $url Base device URL.
     * @param string $password Password for basic authentication.
     * @param string $login Login for basic authentication.
     * @param string $apiPrefix API prefix path.
     */
    public function __construct(
        private readonly string $url,
        private readonly string $password,
        private readonly string $login = 'admin',
        private readonly string $apiPrefix = '/api',
    )
    {
    }

    public function call(string $resource, string $method = 'GET', array $payload = [], int $timeout = 0): array|string
    {
        $req = $this->url . $this->apiPrefix . $resource;

        $ch = curl_init($req);
        curl_setopt_array($ch, [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_UNRESTRICTED_AUTH => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_USERPWD => "$this->login:$this->password",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
        ]);

        if ($payload) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        }

        $res = curl_exec($ch);
        curl_close($ch);

        return json_decode($res, true) ?? $res;
    }
}
