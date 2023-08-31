<?php

namespace Selpol\Container;

interface ContainerFactory
{
    function __invoke(Container $container);
}