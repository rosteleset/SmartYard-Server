<?php

    /**
     * @api {get} /api/notes/search search for notes
     *
     * @apiVersion 1.0.0
     *
     * @apiName searchNotes
     * @apiGroup notes
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiQuery {String} search
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

        class search extends api {

            public static function GET($params) {
                $notes = loadBackend("notes");

                if ($notes) {
                    $search = $notes->searchNotes($params["search"]);
                }

                return api::ANSWER($notes, ($notes !== false) ? "search" : false);
            }

            public static function index() {
                $notes = loadBackend("notes");

                if ($notes) {
                    return [
                        "GET" => "#same(notes,notes,GET)",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
