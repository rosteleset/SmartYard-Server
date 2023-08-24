<?php

/**
 * backends tasks namespace
 */

namespace backends\tasks {

    use tasks\TaskManager;

    class internal extends tasks
    {
        /**
         * @inheritDoc
         */
        public function getQueues(): array
        {
            return $this->config['backends']['tasks']['queues'];
        }

        public function getQueueSize(string $queue): int
        {
            return TaskManager::instance()->worker($queue)->size();
        }
    }
}
