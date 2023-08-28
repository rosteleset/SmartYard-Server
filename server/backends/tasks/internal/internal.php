<?php

/**
 * backends task namespace
 */

namespace backends\tasks {

    use Selpol\Task\TaskManager;

    class internal extends tasks
    {
        /**
         * @inheritDoc
         */
        public function getStatus(): array
        {
            $result = [];

            $queues = TaskManager::instance()->getQueues();

            foreach ($queues as $queue) {
                $worker = TaskManager::instance()->worker($queue);
                $ids = $worker->getIds();

                $result[$queue] = ['worker' => [], 'workers' => $ids, 'size' => $worker->getSize()];

                foreach ($ids as $id)
                    $result[$queue]['worker'][$id] = ['title' => $worker->getTitle($id), 'progress' => $worker->getProgress($id)];
            }

            return $result;
        }
    }
}
