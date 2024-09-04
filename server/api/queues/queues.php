<?php

    /**
     * @api {get} /api/queues/queues get queues
     *
     * @apiVersion 1.0.0
     *
     * @apiName queues
     * @apiGroup queues
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiSuccess {Object[]} queues
     */

    /**
     * queues api
     */

    namespace api\queues {

        use api\api;

        /**
         * queues method
         */

         class queues extends api {

            public static function GET($params) {
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
    }
