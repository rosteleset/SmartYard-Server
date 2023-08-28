<?php

namespace Selpol\Task\Tasks;

use Selpol\Task\Task;

class WaitTask extends Task
{
    private int $wait;

    public function __construct(int $wait)
    {
        parent::__construct('Wait (' . $wait . ')');

        $this->wait = $wait;
    }

    public function onTask()
    {
        for ($i = 0; $i < $this->wait; $i++) {
            sleep(1);

            $this->setProgress($i / $this->wait * 100);
        }
    }
}