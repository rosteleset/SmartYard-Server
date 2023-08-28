<?php

/**
 * @api {get} /task/status get queues status
 *
 * @apiVersion 1.0.0
 *
 * @apiName status
 * @apiGroup task
 *
 * @apiHeader {String} authorization authentication token
 *
 * @apiSuccess {Array} queues status
 * @apiExample {curl} Example usage:
 *  curl -X GET http://127.0.0.1:8000/server/api.php/tasks/status
 */

/**
 * task api
 */

namespace api\tasks {

    use api\api;

    /**
     * queues method
     */
    class status extends api
    {
        public static function GET($params)
        {
            $tasks = loadBackend('task');

            return api::ANSWER($tasks->getStatus());
        }

        public static function index()
        {
            return [
                "GET" => "#common",
            ];
        }
    }
}
