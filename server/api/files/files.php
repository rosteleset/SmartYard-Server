<?php

    /**
     * files api
     */

    namespace api\files {

        use api\api;

        /**
         * files method
         */

        class files extends api {

            public static function GET($params) {
                $files = loadBackend("files");

                $_files = false;

                if ($files) {
                    $_files = $files->searchFiles([
                        "metadata.type" => @$params["type"],
                        "metadata.owner" => $params["_login"],
                    ]);
                }

                if (@$params["withContent"]) {
                    foreach ($_files as &$file) {
                        $file["file"] = $files->streamToContents($files->getFileStream($file["id"]));
                    }
                }

                return api::ANSWER($_files, ($_files !== false)?"files":false);
            }

            public static function index() {
                if (loadBackend("files")) {
                    return [
                        "GET" => "#common",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
