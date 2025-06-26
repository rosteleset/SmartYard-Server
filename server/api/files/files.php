<?php

    /**
     * @api {get} /api/files/files get files
     *
     * @apiVersion 1.0.0
     *
     * @apiName getFiles
     * @apiGroup files
     *
     * @apiHeader {String} token authentication token
     *
     * @apiQuery {String} [type]
     * @apiQuery {Boolean} [withContent]
     *
     * @apiSuccess {Object} operationResult
     */

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

                return api::ANSWER($_files, ($_files !== false) ? "files" : false);
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
