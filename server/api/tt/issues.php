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

                if (@$params["filter"]) {
                    try {
                        $filter = @json_decode($tt->getFilter($params["filter"]), true);
                        $myFilters = $tt->myFilters();

                        if (@$myFilters[$filter["name"]]) {
                            $issues = $tt->getIssues(@$filter["filter"], @$filter["fields"], @$params["sortBy"], @$params["skip"], @$params["limit"]);
                        }
                    } catch (\Exception $e) {
                        setLastError($e->getMessage());
                    }
                }

                return api::ANSWER($issues);
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
