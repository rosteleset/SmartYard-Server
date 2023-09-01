<?php

use Selpol\Kernel\Kernel;
use Selpol\Kernel\Runner\TaskRunner;

require_once dirname(__FILE__) . '/vendor/autoload.php';

$kernel = new Kernel();

exit($kernel->setRunner(new TaskRunner($argv))->bootstrap()->run());