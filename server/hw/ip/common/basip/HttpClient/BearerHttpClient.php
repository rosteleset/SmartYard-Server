<?php

namespace hw\ip\common\basip\HttpClient;

/**
 * HTTP client implementation using Bearer token authentication.
 */
final class BearerHttpClient implements HttpClientInterface
{
    /**
     * Cached bearer token.
     */
    private ?string $bearerToken = null;

    /**
     * @param string $url Base device URL.
     * @param string $password User password.
     * @param string $login Login used for authentication.
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
        if ($this->bearerToken === null) {
            $this->refreshBearerToken();
        }

        $req = $this->url . $this->apiPrefix . $resource;

        $ch = curl_init($req);
        curl_setopt_array($ch, [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPAUTH => CURLAUTH_BEARER,
            CURLOPT_UNRESTRICTED_AUTH => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_XOAUTH2_BEARER => $this->bearerToken,
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

    /**
     * Refreshes bearer token using Basic authentication.
     *
     * @return void
     */
    private function refreshBearerToken(): void
    {
        $basicClient = new BasicHttpClient($this->url, $this->login, $this->password, $this->apiPrefix);

        $params = [
            'username' => $this->login,
            'password' => md5($this->password),
        ];

        $response = $basicClient->call('/v1/login?' . http_build_query($params));
        $this->bearerToken = $response['token'] ?? null;
    }
}
