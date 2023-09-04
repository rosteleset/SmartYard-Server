<?php

namespace Selpol\Controller;

use Selpol\Http\Response;
use Selpol\Http\ServerRequest;
use Selpol\Router\RouterMatch;
use Selpol\Service\HttpService;

class Controller
{
    protected ServerRequest $request;

    public function __construct(ServerRequest $request)
    {
        $this->request = $request;
    }

    protected function getRoute(): RouterMatch
    {
        return $this->request->getAttribute('route');
    }

    protected function response(int $code = 200): Response
    {
        /** @var HttpService $http */
        $http = $this->request->getAttribute('http');

        return $http->createResponse($code);
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