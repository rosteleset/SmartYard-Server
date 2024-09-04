<?php

    /**
     * @api {get} /api/notes/notes get notes
     *
     * @apiVersion 1.0.0
     *
     * @apiName notes
     * @apiGroup notes
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiSuccess {Object[]} notes
     */

    /**
     * notes api
     */

    namespace api\notes {

        use api\api;

        /**
         * notes method
         */

        class notes extends api {

            public static function GET($params) {
                $notes = loadBackend("notes");

                if ($notes) {
                    $notes = $notes->getNotes();
                }

                return api::ANSWER($notes, ($notes !== false) ? "notes" : false);
            }

            public static function index() {
                $notes = loadBackend("notes");

                if ($notes) {
                    return [
                        "GET" => "#common",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
