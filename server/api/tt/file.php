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
                $inline = [
                    "application/pdf",
                    "image/png",
                    "image/jpeg",
                    "image/gif",
                    "image/bmp",
                    "image/vnd.microsoft.icon",
                    "image/tiff",
                    "audio/mpeg",
                    "audio/x-m4a",
                    "video/mp4",
                ];

                $tt = loadBackend("tt");

                if (!$tt) {
                    return API::ERROR(500);
                }

                $project = explode("-", $params["issueId"])[0];
                $filename = $params["filename"];

                $issue = $tt->getIssues($project, [ "issueId" => $params["issueId"] ], [ "issueId" ]);

                if (!$issue || !$issue["issues"] || !$issue["issues"][0]) {
                    return API::ERROR("notFound");
                }

                $roles = $tt->myRoles();

                if (!@$roles[$project]) {
                    return API::ERROR("forbidden");
                }

                $files = loadBackend("files");
                $list = $files->searchFiles([ "metadata.issue" => true, "metadata.issueId" => $params["issueId"], "filename" => $filename ]);
                $file = @$files->getFileStream($list[0]["id"]);

                if ($file) {
                    header("Content-type: " . $list[0]["metadata"]["type"]);
                    if (in_array($list[0]["metadata"]["type"], $inline) !== false) {
                        header("Content-Disposition: inline; filename=" . urlencode($list[0]["filename"]));
                    } else {
                        header("Content-Disposition: attachment; filename=" . urlencode($list[0]["filename"]));
                    }

                    $begin = 0;
                    $size = (int)($list[0]["length"]);
                    $end = $size - 1;
                    $portion = $size - $begin;

                    if (isset($_SERVER['HTTP_RANGE'])) {
                        if (preg_match('/bytes=\h*(\d+)-(\d*)[\D.*]?/i', $_SERVER['HTTP_RANGE'], $matches)) {
                            $begin = intval($matches[1]);
                            if (!empty($matches[2])) {
                                $end = intval($matches[2]);
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
                    header('Content-Length:' . $portion);
                    header('Content-Transfer-Encoding: binary');

                    fseek($file, $begin);

                    $body = "";
                    $chunk = 0;
                    while (strlen($body) < $portion && !feof($file)) {
                        $part = fread($file, $chunk ? : $portion);
                        $body .= $part;
                        $chunk = $chunk ? : strlen($part);
                    }

                    echo substr($body, 0, $portion);

                    exit();
                }

                api::ERROR("notFound");
            }

            public static function POST($params)
            {
                $tt = loadBackend("tt");

                if (!$tt) {
                    return API::ERROR(500);
                }

                $success = $tt->addAttachments($params["issueId"], $params["attachments"]);

                if ($success) {
                    return api::ANSWER($success, "checksums");
                } else {
                    return api::ANSWER(false, "notAcceptable");
                }
            }

            public static function DELETE($params)
            {
                $tt = loadBackend("tt");

                if (!$tt) {
                    return API::ERROR(500);
                }

                $success = $tt->deleteAttachment($params["issueId"], $params["filename"]);

                return api::ANSWER($success, ($success !== false)?false:"notAcceptable");
            }

            public static function index() {
                if (loadBackend("tt")) {
                    return [
                        "GET" => "#same(tt,issue,GET)",
                        "POST" => "#same(tt,issue,POST)",
                        "DELETE" => "#same(tt,issue,DELETE)",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
