<?php

/**
 * @api {get} /tasks/queues get queues list
 *
 * @apiVersion 1.0.0
 *
 * @apiName queues
 * @apiGroup tasks
 *
 * @apiHeader {String} authorization authentication token
 *
 * @apiSuccess {String} queues list
 *
 * @apiSuccessExample Success-Response:
 *  HTTP/1.1 200 OK
 *  {
 *      "queues": ["high", "medium", "low", "default"]
 *  }
 *
 * @apiExample {curl} Example usage:
 *  curl -X GET http://127.0.0.1:8000/server/api.php/tasks/queues
 */

/**
 * tasks api
 */

namespace api\tasks {

    use api\api;

    /**
     * queues method
     */
    class queues extends api
    {

        public static function GET($params)
        {
            $tasks = loadBackend('tasks');

            return api::ANSWER($tasks->getQueues());
        }

        public static function index()
        {
            return [
                "GET" => "#common",
            ];
        }
    }
}
