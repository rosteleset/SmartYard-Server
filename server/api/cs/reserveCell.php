<?php

    /**
     * @api {put} /api/cs/reserveCell reserve or unreserve CS cell
     *
     * @apiVersion 1.0.0
     *
     * @apiName reserveCell
     * @apiGroup cs
     *
     * @apiHeader {String} token authentication token
     *
     * @apiBody {String} sheet
     * @apiBody {Timestamp} date
     * @apiBody {String} col
     * @apiBody {String} row
     * @apiBody {String} uid
     * @apiBody {Timestamp} expire
     * @apiBody {String} sid
     * @apiBody {String} comment
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * @api {delete} /api/cs/reserveCell force unreserve CS cell
     *
     * @apiVersion 1.0.0
     *
     * @apiName releaseCell
     * @apiGroup cs
     *
     * @apiHeader {String} token authentication token
     *
     * @apiBody {String} sheet
     * @apiBody {Timestamp} date
     * @apiBody {String} col
     * @apiBody {String} row
     * @apiBody {String} uid
     * @apiBody {String} sid
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * cs api
     */

    namespace api\cs {

        use api\api;

        /**
         * cs cell
         */

        class reserveCell extends api {

            public static function PUT($params) {
                $cs = loadBackend("cs");

                $success = false;

                if ($cs) {
                    if (@$params["col"] && @$params["row"]) {
                        $success = $cs->setCell("reserve", $params["sheet"], $params["date"], $params["col"], $params["row"], $params["uid"], (int)@$params["expire"], @$params["sid"], 0, @$params["comment"]);
                    }
                }

                return api::ANSWER($success);
            }

            public static function DELETE($params) {
                $cs = loadBackend("cs");

                $success = false;

                if ($cs) {
                    if (@$params["col"] && @$params["row"]) {
                        $success = $cs->setCell("release-force", $params["sheet"], $params["date"], $params["col"], $params["row"], $params["uid"], 0, @$params["sid"]);
                    }
                }

                return api::ANSWER($success);
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [
                        "PUT",
                        "DELETE",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
