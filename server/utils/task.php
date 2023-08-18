<?php

function task(Task $task): TaskContainer
{
    return new TaskContainer($task);
}

abstract class Task
{

}

class TaskContainer
{
    private Task $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    public function getTask(): Task
    {
        return $this->task;
    }
}
