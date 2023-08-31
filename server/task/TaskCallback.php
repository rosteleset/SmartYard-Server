<?php

namespace Selpol\Task;

interface TaskCallback
{
    public function __invoke(Task $task);
}