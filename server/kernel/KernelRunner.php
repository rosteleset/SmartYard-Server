<?php

namespace Selpol\Kernel;

use Exception;
use Throwable;

interface KernelRunner
{
    /**
     * @param Kernel $kernel
     * @return int
     * @throws Exception
     */
    function __invoke(Kernel $kernel): int;

    function onFailed(Throwable $throwable, bool $fatal): int;
}