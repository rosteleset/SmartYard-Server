<?php

    /**
     * files api
     */

    namespace api\files {

        use api\api;

        /**
         * file method
         */

        class file extends api {

            public static function GET($params) {
                $files = loadBackend("files");

                $list = $files->searchFiles([
                    "metadata.type" => @$params["type"],
                    "metadata.owner" => $params["_login"],
                    "filename" => @$params["filename"],
                ]);

                $file = false;

                foreach ($list as $f) {
                    $file = $files->streamToContents($files->getFileStream($f["id"]));
                    break;
                }

                return api::ANSWER($file, ($file !== false)?"file":false);
            }

            public static function POST($params) {
                return self::PUT($params);
            }

            public static function PUT($params) {
                $files = loadBackend("files");

                $success = true;

                $list = $files->searchFiles([
                    "metadata.type" => @$params["type"],
                    "metadata.owner" => $params["_login"],
                    "filename" => @$params["filename"],
                ]);

                foreach ($list as $f) {
                    $success = $success && $files->deleteFile($f["id"]);
                }

                $success = $success && $files->addFile(@$params["filename"], $files->contentsToStream(@$params["file"]), [
                    "type" => @$params["type"],
                    "owner" => $params["_login"],
                ]);

                return api::ANSWER($success);
            }

            public static function DELETE($params) {
                $files = loadBackend("files");

                $success = true;

                $list = $files->searchFiles([
                    "metadata.type" => @$params["type"],
                    "metadata.owner" => $params["_login"],
                    "filename" => @$params["filename"],
                ]);

                foreach ($list as $f) {
                    $success = $success && $files->deleteFile($f["id"]);
                }

                return api::ANSWER($success);
            }

            public static function index() {
                if (loadBackend("files")) {
                    return [
                        "GET" => "#common",
                        "POST" => "#common",
                        "PUT" => "#common",
                        "DELETE" => "#common",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
