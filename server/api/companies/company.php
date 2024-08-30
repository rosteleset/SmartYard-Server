<?php

    /**
     * @api {get} /api/companies/company/:companyId get company
     *
     * @apiVersion 1.0.0
     *
     * @apiName getCompany
     * @apiGroup companies
     *
     * @apiHeader {String} token authentication token
     *
     * @apiParam {Number} companyId
     *
     * @apiSuccess {Object} company
     */

    /**
     * @api {post} /api/companies/company create company
     *
     * @apiVersion 1.0.0
     *
     * @apiName addCompany
     * @apiGroup companies
     *
     * @apiHeader {String} token authentication token
     *
     * @apiBody {String} type
     * @apiBody {String} uid
     * @apiBody {String} name
     * @apiBody {String} contacts
     * @apiBody {String} comments
     *
     * @apiSuccess {Number} companyId
     */

    /**
     * @api {put} /api/companies/company/:companyId modify company
     *
     * @apiVersion 1.0.0
     *
     * @apiName modifyCompany
     * @apiGroup companies
     *
     * @apiHeader {String} token authentication token
     *
     * @apiParam {Number} companyId
     * @apiBody {String} uid
     * @apiBody {String} name
     * @apiBody {String} contacts
     * @apiBody {String} comments
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * @api {delete} /api/companies/company/:companyId delete company
     *
     * @apiVersion 1.0.0
     *
     * @apiName deleteCompany
     * @apiGroup companies
     *
     * @apiHeader {String} token authentication token
     *
     * @apiParam {Number} companyId
     *
     * @apiSuccess {Boolean} operationResult
     */

    /**
     * companies api
     */

    namespace api\companies {

        use api\api;

        /**
         * companies
         */

        class company extends api {

            public static function GET($params) {
                $companies = loadBackend("companies");

                $company = false;

                if ($companies) {
                    $company = $companies->getCompany(@$params["_id"]);
                }

                return api::ANSWER($company, ($company !== false)?"company":"notFound");
            }

            public static function POST($params) {
                $companies = loadBackend("companies");

                $success = false;

                if ($companies) {
                    $success = $companies->addCompany(@$params["type"], @$params["uid"], @$params["name"], @$params["contacts"], @$params["comments"]);
                }

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function PUT($params) {
                $companies = loadBackend("companies");

                $success = false;

                if ($companies) {
                    $success = $companies->modifyCompany($params["_id"], @$params["type"], @$params["uid"], @$params["name"], @$params["contacts"], @$params["comments"]);
                }

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function DELETE($params) {
                $companies = loadBackend("companies");

                $success = false;

                if ($companies) {
                    $success = $companies->deleteCompany($params["_id"]);
                }

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function index() {
                if (loadBackend("companies")) {
                    return [
                        "GET" => "#same(companies,companies,GET)",
                        "POST",
                        "PUT",
                        "DELETE",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
