<?php

    /**
     * @api {put} /api/notes/check (un)check note line
     *
     * @apiVersion 1.0.0
     *
     * @apiName check
     * @apiGroup notes
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * notes api
     */

    namespace api\notes {

        use api\api;

        /**
         * check method
         */

        class check extends api {

            public static function PUT($params) {
                $notes = loadBackend("notes");

                if ($notes) {
                    $success = $notes->modifyNote(@$params["_id"], @$params["line"], @$params["checked"]);
                }

                return api::ANSWER($success);
            }

            public static function index() {
                $notes = loadBackend("notes");

                if ($notes) {
                    return [
                        "PUT" => "#common",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
