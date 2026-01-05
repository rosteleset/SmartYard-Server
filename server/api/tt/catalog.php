<?php

    /**
     * @api {post} /api/tt/catalog get catalog for creation issue
     *
     * @apiVersion 1.0.0
     *
     * @apiName getCatalogByIssue
     * @apiGroup tt
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiBody {Object} issue
     *
     * @apiSuccess {Object} catalog
     */

    /**
     * tt api
     */

    namespace api\tt {

        use api\api;

        /**
         * catalog method
         */

        class catalog extends api {

            public static function POST($params) {
                $workflow = @$params["issue"]["workflow"];

                if ($workflow) {
                    $w = loadBackend("tt")->loadWorkflow($workflow);

                    if ($w) {
                        $catalog = $w->getWorkflowCatalog(@$params["issue"]);
                        return api::ANSWER($catalog, ($catalog !== false) ? "catalog" : "notAcceptable");
                    }
                }

                return api::ERROR("notAcceptable");
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [
                        "POST" => "tt",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
