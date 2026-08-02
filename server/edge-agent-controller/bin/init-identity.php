<?php

declare(strict_types=1);

use SesameWare\RbtAgent\ControllerIdentity;

require_once dirname(__DIR__) . '/bootstrap.php';

$identity = ControllerIdentity::loadOrCreate(rbtAgentIdentityPath());
fwrite(STDOUT, json_encode($identity->publicData(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
