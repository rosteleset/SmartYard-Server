<?php

    /**
     * @api {get} /api/mkb/desks get desks
     *
     * @apiVersion 1.0.0
     *
     * @apiName getDesks
     * @apiGroup mkb
     *
     * @apiHeader {String} Authorization authentication token
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

        class desks extends api {

            public static function GET($params) {
                $mkb = loadBackend("mkb");

                if ($mkb) {
                    $desks = $mkb->getDesks();
                }

                return api::ANSWER($mkb, ($mkb !== false) ? "desks" : false);
            }

            public static function index() {
                $mkb = loadBackend("mkb");

                if ($mkb) {
                    return [
                        "GET" => "#same(mkb,cards,GET)",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
