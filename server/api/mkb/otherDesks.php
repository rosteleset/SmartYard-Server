<?php

    /**
     * @api {get} /api/mkb/otherDesks/:login get desks
     *
     * @apiVersion 1.0.0
     *
     * @apiName getOtherDesks
     * @apiGroup mkb
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {String} login
     *
     * @apiSuccess {Object[]} desks
     */

    /**
     * mkb api
     */

    namespace api\mkb {

        use api\api;

        /**
         * mkb method
         */

        class otherDesks extends api {

            public static function GET($params) {
                $mkb = loadBackend("mkb");

                $desks = false;

                if ($mkb) {
                    $desks = $mkb->getDesks(@$params["_id"]);
                }

                return api::ANSWER($desks, $desks ? "desks" : false);
            }

            public static function index() {
                $mkb = loadBackend("mkb");

                if ($mkb) {
                    return [
                        "GET",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
