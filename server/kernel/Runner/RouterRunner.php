<?php

namespace Selpol\Kernel\Runner;

use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Selpol\Container\Container;
use Selpol\Http\HttpException;
use Selpol\Http\ServerRequest;
use Selpol\Kernel\Kernel;
use Selpol\Kernel\KernelRunner;
use Selpol\Router\Router;
use Selpol\Router\RouterMatch;
use Selpol\Service\HttpService;
use Throwable;

class RouterRunner implements KernelRunner, RequestHandlerInterface
{
    private Router $router;

    /** @var string[] $middlewares */
    private array $middlewares = [];

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     * @throws Exception
     */
    function __invoke(Kernel $kernel): int
    {
        $http = $kernel->getContainer()->get(HttpService::class);

        $request = $http->createServerRequest($_SERVER['REQUEST_METHOD'], $_SERVER["REQUEST_URI"], $_SERVER);

        $kernel->getContainer()->set(ServerRequest::class, $request);

        $route = $this->router->match($request);

        if ($route !== null) {
            $this->middlewares = $route->getMiddlewares();

            return $this->emit($this->handle(
                $request
                    ->withAttribute('kernel', $kernel)
                    ->withAttribute('container', $kernel->getContainer())
                    ->withAttribute('http', $http)
                    ->withAttribute('route', $route)
            ));
        }

        return $this->emit($http->createResponse(404)->withJson(['success' => false]));
    }

    /**
     * @throws Exception
     * @throws ContainerExceptionInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (count($this->middlewares) === 0) {
            /** @var HttpService $http */
            $http = $request->getAttribute('http');

            if ($http === null)
                return $http->createResponse(404)->withJson(['success' => false]);

            /** @var RouterMatch $route */
            $route = $request->getAttribute('route');

            if ($route === null)
                return $http->createResponse(404)->withJson(['success' => false]);

            if ($route->getMethod() === 'file') {
                if (!file_exists($route->getClass()))
                    return $http->createResponse(404)->withJson(['success' => false]);

                return require_once $route->getClass();
            } else if (!class_exists($route->getClass())) {
                var_dump($route->getClass());

                return $http->createResponse(404)->withJson(['success' => false]);
            }

            $class = $route->getClass();
            $instance = new $class($request);

            return $instance->{$route->getMethod()}($request);
        }

        /** @var Container $container */
        $container = $request->getAttribute('container');

        /** @var MiddlewareInterface $middleware */
        $middleware = $container->make(array_shift($this->middlewares));

        return $middleware->process($request, $this);
    }

    function onFailed(Throwable $throwable, bool $fatal): int
    {
        logger('response')->error($throwable, ['fatal' => $fatal]);

        try {
            if ($throwable instanceof HttpException)
                $response = container(HttpService::class)
                    ->createResponse($throwable->getCode())
                    ->withJson(['code' => $throwable->getCode(), 'message' => $throwable->getMessage()]);
            else $response = container(HttpService::class)
                ->createResponse(500)
                ->withJson(['code' => 500, 'message' => 'Внутренняя ошибка сервера']);

            return $this->emit($response);
        } catch (Throwable $throwable) {
            logger('response')->critical($throwable);

            return 1;
        }
    }

    private function emit(ResponseInterface $response): int
    {
        header('HTTP/' . $response->getProtocolVersion() . ' ' . $response->getStatusCode() . ' ' . $response->getReasonPhrase());

        foreach ($response->getHeaders() as $name => $values)
            header($name . ': ' . $response->getHeaderLine($name), false);

        if ($response->getStatusCode() != 204) {
            $body = $response->getBody();

            if ($body->getSize() > 1024 * 1024) {
                $begin = 0;
                $size = $body->getSize();
                $end = $size - 1;

                if (isset($_SERVER['HTTP_RANGE'])) {
                    if (preg_match('/bytes=\h*(\d+)-(\d*)[\D.*]?/i', $_SERVER['HTTP_RANGE'], $matches)) {
                        $begin = intval($matches[1]);
                        if (!empty($matches[2]))
                            $end = intval($matches[2]);
                    }

                    header('HTTP/1.1 206 Partial Content');
                    header("Content-Range: bytes $begin-$end/$size");
                } else
                    header('HTTP/1.1 200 OK');

                $new_length = $end - $begin + 1;

                header('Cache-Control: public, must-revalidate, max-age=0');
                header('Pragma: no-cache');
                header('Accept-Ranges: bytes');
                header('Content-Length:' . $new_length);
                header('Content-Transfer-Encoding: binary');

                $chunk_size = 1024 * 1024;
                $bytes_send = 0;

                if (isset($_SERVER['HTTP_RANGE']))
                    $body->seek($begin);

                while (!$body->eof() && !connection_aborted() && ($bytes_send < $new_length)) {
                    $buffer = $body->read($chunk_size);

                    echo $buffer;

                    $bytes_send += strlen($buffer);
                }

            } else echo $body->getContents();

            $body->close();
        }

        return 0;
    }
}