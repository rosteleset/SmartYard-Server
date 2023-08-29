<?php

namespace Selpol\Logger;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Stringable;

class FileLogger implements LoggerInterface
{
    use LoggerTrait;

    /** @var FileLogger[] $loggers */
    private static array $loggers = [];

    private string $file;

    public function __construct(string $file)
    {
        $this->file = $file;
    }

    public function log($level, Stringable|string $message, array $context = []): void
    {
        file_put_contents($this->getFile(), '[' . date('Y-m-d H:i:s') . '] ' . $level . ': ' . $message . ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
    }

    private function getFile(): string
    {
        return __DIR__ . '/../logs/' . $this->file . '-' . date('Y-m-d') . '.log';
    }

    public static function channel(string $channel): FileLogger
    {
        if (!array_key_exists($channel, self::$loggers))
            self::$loggers[$channel] = new FileLogger($channel);

        return self::$loggers[$channel];
    }
}