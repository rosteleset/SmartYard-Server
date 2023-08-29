<?php

/**
 * @api {post} /tasks/wait wait queue
 *
 * @apiVersion 1.0.0
 *
 * @apiName wait
 * @apiGroup tasks
 *
 * @apiHeader {String} authorization authentication token
 *
 * @apiExample {curl} Example usage:
 *  curl -X POST http://127.0.0.1:8000/server/api.php/tasks/wait
 */

/**
 * tasks api
 */

namespace api\tasks {

    use api\api;
    use Selpol\Task\Tasks\WaitTask;
    use Selpol\Validator\Rule;

    /**
     * queues method
     */
    class wait extends api
    {
        public static function POST($params)
        {
            $validate = validate($params, [
                'queue' => [Rule::required(), Rule::in(['high', 'medium', 'low', 'default']), Rule::nonNullable()],
                'wait' => [Rule::required(), Rule::min(0), Rule::max(100), Rule::nonNullable()]
            ]);

            if ($validate) {
                task(new WaitTask($validate['wait']))->queue($validate['queue'])->dispatch();

                return api::ANSWER();
            }

            return api::ERROR();
        }

        public static function index()
        {
            return [
                "POST" => "#common",
            ];
        }
    }
}