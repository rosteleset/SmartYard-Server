<?php

/**
 * backends tasks namespace
 */

namespace backends\tasks {

    use backends\backend;

    /**
     * base sip class
     */
    abstract class tasks extends backend
    {
        /**
         * Получить список очередей из настроек бэкенда
         * @return string[]
         */
        public abstract function getQueues(): array;

        /**
         * Получить количество задач в очереди
         * @param string $queue
         * @return int
         */
        public abstract function getQueueSize(string $queue): int;
    }
}
