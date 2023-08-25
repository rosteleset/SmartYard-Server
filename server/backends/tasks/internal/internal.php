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
            return TaskManager::instance()->getQueues();
        }

        /**
         * @inheritDoc
         */
        public function getQueueSize(string $queue): int
        {
            return TaskManager::instance()->worker($queue)->getSize();
        }
    }
}
