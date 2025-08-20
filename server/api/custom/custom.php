<?php

    /**
     * @api {get} /api/custom/custom custom GET method
     *
     * @apiVersion 1.0.0
     *
     * @apiName getCustom
     * @apiGroup custom
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiSuccess {Object} operationResult
     */

    /**
     * @api {post} /api/custom/custom custom POST method
     *
     * @apiVersion 1.0.0
     *
     * @apiName postCustom
     * @apiGroup custom
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiSuccess {Object} operationResult
     */

    /**
     * @api {put} /api/custom/custom custom PUT method
     *
     * @apiVersion 1.0.0
     *
     * @apiName putCustom
     * @apiGroup custom
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiSuccess {Object} operationResult
     */

    /**
     * @api {delete} /api/custom/custom custom DELETE method
     *
     * @apiVersion 1.0.0
     *
     * @apiName deleteCustom
     * @apiGroup custom
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiSuccess {Object} operationResult
     */

    /**
     * custom api
     */

    namespace api\custom {

        use api\api;

        /**
         * custom method
         */

        class custom extends api {

            public static function GET($params) {
                $custom = loadBackend("custom");

                $answer = false;

                if ($custom) {
                    $answer = $custom->GET($params);
                }

                return api::ANSWER($answer, ($answer !== false) ? "custom" : false);
            }

            public static function POST($params) {
                $custom = loadBackend("custom");

                $answer = false;

                if ($custom) {
                    $answer = $custom->POST($params);
                }

                return api::ANSWER($answer, ($answer !== false) ? "custom" : false);
            }

            public static function PUT($params) {
                $custom = loadBackend("custom");

                $answer = false;

                if ($custom) {
                    $answer = $custom->PUT($params);
                }

                return api::ANSWER($answer, ($answer !== false) ? "custom" : false);
            }

            public static function DELETE($params) {
                $custom = loadBackend("custom");

                $answer = false;

                if ($custom) {
                    $answer = $custom->DELETE($params);
                }

                return api::ANSWER($answer, ($answer !== false) ? "custom" : false);
            }

            public static function index() {
                $custom = loadBackend("custom");

                if ($custom) {
                    return [
                        "GET",
                        "POST",
                        "PUT",
                        "DELETE",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
