<?php

    /**
     * @api {get} /api/accounts/groups get groups
     *
     * @apiVersion 1.0.0
     *
     * @apiName getGroups
     * @apiGroup groups
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiSuccess {Object[]} groups groups
     */

    /**
     * accounts namespace
     */

    namespace api\accounts {

        use api\api;

        /**
         * groups methods
         */

        class groups extends api {

            public static function GET($params) {
                $groups = loadBackend("groups");

                if ($groups) {
                    $groups = $groups->getGroups(false);
                }

                return api::ANSWER($groups, ($groups !== false)?"groups":"notFound");
            }

            public static function index() {
                if (loadBackend("groups")) {
                    return [
                        "GET" => "#common",
                    ];
                } else {
                    return [ ];
                }
            }
        }
    }
