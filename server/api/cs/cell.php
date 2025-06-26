<?php

    /**
     * @api {get} /api/cs/cell get CS
     *
     * @apiVersion 1.0.0
     *
     * @apiName getCell
     * @apiGroup cs
     *
     * @apiHeader {String} token authentication token
     *
     * @apiQuery {String} [uid]
     * @apiQuery {String} [sheet]
     * @apiQuery {String} [date]
     * @apiQuery {String} [col]
     * @apiQuery {String} [row]
     *
     * @apiSuccess {Object} cs
     */

    /**
     * @api {get} /api/cs/cs update CS
     *
     * @apiVersion 1.0.0
     *
     * @apiName putCell
     * @apiGroup cs
     *
     * @apiHeader {String} token authentication token
     *
     * @apiBody {String="claim,release"} action
     * @apiBody {String} sheet
     * @apiBody {Timestamp} date
     * @apiBody {String} col
     * @apiBody {String} row
     * @apiBody {Number} uid
     * @apiBody {Timestamp} expire
     * @apiBody {String} sid
     * @apiBody {String} step
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

        class cell extends api {

            public static function GET($params) {
                $cs = loadBackend("cs");

                $sheet = false;

                if ($cs) {
                    if (@$params["uid"]) {
                        $success = $cs->getCellByUID($params["uid"]);
                    } else {
                        $success = $cs->getCellByXYZ($params["sheet"], $params["date"], $params["col"], $params["row"]);
                    }
                }

                return api::ANSWER($sheet, ($sheet !== false) ? "sheet" : "notFound");
            }

            public static function PUT($params) {
                $cs = loadBackend("cs");

                $success = false;

                if ($cs && ($params["action"] == "claim" || $params["action"] == "release")) {
                    if (@$params["col"] && @$params["row"]) {
                        $success = $cs->setCell($params["action"], $params["sheet"], $params["date"], $params["col"], $params["row"], $params["uid"], (int)@$params["expire"], @$params["sid"], @$params["step"]);
                    } else {
                        $success = true;
                    }
                }

                return api::ANSWER($success);
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [
                        "GET" => "#same(tt,issue,GET)",
                        "PUT" => "#same(tt,issue,GET)",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
