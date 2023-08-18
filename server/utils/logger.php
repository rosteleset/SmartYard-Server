<?php

abstract class Logger
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

class SingleLogger extends Logger
{
    private string $file;
    private bool $writable = false;

    public function __construct(string $file)
    {
        $this->file = $file;

        try {
            if (file_exists($this->getFile())) {
                if (is_writable($this->getFile()))
                    $this->writable = true;
                else {
                    if (!chown($this->getFile(), get_current_user())) return;
                    if (!chmod($this->getFile(), 0665)) return;
                }
            } else {
                if (!is_dir($this->getDirectory())) {
                    $old = umask(0);

                    if (mkdir($this->getDirectory(), 0665, true)) {
                        umask($old);

                        if (!chown($this->getDirectory(), get_current_user())) return;
                    } else return;
                }

                if (!touch($this->getFile())) return;
                if (!chown($this->getFile(), get_current_user())) return;
                if (!chmod($this->getFile(), 0665)) return;

                $this->writable = is_writable($this->getFile());
            }
        } catch (Exception) {
            $this->writable = false;
        }
    }

    public function log(string $level, string $message, array $context = [], string $tag = 'application'): void
    {
        if ($this->writable)
            file_put_contents($this->getFile(), '[' . date('Y-m-d H:i:s') . '] ' . $tag . '.' . $level . ': ' . $message . ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) . PHP_EOL, FILE_APPEND);
    }

    private function getDirectory(): string
    {
        return __DIR__ . '/../logs/' . $this->file;
    }

    private function getFile(): string
    {
        return __DIR__ . '/../logs/' . $this->file . '/' . $this->file . '-' . date('Y-m-d') . '.log';
    }
}

class MultipleLogger extends Logger
{
    /** @var Logger[] $loggers */
    private array $loggers;

    /**
     * MultipleLogger constructor.
     * @param Logger[] $loggers
     */
    public function __construct(array $loggers)
    {
        $this->loggers = $loggers;
    }

    public function log(string $level, string $message, array $context = []): void
    {
        foreach ($this->loggers as $logger)
            $logger->log($level, $message, $context);
    }
}
