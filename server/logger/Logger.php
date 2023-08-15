<?php

namespace logger;

class Logger
{
    private static array $channels = [];

    const EMERGENCY = 'EMERGENCY';
    const ALERT = 'ALERT';
    const CRITICAL = 'CRITICAL';
    const ERROR = 'ERROR';
    const WARNING = 'WARNING';
    const NOTICE = 'NOTICE';
    const INFO = 'INFO';
    const DEBUG = 'DEBUG';

    private string $file;
    private string $tag;

    public function __construct(string $file, string $tag)
    {
        $this->file = $file;
        $this->tag = $tag;

        if (!file_exists($this->getFile()))
            touch($this->getFile());
    }

    public function emergency(string $message, array $context = []): void
    {
        $this->log(self::EMERGENCY, $message, $context);
    }

    public function alert(string $message, array $context = []): void
    {
        $this->log(self::ALERT, $message, $context);
    }

    public function critical(string $message, array $context = []): void
    {
        $this->log(self::CRITICAL, $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log(self::ERROR, $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log(self::WARNING, $message, $context);
    }

    public function notice(string $message, array $context = []): void
    {
        $this->log(self::NOTICE, $message, $context);
    }

    public function info(string $message, array $context = []): void
    {
        $this->log(self::INFO, $message, $context);
    }

    public function debug(string $message, array $context = []): void
    {
        $this->log(self::DEBUG, $message, $context);
    }

    public function log(string $level, string $message, array $context = []): void
    {
        file_put_contents($this->getFile(), '[' . date('Y-m-d H:i:s') . '] ' . $this->tag . '.' . $level . ': ' . $message . ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
    }

    private function getFile(): string
    {
        return __DIR__ . '/../logs/' . $this->file . '-' . date('Y-m-d') . '.log';
    }

    public static function channel(string $file = 'main', string $tag = 'application'): static
    {
        if (!array_key_exists($file . $tag, self::$channels))
            self::$channels[$file . $tag] = new static($file, $tag);

        return self::$channels[$file . $tag];
    }

    public static function channels(array $files, string $tag = 'application'): static
    {
        return new GroupLogger(array_map(static fn ($file) => self::channel($file, $tag), $files));
    }
}
