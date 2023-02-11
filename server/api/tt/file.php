<?php

    /**
     * tt api
     */

    namespace api\tt {

        use api\api;

        /**
         * tt (task tracker metadata(s)) method
         */

        class file extends api {

            public static function GET($params) {
                $tt = loadBackend("tt");

                if (!$tt) {
                    return API::ERROR(500);
                }

                $project = explode("-", $params["issueId"])[0];
                $filename = $params["filename"];

                $issue = $tt->getIssues($project, [ "issueId" => $params["issueId"] ], [ "issueId" ]);

                if (!$issue || !$issue["issues"] || !$issue["issues"][0]) {
                    return API::ERROR(404);
                }

                $files = loadBackend("files");

                return api::ANSWER($issue, "issue");
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
