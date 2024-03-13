<?php

namespace api\queues;

use api\api;

class queues extends api
{

    public static function GET($params)
    {
        $queue = loadBackend('queue');

        if (!$queue) {
            return api::ERROR();
        }

        $tasks = $queue->getTasks();

        return api::ANSWER($tasks, 'queues');
    }

    public static function index() {
        if (loadBackend("queue")) {
            return [
                "GET",
            ];
        } else {
            return false;
        }
    }
}
