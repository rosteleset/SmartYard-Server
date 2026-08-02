<?php

declare(strict_types=1);

namespace SesameWare\RbtAgent;

use DateTimeImmutable;
use DateTimeZone;

final class JsonLog
{
    public function __construct(private readonly string $path)
    {
    }

    /** @param array<string,mixed> $fields */
    public function write(string $event, array $fields = []): void
    {
        $record = array_merge([
            'timestamp' => (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d\TH:i:s\Z'),
            'event' => $event,
        ], $this->sanitize($fields));
        $directory = dirname($this->path);
        if (!is_dir($directory)) {
            @mkdir($directory, 0750, true);
        }
        @file_put_contents(
            $this->path,
            json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n",
            FILE_APPEND | LOCK_EX,
        );
    }

    /** @return list<array<string,mixed>> */
    public function tail(int $limit = 200): array
    {
        $limit = max(1, min(1000, $limit));
        if (!is_file($this->path) || !is_readable($this->path)) {
            return [];
        }
        $handle = fopen($this->path, 'rb');
        if ($handle === false) {
            return [];
        }
        $lines = [];
        try {
            fseek($handle, 0, SEEK_END);
            $position = ftell($handle);
            $buffer = '';
            while ($position > 0 && count($lines) <= $limit) {
                $read = min(8192, $position);
                $position -= $read;
                fseek($handle, $position);
                $buffer = (string) fread($handle, $read) . $buffer;
                $parts = explode("\n", $buffer);
                $buffer = array_shift($parts) ?? '';
                foreach (array_reverse($parts) as $line) {
                    if ($line !== '') {
                        $lines[] = $line;
                    }
                    if (count($lines) >= $limit) {
                        break 2;
                    }
                }
            }
            if ($position === 0 && $buffer !== '' && count($lines) < $limit) {
                $lines[] = $buffer;
            }
        } finally {
            fclose($handle);
        }
        $result = [];
        foreach (array_reverse(array_slice($lines, 0, $limit)) as $line) {
            $record = json_decode($line, true);
            if (is_array($record)) {
                $result[] = $record;
            }
        }
        return $result;
    }

    /** @param array<string,mixed> $value @return array<string,mixed> */
    private function sanitize(array $value): array
    {
        $result = [];
        foreach ($value as $key => $item) {
            if (preg_match('/(?:token|code|secret|password|private|signature|proof|credential|authorization)/i', (string) $key)) {
                $result[$key] = '<redacted>';
            } elseif (is_array($item)) {
                $result[$key] = $this->sanitize($item);
            } else {
                $result[$key] = $item;
            }
        }
        return $result;
    }
}
