<?php

    /**
     * tt api
     */

    namespace api\tt {

        use api\api;

        /**
         * tt issues count and bodies
         */

        class issues extends api {

            public static function GET($params) {
                $issues = [];

                $tt = loadBackend("tt");

                if (@$params["filter"] && $params["filter"] != "empty") {
                    try {
                        $filter = @json_decode($tt->getFilter($params["filter"]), true);
                        if ($filter) {
                            $issues = $tt->getIssues(@$params["project"] ? : "TT", @$filter["filter"], @$filter["fields"], @$params["sortBy"] ? : [ "created" => 1 ], @$params["skip"] ? : 0, @$params["limit"] ? : 5);
                        } else {
                            setLastError("filterNotFound");
                            return api::ERROR();
                        }
                    } catch (\Exception $e) {
                        setLastError($e->getMessage());
                        return api::ERROR();
                    }
                }

                return api::ANSWER($issues, ($issues !== false)?"issues":"notFound");
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [
                        "GET" => "#same(tt,issue,GET)",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
