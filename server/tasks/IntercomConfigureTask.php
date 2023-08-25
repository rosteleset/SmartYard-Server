<?php

namespace tasks;

use Throwable;

class IntercomConfigureTask extends Task
{
    public int $id;
    public bool $first;

    public function __construct(int $id, bool $first)
    {
        parent::__construct('Настройка домофона');

        $this->id = $id;
        $this->first = $first;
    }

    public function onTask()
    {
        require_once dirname(__FILE__) . '/../utils/autoconfigure_domophone.php';

        autoconfigure_domophone($this->id, $this->first);
    }

    public function onError(Throwable $throwable)
    {
        task(new IntercomConfigureTask($this->id, $this->first))->low()->delay(600)->dispatch();
    }
}