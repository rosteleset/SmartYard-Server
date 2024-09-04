<?php

    /**
     * @api {put} /api/notes/xyz/:noteId create note
     *
     * @apiVersion 1.0.0
     *
     * @apiName moveNote
     * @apiGroup notes
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {Number} noteId
     * @apiBody {Number} x
     * @apiBody {Number} y
     * @apiBody {Number} z
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * notes api
     */

    namespace api\notes {

        use api\api;

        /**
         * xyz method
         */

        class xyz extends api {

            public static function PUT($params) {
                $notes = loadBackend("notes");

                if ($notes) {
                    $success = $notes->modifyNote(@$params["_id"], @$params["x"], @$params["y"], @$params["z"]);
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
