<?php

declare(strict_types=1);

namespace SesameWare\RbtAgent;

use RuntimeException;

interface GatewayCommandRunnerInterface
{
    public function find(string $command): ?string;

    /** @param list<string> $arguments */
    public function run(string $command, array $arguments = [], string $stdin = ''): string;
}

final class GatewayCommandRunner implements GatewayCommandRunnerInterface
{
    public function find(string $command): ?string
    {
        if (!preg_match('/^[A-Za-z0-9_.-]+$/', $command)) {
            return null;
        }
        $path = trim((string) shell_exec('command -v ' . escapeshellarg($command) . ' 2>/dev/null'));
        return $path !== '' ? $path : null;
    }

    public function run(string $command, array $arguments = [], string $stdin = ''): string
    {
        $path = str_contains($command, '/') ? $command : $this->find($command);
        if (!is_string($path) || $path === '') {
            throw new RuntimeException("required command $command is not available");
        }
        $process = proc_open(
            array_merge([$path], $arguments),
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            null,
            ['LANG' => 'C', 'LC_ALL' => 'C'],
        );
        if (!is_resource($process)) {
            throw new RuntimeException("failed to execute $command");
        }
        fwrite($pipes[0], $stdin);
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $status = proc_close($process);
        if ($status !== 0) {
            $detail = trim((string) $stderr) ?: trim((string) $stdout);
            throw new RuntimeException(sprintf(
                '%s %s exited %d%s',
                basename($path),
                implode(' ', $arguments),
                $status,
                $detail !== '' ? ': ' . $detail : '',
            ));
        }
        return (string) $stdout;
    }
}
