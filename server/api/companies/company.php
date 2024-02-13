<?php

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
