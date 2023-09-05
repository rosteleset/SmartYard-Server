<?php

namespace Selpol\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Selpol\Service\AuthService;
use Selpol\Service\HttpService;

class JwtMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $result = container(AuthService::class)->setJwtFromRequest($request);

        if ($result !== null) {
            /** @var HttpService $http */
            $http = $request->getAttribute('http');

            return $http->createResponse(401)->withJson(['code' => 401, 'message' => $result]);
        }

        return $handler->handle($request);
    }
}