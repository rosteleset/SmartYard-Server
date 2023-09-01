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
        if (!isset($this->container)) {
            $this->container = Container::file(path('config/container.php'));
            $this->container->set(Kernel::class, $this);
        }

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

    public function bootstrap(bool $lazy = true): static
    {
        mb_internal_encoding('UTF-8');

        if (!$lazy && !isset($this->container))
            $this->container = Container::file(path('config/container.php'));

        register_shutdown_function([$this, 'shutdown']);

        require_once path('backends/backend.php');

        return $this;
    }

    public function run(): int
    {
        try {
            return $this->runner->__invoke($this);
        } catch (Throwable $throwable) {
            if (isset($this->runner))
                return $this->runner->onFailed($throwable);

            return 1;
        }
    }

    private function shutdown()
    {
        foreach ($this->shutdownCallbacks as $callback)
            $callback($this);

        if (isset($this->container))
            $this->container->dispose();
    }

    public static function instance(): ?Kernel
    {
        return self::$instance;
    }
}