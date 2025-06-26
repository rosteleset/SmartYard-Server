<?php

    /**
     * @api {get} /api/inbox/message/:subscriberId get messages
     *
     * @apiVersion 1.0.0
     *
     * @apiName getMessages
     * @apiGroup inbox
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {Number} subscriberId
     * @apiQuery {Number} [messageId]
     *
     * @apiSuccess {Object[]} messages
     */

    /**
     * @api {post} /api/inbox/message/:subscriberId send message
     *
     * @apiVersion 1.0.0
     *
     * @apiName sendMessage
     * @apiGroup inbox
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {Number} subscriberId
     * @apiBody {String} title
     * @apiBody {String} body
     * @apiBody {String} action
     *
     * @apiSuccess {Object} sent
     */

    /**
     * inbox api
     */

    namespace api\inbox {

        use api\api;

        /**
         * message method
         */

        class message extends api {

            public static function GET($params) {
                $inbox = loadBackend("inbox");

                if (@$params["messageId"]) {
                    $messages = $inbox->getMessages($params["_id"], "id", $params["messageId"]);
                } else {
                    $messages = $inbox->getMessages($params["_id"], "dates", [ "dateFrom" => 0, "dateTo" => time() + 1 ]);
                }

                return api::ANSWER($messages, ($messages !== false) ? "messages" : "notAcceptable");
            }

            public static function POST($params) {
                $inbox = loadBackend("inbox");

                $success = $inbox->sendMessage($params["_id"], $params["title"], $params["body"], $params["action"]);

                return api::ANSWER($success, ($success !== false) ? "sent" : "notAcceptable");
            }

            public static function index() {
                return [
                    "GET" => "#same(addresses,house,GET)",
                    "POST" => "#same(addresses,house,PUT)",
                ];
            }
        }
    }
