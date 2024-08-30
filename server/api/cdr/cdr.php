<?php

    /**
     * @api {post} /api/cdr/cdr get cdr records
     *
     * @apiVersion 1.0.0
     *
     * @apiName cdr
     * @apiGroup cdr
     *
     * @apiHeader {String} token authentication token
     *
     * @apiBody {String[]} phones
     * @apiBody {Timestamp} [dateFrom]
     * @apiBody {Timestamp} [dateTo]
     *
     * @apiSuccess {Object[]} cdr
     */

    /**
     * cdr namespace
     */

    namespace api\cdr {

        use api\api;

        /**
         * cdr methods
         */

        class cdr extends api {

            public static function POST($params) {
                $cdr = loadBackend("cdr")->getCDR(@$params["phones"], @$params["dateFrom"], @$params["dateTo"]);

                return api::ANSWER($cdr, ($cdr !== false) ? "cdr" : "404");
            }

            public static function index() {
                $cdr = loadBackend("cdr");

                if ($cdr) {
                    return [
                        "POST",
                    ];
                }
            }
        }
    }
