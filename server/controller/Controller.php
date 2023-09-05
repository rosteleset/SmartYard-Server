<?php

namespace Selpol\Controller;

use Selpol\Http\Response;
use Selpol\Http\ServerRequest;
use Selpol\Kernel\Kernel;
use Selpol\Router\RouterMatch;
use Selpol\Service\AuthService;
use Selpol\Service\HttpService;

class Controller
{
    protected ServerRequest $request;

    public function __construct(ServerRequest $request)
    {
        $this->request = $request;
    }

    protected function getKernel(): Kernel
    {
        return $this->request->getAttribute('kernel');
    }

    protected function getHttp(): HttpService
    {
        return $this->request->getAttribute('http');
    }

    protected function getRoute(): RouterMatch
    {
        return $this->request->getAttribute('route');
    }

    protected function getJwt(): array
    {
        return container(AuthService::class)->getJwrOrThrow();
    }

    protected function getSubscriber(): array
    {
        return container(AuthService::class)->getSubscriberOrThrow();
    }

    protected function response(int $code = 200): Response
    {
        return $this->getHttp()->createResponse($code);
    }

    protected function rbtResponse(int $code = 200, mixed $data = null, ?string $name = null, ?string $message = null): Response
    {
        if ($code !== 204) {
            $body = ['code' => $code];

            if ($message === null) {
                if ($name)
                    $message = $name;
                else if (array_key_exists($code, Response::$codes))
                    $message = Response::$codes[$code]['message'];
            }

            if ($name === null) {
                if (array_key_exists($code, Response::$codes))
                    $body['name'] = Response::$codes[$code]['name'];
            } else $body['name'] = $name;

            if ($message !== null) $body['message'] = $message;
            if ($data !== null) $body['data'] = $data;

            return $this->response($code)->withJson($body);
        }

        return $this->response($code);
    }
}