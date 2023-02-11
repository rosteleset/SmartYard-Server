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
                /*
                 Array
                    (
                        [0] => Array
                            (
                                [chunkSize] => 261120
                                [filename] => upgrade-t85_default.hex
                                [length] => 6096
                                [uploadDate] => Array
                                    (
                                        [$date] => Array
                                            (
                                                [$numberLong] => 1675852725589
                                            )

                                    )

                                [md5] => c9845e138884b03218b736c563cbf3e1
                                [metadata] => Array
                                    (
                                        [date] => 1673495418
                                        [type] => text/x-hex
                                        [issue] => 1
                                        [project] => REM
                                        [issueId] => REM-32
                                        [attachman] => mmikel
                                    )

                                [id] => 63e37bb5a30d089fa90bcf30
                            )

                    )
                */
                $list = $files->searchFiles([ "metadata.issue" => true, "metadata.issueId" => $params["issueId"], "filename" => $filename ]);
                $file = $files->getFileStream($list[0]["id"]);

                header("Content-type: ".$lst[$f]['type']);
                /*
                 * Content-Disposition: inline
                 * Content-Disposition: attachment
                 * Content-Disposition: attachment; filename="filename.jpg"
                 */
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
                header('Content-Length:' . ($size - $begin));
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
