<?php

namespace Selpol\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Selpol\Service\HttpService;

class InternalMiddleware implements MiddlewareInterface
{
    private array $trust;

    public function __construct()
    {
        $this->trust = config('internal.trust') ?? ['127.0.0.1/32'];
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $ip = $request->getHeader('X-Real-Ip');

        if (count($ip) === 0) {
            /** @var HttpService $http */
            $http = $request->getAttribute('http');

            return $http->createResponse(404)->withJson(['success' => false]);
        }

        foreach ($this->trust as $item)
            if ($this->ipInRange($ip[0], $item))
                return $handler->handle($request);

        /** @var HttpService $http */
        $http = $request->getAttribute('http');

        return $http->createResponse(404)->withJson(['success' => false]);
    }

    private function ipInRange(string $ip, string $range): bool
    {
        if (!strpos($range, '/'))
            $range .= '/32';

        list($range, $netmask) = explode('/', $range, 2);

        $ip_decimal = ip2long($ip);
        $range_decimal = ip2long($range);
        $netmask_decimal = ~(pow(2, (32 - $netmask)) - 1);

        return (($ip_decimal & $netmask_decimal) == ($range_decimal & $netmask_decimal));
    }
}