<?php

    /**
     * @api {put} /api/notes/reorder reorder notes
     *
     * @apiVersion 1.0.0
     *
     * @apiName moveNote
     * @apiGroup notes
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiBody {Array} newOrder
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * notes api
     */

    namespace api\notes {

        use api\api;

        /**
         * reorder method
         */

        class reorder extends api {

            public static function PUT($params) {
                $notes = loadBackend("notes");

                if ($notes) {
                    $success = $notes->reorder(@$params["newOrder"]);
                }

                return api::ANSWER($success);
            }

            public static function index() {
                $notes = loadBackend("notes");

                if ($notes) {
                    return [
                        "PUT" => "#same(notes,notes,GET)",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
