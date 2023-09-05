<?php

namespace Selpol\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Selpol\Service\HttpService;

class RateLimitMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $ip = $request->getHeader('X-Real-Ip');

        if (count($ip) === 0) {
            /** @var HttpService $http */
            $http = $request->getAttribute('http');

            return $http->createResponse(429)->withJson(['success' => false]);
        }

        return $handler->handle($request);
    }
}