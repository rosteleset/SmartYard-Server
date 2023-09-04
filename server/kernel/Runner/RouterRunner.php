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
use Selpol\Http\ServerRequest;
use Selpol\Kernel\Kernel;
use Selpol\Kernel\KernelRunner;
use Selpol\Router\Router;
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

        $route = $this->router->match($request);

        if ($route !== null) {
            $this->middlewares = $this->router->getMiddlewares() + $route['middlewares'];

            $kernel->getContainer()->set(ServerRequest::class, $request);

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
            $route = $request->getAttribute('route');

            /** @var HttpService $http */
            $http = $request->getAttribute('http');

            if ($route === null)
                return $http->createResponse(404)->withJson(['success' => false]);

            if ($route['method'] === 'file') {
                if (!file_exists($route['class']))
                    return $http->createResponse(404)->withJson(['success' => false]);

                return require_once $route['class'];
            } else if (!class_exists($route['class']))
                return $http->createResponse(404)->withJson(['success' => false]);

            /** @var Container $container */
            $container = $request->getAttribute('container');

            $instance = $container->has($route['class']) ? $container->get($route['class']) : $container->make($route['class']);

            return $instance->{$route['method']}($request);
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
            $response = container(HttpService::class)->createResponse(500)->withJson(['success' => false]);

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

        if ($response->getStatusCode() != 204)
            echo $response->getBody()->getContents();

        return 0;
    }
}