<?php

namespace Selpol\Logger;

abstract class Logger
{
    private static array $channels = [];

    const LEVELS = ['DEBUG' => 0, 'INFO' => 1, 'NOTICE' => 2, 'WARNING' => 3, 'ERROR' => 4, 'CRITICAL' => 5, 'ALERT' => 6, 'EMERGENCY' => 7];

    const EMERGENCY = 'EMERGENCY';
    const ALERT = 'ALERT';
    const CRITICAL = 'CRITICAL';
    const ERROR = 'ERROR';
    const WARNING = 'WARNING';
    const NOTICE = 'NOTICE';
    const INFO = 'INFO';
    const DEBUG = 'DEBUG';

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

    public abstract function log(string $level, string $message, array $context = []): void;

    public static function channel(string $file = 'main'): Logger
    {
        if (!array_key_exists($file, self::$channels))
            self::$channels[$file] = new SingleLogger($file);

        return self::$channels[$file];
    }

    /**
     * @param string[] $files
     * @return Logger
     */
    public static function channels(array $files): Logger
    {
        return new MultipleLogger(array_map(static fn($file) => self::channel($file), $files));
    }
}