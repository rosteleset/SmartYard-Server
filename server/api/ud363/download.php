<?php

    /**
     * @api {get} /api/ud363/download get file download link
     *
     * @apiVersion 1.0.0
     *
     * @apiName getDownloadLink
     * @apiGroup ud363
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiQuery {query} file search query
     *
     * @apiSuccess {Object} operationResult
     */

    /**
     * ud363 api
     */

    namespace api\ud363 {

        use api\api;

        /**
         * file method
         */

        class file extends api {

            public static function GET($params) {
                return true;
            }

            public static function index() {
                if (loadBackend("ud363")) {
                    return [
                        "GET" => "#common",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
