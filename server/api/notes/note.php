<?php

    /**
     * @api {post} /api/notes/note create note
     *
     * @apiVersion 1.0.0
     *
     * @apiName createNote
     * @apiGroup notes
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiBody {String} subject
     * @apiBody {String} body
     * @apiBody {Boolean} checks
     * @apiBody {String} category
     * @apiBody {Timestamp} remind
     * @apiBody {String} icon
     * @apiBody {String} font
     * @apiBody {String} color
     * @apiBody {Number} x
     * @apiBody {Number} y
     * @apiBody {Number} z
     *
     * @apiSuccess {Object} Note
     */

    /**
     * @api {put} /api/notes/note/:noteId create note
     *
     * @apiVersion 1.0.0
     *
     * @apiName createNote
     * @apiGroup notes
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {Number} noteId
     * @apiBody {String} subject
     * @apiBody {String} body
     * @apiBody {String} category
     * @apiBody {Timestamp} remind
     * @apiBody {String} icon
     * @apiBody {String} font
     * @apiBody {String} color
     * @apiBody {Number} x
     * @apiBody {Number} y
     * @apiBody {Number} z
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * @api {delete} /api/notes/note/:noteId delete note
     *
     * @apiVersion 1.0.0
     *
     * @apiName deleteNote
     * @apiGroup notes
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {Number} noteId
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * notes api
     */

    namespace api\notes {

        use api\api;

        /**
         * note method
         */

        class note extends api {

            public static function POST($params) {
                $notes = loadBackend("notes");

                if ($notes) {
                    $note = $notes->addNote(@$params["subject"], @$params["body"], @$params["checks"], @$params["category"], @$params["remind"], @$params["icon"], @$params["font"], @$params["color"], @$params["x"], @$params["y"], @$params["z"]);
                }

                return api::ANSWER($note, ($note !== false) ? "note" : "error");
            }

            public static function PUT($params) {
                $notes = loadBackend("notes");

                if ($notes) {
                    $success = $notes->modifyNote(@$params["_id"], @$params["subject"], @$params["body"], @$params["category"], @$params["remind"], @$params["icon"], @$params["font"], @$params["color"], @$params["x"], @$params["y"], @$params["z"]);
                }

                return api::ANSWER($success);
            }

            public static function DELETE($params) {
                $notes = loadBackend("notes");

                if ($notes) {
                    $success = $notes->deleteNote(@$params["_id"]);
                }

                return api::ANSWER($success);
            }

            public static function index() {
                $notes = loadBackend("notes");

                if ($notes) {
                    return [
                        "POST" => "#common",
                        "PUT" => "#common",
                        "DELETE" => "#common",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
