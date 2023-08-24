<?php

namespace tasks;

class IntercomConfigureTask extends Task
{
    public int $id;
    public bool $first;

    public function __construct(int $id, bool $first)
    {
        $this->id = $id;
        $this->first = $first;
    }

    public function onTask()
    {
        require_once __DIR__ . './../utils/autoconfigure_domophone.php';

        autoconfigure_domophone($this->id, $this->first);
    }
}