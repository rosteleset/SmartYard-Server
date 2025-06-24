<?php

    /**
     * @api {post} /api/tt/customField add custom field
     *
     * @apiVersion 1.0.0
     *
     * @apiName addCustomField
     * @apiGroup tt
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiBody {String} catalog
     * @apiBody {String} type
     * @apiBody {String} field
     * @apiBody {String} fieldDisplay
     * @apiBody {String} fieldDisplayList
     *
     * @apiSuccess {Number} customFieldId
     */

    /**
     * @api {put} /api/tt/customField/:customFieldId modify custom field
     *
     * @apiVersion 1.0.0
     *
     * @apiName modifyCustomField
     * @apiGroup tt
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {Number} customFieldId
     * @apiBody {String} catalog
     * @apiBody {String} fieldDisplay
     * @apiBody {String} fieldDisplayList
     * @apiBody {String} fieldDescription
     * @apiBody {String} regex
     * @apiBody {String} format
     * @apiBody {String} link
     * @apiBody {String} options
     * @apiBody {Boolean} indx
     * @apiBody {Boolean} search
     * @apiBody {Boolean} required
     * @apiBody {String} editor
     * @apiBody {Number} float
     * @apiBody {Boolean} readonly
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * @api {delete} /api/tt/customField/:customFieldId delete custom field
     *
     * @apiVersion 1.0.0
     *
     * @apiName deleteCustomField
     * @apiGroup tt
     *
     * @apiHeader {String} authorization authentication token
     *
     * @apiParam {Number} customFieldId
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * tt api
     */

    namespace api\tt {

        use api\api;

        /**
         * customField method
         */

        class customField extends api {

            public static function POST($params) {
                $customFieldId = loadBackend("tt")->addCustomField($params["catalog"], $params["type"], $params["field"], $params["fieldDisplay"], $params["fieldDisplayList"]);

                return api::ANSWER($customFieldId, ($customFieldId !== false) ? "customFieldId" : "notAcceptable");
            }

            public static function PUT($params) {
                $success = loadBackend("tt")->modifyCustomField($params["_id"], $params["catalog"], $params["fieldDisplay"], $params["fieldDisplayList"], $params["fieldDescription"], $params["regex"], $params["format"], $params["link"], $params["options"], $params["indx"], $params["search"], $params["required"], $params["editor"], $params["float"], $params["readonly"]);

                return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
            }

            public static function DELETE($params) {
                $success = loadBackend("tt")->deleteCustomField($params["_id"]);

                return api::ANSWER($success, ($success !== false) ? false : "notAcceptable");
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [
                        "POST" => "#same(tt,project,POST)",
                        "PUT" => "#same(tt,project,PUT)",
                        "DELETE" => "#same(tt,project,DELETE)",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
