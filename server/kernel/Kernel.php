<?php

namespace Selpol\Kernel;

use Selpol\Container\Container;
use Throwable;

class Kernel
{
    private static ?Kernel $instance = null;

    private Container $container;

    private KernelRunner $runner;

    /** @var KernelShutdownCallback[] $shutdownCallbacks */
    private array $shutdownCallbacks = [];

    public function __construct()
    {
        self::$instance = $this;
    }

    public function getContainer(): Container
    {
        if (!isset($this->container))
            $this->container = new Container();

        return $this->container;
    }

    public function setContainer(Container $container): static
    {
        $this->container = $container;

        return $this;
    }

    public function setRunner(KernelRunner $runner): static
    {
        $this->runner = $runner;

        return $this;
    }

    public function addShutdownCallback(KernelShutdownCallback|callable $callback): static
    {
        $this->shutdownCallbacks[] = $callback;

        return $this;
    }

    public function removeShutdownCallback(KernelShutdownCallback|callable $callback): static
    {
        $this->shutdownCallbacks[] = $callback;

        return $this;
    }

    public function bootstrap(): static
    {
        mb_internal_encoding('UTF-8');

        $this->container = new Container();

        require_once path('backends/backend.php');

        register_shutdown_function([$this, 'shutdown']);
        //set_error_handler([$this, 'error']);

        return $this;
    }

    public function run(): int
    {
        try {
            return $this->runner->__invoke($this);
        } catch (Throwable $throwable) {
            if (isset($this->runner))
                return $this->runner->onFailed($throwable, false);

            return 1;
        }
    }

    private function shutdown(): void
    {
        foreach ($this->shutdownCallbacks as $callback)
            $callback($this);

        if (isset($this->container))
            $this->container->dispose();
    }

//    public function error(int $errno, string $errstr, ?string $errfile = null, ?int $errline = null, ?array $errcontext = null): void
//    {
//        logger('kernel')->emergency('Kernel error', ['errno' => $errno, 'errstr' => $errstr, 'errfile' => $errline, 'errline' => $errfile, 'errcontext' => $errcontext]);
//
//        exit($this->runner->onFailed(new RuntimeException(), true));
//    }

    public static function instance(): ?Kernel
    {
        return self::$instance;
    }
}