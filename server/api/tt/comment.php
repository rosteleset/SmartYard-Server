<?php

    /**
     * tt api
     */

    namespace api\tt {

        use api\api;

        /**
         * tt (task tracker metadata(s)) method
         */

        class comment extends api {

            public static function GET($params) {
                return api::ANSWER();
            }

            public static function POST($params)
            {
                $tt = loadBackend("tt");

                if (!$tt) {
                    return API::ERROR(500);
                }

                $acr = explode("-", $params["issueId"])[0];

                $projects = $tt->getProjects();
                $project = false;
                foreach ($projects as $p) {
                    if ($p["acronym"] == $acr) {
                        $project = $p;
                    }
                }

                $issue = $tt->getIssues($acr, [ "issueId" => $params["issueId"] ], [ "issueId" ]);

                if (!$issue || !$issue["issues"] || !$issue["issues"][0] || !$project) {
                    return API::ERROR("notFound");
                }

                $roles = $tt->myRoles();

                if (!@$roles[$acr] || $roles[$acr] < 20) {
                    return API::ERROR("forbidden");
                }

                if (!checkStr($params["comment"])) {
                    return API::ERROR("notAcceptable");
                }

                $tt->addComment($issue["issues"][0], $params["comment"], !!$params["commentPrivate"]);

                return api::ANSWER();
            }

            public static function PUT($params)
            {
                return api::ANSWER();
            }

            public static function DELETE($params)
            {
                return api::ANSWER();
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [
                        "GET" => "#same(tt,issue,GET)",
                        "POST" => "#same(tt,issue,POST)",
                        "PUT" => "#same(tt,issue,PUT)",
                        "DELETE" => "#same(tt,issue,DELETE)",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
