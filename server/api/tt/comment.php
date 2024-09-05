<?php

    /**
     * @api {put} /tt/comment add comment
     *
     * @apiVersion 1.0.0
     *
     * @apiName addComment
     * @apiGroup tt
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiBody {String} issueId
     * @apiBody {String} comment
     * @apiBody {Booleaning} commentPrivate
     * @apiBody {String} type
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * @api {put} /tt/comment modify comment
     *
     * @apiVersion 1.0.0
     *
     * @apiName modifyComment
     * @apiGroup tt
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiBody {String} issueId
     * @apiBody {Number} commentIndex
     * @apiBody {String} comment
     * @apiBody {Boolean} commentPrivate
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * @api {delete} /tt/comment delete comment
     *
     * @apiVersion 1.0.0
     *
     * @apiName modifyComment
     * @apiGroup tt
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiBody {String} issueId
     * @apiBody {Number} commentIndex
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * tt api
     */

    namespace api\tt {

        use api\api;

        /**
         * comment method
         */

        class comment extends api {

            public static function POST($params) {
                $tt = loadBackend("tt");

                if (!$tt) {
                    return API::ERROR(500);
                }

                $success =  $tt->addComment($params["issueId"], $params["comment"], !!@$params["commentPrivate"], @$params["type"]);

                return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
            }

            public static function PUT($params) {
                $tt = loadBackend("tt");

                if (!$tt) {
                    return API::ERROR(500);
                }

                $success =  $tt->modifyComment($params["issueId"], $params["commentIndex"], $params["comment"], !!@$params["commentPrivate"]);

                return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
            }

            public static function DELETE($params) {
                $tt = loadBackend("tt");

                if (!$tt) {
                    return API::ERROR(500);
                }

                $success =  $tt->deleteComment($params["issueId"], $params["commentIndex"]);

                return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [
                        "POST" => "#same(tt,issue,POST)",
                        "PUT" => "#same(tt,issue,PUT)",
                        "DELETE" => "#same(tt,issue,DELETE)",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
