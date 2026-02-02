<?php

namespace hw\ip\common\basip\HttpClient;

interface HttpClientInterface
{
    public function call(
        string $resource,
        string $method = 'GET',
        array  $payload = [],
        int    $timeout = 0,
    ): array|string;
}
