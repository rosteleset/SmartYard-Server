<?php

namespace logger;

class GroupLogger extends Logger
{
    /** @var Logger[] $loggers */
    private array $loggers;

    /**
     * GroupLogger constructor.
     * @param Logger[] $loggers
     */
    public function __construct(array $loggers)
    {
        parent::__construct();

        $this->loggers = $loggers;
    }

    public function log(string $level, string $message, array $context = []): void
    {
        foreach ($this->loggers as $logger)
            $logger->log($level, $message, $context);
    }
}
