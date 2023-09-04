<?php

use Selpol\Http\Response;
use Selpol\Kernel\Kernel;
use Selpol\Kernel\Runner\RouterRunner;
use Selpol\Router\Router;
use Selpol\Service\HttpService;

require_once dirname(__FILE__, 2) . '/vendor/autoload.php';

function response(int $code = 204, mixed $data = null, ?string $name = null, ?string $message = null)
{
    $http = container(HttpService::class);
    $response = $http->createResponse($code);

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

        $response->withJson($body);
    }

    return $response;
}

$kernel = new Kernel();

exit($kernel->setRunner(new RouterRunner(new Router()))->bootstrap()->run());