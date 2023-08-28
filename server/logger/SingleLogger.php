<?php

namespace Selpol\Logger;

class SingleLogger extends Logger
{
    private string $file;

    public function __construct(string $file)
    {
        $this->file = $file;
    }

    public function log(string $level, string $message, array $context = []): void
    {
        file_put_contents($this->getFile(), '[' . date('Y-m-d H:i:s') . '] ' . $level . ': ' . $message . ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
    }

    private function getFile(): string
    {
        return __DIR__ . '/../logs/' . $this->file . '-' . date('Y-m-d') . '.log';
    }
}