<?php

/**
 * @api {post} /tasks/size get size on queue
 *
 * @apiVersion 1.0.0
 *
 * @apiName size
 * @apiGroup tasks
 *
 * @apiHeader {String} authorization authentication token
 *
 * @apiSuccess {String} size on queue
 *
 * @apiSuccessExample Success-Response:
 *  HTTP/1.1 200 OK
 *  {
 *      "size": 0
 *  }
 *
 * @apiExample {curl} Example usage:
 *  curl -X POST http://127.0.0.1:8000/server/api.php/tasks/size
 */

/**
 * tasks api
 */

namespace api\tasks {

    use api\api;
    use Rule;

    /**
     * queues method
     */
    class size extends api
    {

        public static function GET($params)
        {
            $tasks = loadBackend('tasks');

            $params = validate($params, ['queue' => [Rule::required(), Rule::in($tasks->getQueues()), Rule::nonNullable()]], 'tasks.size');

            return api::ANSWER($tasks->getQueueSize($params['queue']));
        }

        public static function index()
        {
            return [
                "GET" => "#common",
            ];
        }
    }
}
