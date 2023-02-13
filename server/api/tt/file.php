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

                $files = loadBackend("files");

                foreach ($params["attachments"] as $attachment) {
                    $list = $files->searchFiles([ "metadata.issue" => true, "metadata.issueId" => $params["issueId"], "filename" => $attachment["name"] ]);
                    if (count($list)) {
                        return API::ERROR("alreadyExists");
                    }
                    if (strlen(base64_decode($attachment["body"])) > $project["maxFileSize"]) {
                        return API::ERROR("exceededSize");
                    }
                }

                foreach ($params["attachments"] as $attachment) {
                    $files->addFile($attachment["name"], $files->contentsToStream(base64_decode($attachment["body"])), [
                        "date" => round($attachment["date"] / 1000),
                        "added" => time(),
                        "type" => $attachment["type"],
                        "issue" => true,
                        "project" => $acr,
                        "issueId" => $params["issueId"],
                        "attachman" => $params["_login"],
                    ]);
                }

                return api::ANSWER();
            }

            public static function DELETE($params)
            {
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

                if (!@$roles[$project] || $roles[$project] < 20) {
                    return API::ERROR("forbidden");
                }

                $files = loadBackend("files");
                $list = $files->searchFiles([ "metadata.issue" => true, "metadata.attachman" => $params["_login"], "metadata.issueId" => $params["issueId"], "filename" => $filename ]);

                if ($list && $list[0] && $list[0]["id"]) {
                    $files->deleteFile($list[0]["id"]);

                    return api::ANSWER();
                }

                return api::ERROR("notFound");
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
