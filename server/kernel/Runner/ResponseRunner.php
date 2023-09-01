<?php

namespace Selpol\Kernel\Runner;

use Selpol\Kernel\Kernel;
use Selpol\Kernel\KernelRunner;
use Throwable;

class ResponseRunner implements KernelRunner
{
    function __invoke(Kernel $kernel): int
    {
        return 0;
    }

    function onFailed(Throwable $throwable): int
    {
        return 0;
    }
}