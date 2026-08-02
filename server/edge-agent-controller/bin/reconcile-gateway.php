#!/usr/bin/env php
<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

$dryRun = in_array('--dry-run', $argv, true);
$result = rbtAgentGatewayReconciler()->reconcileAll($dryRun);
fwrite(STDOUT, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n");
exit($result['ok'] ? 0 : 1);
