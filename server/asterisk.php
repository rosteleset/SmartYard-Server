<?php

use Selpol\Kernel\Kernel;
use Selpol\Kernel\Runner\AsteriskRunner;

require_once dirname(__FILE__) . '/vendor/autoload.php';

$kernel = new Kernel();

exit($kernel->setRunner(new AsteriskRunner())->bootstrap()->run());