<?php

    /**
     * @api {get} /api/providers/provider get providers
     *
     * @apiVersion 1.0.0
     *
     * @apiName getProviders
     * @apiGroup providers
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiSuccess {Object} providers
     */

    /**
     * @api {put} /api/providers/provider/:providerId modify provider
     *
     * @apiVersion 1.0.0
     *
     * @apiName modifyProvider
     * @apiGroup providers
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {Number} providerId
     * @apiBody {Number} uid
     * @apiBody {String} name
     * @apiBody {String} baseUrl
     * @apiBody {String} logo
     * @apiBody {String} tokenCommon
     * @apiBody {String} tokenSms
     * @apiBody {Boolean} hidden
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * @api {post} /api/providers/provider create provider
     *
     * @apiVersion 1.0.0
     *
     * @apiName createProvider
     * @apiGroup providers
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiBody {Number} uid
     * @apiBody {String} name
     * @apiBody {String} baseUrl
     * @apiBody {String} logo
     * @apiBody {String} tokenCommon
     * @apiBody {String} tokenSms
     * @apiBody {Boolean} hidden
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * @api {delete} /api/providers/provider/:providerId delete provider
     *
     * @apiVersion 1.0.0
     *
     * @apiName deleteProvider
     * @apiGroup providers
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {Number} providerId
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * providers api
     */

    namespace api\providers {

        use api\api;

        /**
         * providers method
         */

        class provider extends api {

            public static function GET($params) {
                $providers = loadBackend("providers");

                $providers = $providers->getProviders();

                return api::ANSWER($providers, ($providers !== false) ? "providers" : "notAcceptable");
            }

            public static function PUT($params) {
                $providers = loadBackend("providers");

                $success = $providers->modifyProvider($params["_id"], @$params["uid"], @$params["name"], @$params["baseUrl"], @$params["logo"], @$params["tokenCommon"], @$params["tokenSms"], @$params["hidden"]);

                return api::ANSWER($success);
            }

            public static function POST($params) {
                $providers = loadBackend("providers");

                $success = $providers->addProvider(@$params["uid"], @$params["name"], @$params["baseUrl"], @$params["logo"], @$params["tokenCommon"], @$params["tokenSms"], @$params["hidden"]);

                return api::ANSWER($success);
            }

            public static function DELETE($params) {
                $providers = loadBackend("providers");

                $success = $providers->deleteProvider($params["_id"]);

                return api::ANSWER($success);
            }

            public static function index() {
                $providers = loadBackend("providers");

                if ($providers) {
                    return [
                        "GET",
                        "PUT",
                        "POST" => "#same(providers,provider,PUT)",
                        "DELETE" => "#same(providers,provider,PUT)",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
