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

                header("Content-type: ".$lst[$f]['type']);
                header("Content-Disposition: attachment; filename=".urlencode($lst[$f]['name']));

                $begin = 0;
                $size = strlen($mon);
                $end = $size - 1;

                if (isset($_SERVER['HTTP_RANGE'])) {
                    if (preg_match('/bytes=\h*(\d+)-(\d*)[\D.*]?/i', $_SERVER['HTTP_RANGE'], $matches)) {
                        $begin  = intval($matches[1]);
                        if (!empty($matches[2])) {
                            $end  = intval($matches[2]);
                        }
                    }
                    header('HTTP/1.1 206 Partial Content');
                    header("Content-Range: bytes $begin-$end/$size");
                } else {
                    header('HTTP/1.1 200 OK');
                }

                header('Cache-Control: public, must-revalidate, max-age=0');
                header('Pragma: no-cache');
                header('Accept-Ranges: bytes');
                header('Content-Length:'.($size - $begin));
                header('Content-Transfer-Encoding: binary');

                echo substr($mon, $begin, $size - $begin);


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
