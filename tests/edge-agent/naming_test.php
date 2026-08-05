<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
$requiredFiles = [
    'doc/rbt-agent/target-model.ru.md',
    'doc/rbt-agent/http-protocol-v2.ru.md',
    'install/16.rbt-agent.md',
];
$obsoletePaths = [
    'doc/sesame-agent',
    'install/16.sesame-agent.md',
];
$scanPaths = [
    'README.ru.md',
    'doc/README.ru.md',
    'doc/install/README.ru.md',
    'doc/rbt-agent',
    'install/16.rbt-agent.md',
    'install/nginx/rbt-agent-controller.conf',
    'install/systemd/rbt-overlay-gateway.service',
    'install/systemd/rbt-overlay-gateway.timer',
    'install/sysctl/90-rbt-agent-overlay.conf',
    'client/config/config.sample.json5',
    'client/modules/edgeAgents',
    'server/config/config.sample.json5',
    'server/edge-agent-controller',
    'tests/edge-agent',
];
$forbidden = [
    'SesameAgent',
    'sesame-agent',
    'X-Sesame-',
    '/rbt-agent/v1',
    'http-protocol-v1',
    '16.sesame-agent',
    'feature/sesame-agent-overlay',
];
$self = realpath(__FILE__);
$failures = [];

foreach ($requiredFiles as $relativePath) {
    if (!is_file($root . '/' . $relativePath)) {
        $failures[] = "required file is missing: $relativePath";
    }
}
foreach ($obsoletePaths as $relativePath) {
    if (file_exists($root . '/' . $relativePath)) {
        $failures[] = "obsolete path still exists: $relativePath";
    }
}

$checkFile = static function (string $path) use ($root, $self, $forbidden, &$failures): void {
    if (realpath($path) === $self || !is_file($path)) {
        return;
    }
    $contents = file_get_contents($path);
    if (!is_string($contents) || str_contains($contents, "\0")) {
        return;
    }
    $relativePath = ltrim(substr($path, strlen($root)), '/');
    foreach ($forbidden as $needle) {
        if (str_contains($contents, $needle)) {
            $failures[] = "$relativePath contains obsolete identifier $needle";
        }
    }
};

foreach ($scanPaths as $relativePath) {
    $path = $root . '/' . $relativePath;
    if (is_file($path)) {
        $checkFile($path);
        continue;
    }
    if (!is_dir($path)) {
        $failures[] = "scan path is missing: $relativePath";
        continue;
    }
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
    );
    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $checkFile($file->getPathname());
        }
    }
}

$readme = file_get_contents($root . '/README.ru.md');
foreach ([
    'https://gitap.ru/SesameWare/RBTAgent',
    'doc/rbt-agent/http-protocol-v2.ru.md',
    'install/16.rbt-agent.md',
] as $requiredReference) {
    if (!is_string($readme) || !str_contains($readme, $requiredReference)) {
        $failures[] = "README.ru.md is missing reference $requiredReference";
    }
}

if ($failures !== []) {
    fwrite(STDERR, implode("\n", $failures) . "\n");
    exit(1);
}

fwrite(STDOUT, "naming_test: ok\n");
