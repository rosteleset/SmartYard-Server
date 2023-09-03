<?php

namespace Selpol\Kernel;

interface KernelShutdownCallback
{
    public function __invoke(Kernel $kernel): void;
}