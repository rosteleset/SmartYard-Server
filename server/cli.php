<?php

use Selpol\Kernel\Kernel;
use Selpol\Kernel\Runner\CliRunner;

require_once dirname(__FILE__) . '/vendor/autoload.php';

chdir(path(''));

$kernel = new Kernel();

exit($kernel->setRunner(new CliRunner($argv))->bootstrap()->run());