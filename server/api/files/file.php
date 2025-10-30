<?php

    /**
     * @api {get} /api/files/file get file
     *
     * @apiVersion 1.0.0
     *
     * @apiName getFile
     * @apiGroup files
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiQuery {String} filename
     * @apiQuery {String} [type]
     *
     * @apiSuccess {Object} operationResult
     */

    /**
     * @api {put} /api/files/file put file
     *
     * @apiVersion 1.0.0
     *
     * @apiName putFile
     * @apiGroup files
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiBody {String} filename
     * @apiBody {String} file
     * @apiBody {String} [type]
     * @apiBody {Object} [metadata]
     *
     * @apiSuccess {Object} operationResult
     */

    /**
     * @api {post} /api/files/file put file
     *
     * @apiVersion 1.0.0
     *
     * @apiName postFile
     * @apiGroup files
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiBody {String} filename
     * @apiBody {String} file
     * @apiBody {String} [type]
     * @apiBody {Object} [metadata]
     *
     * @apiSuccess {Object} operationResult
     */

    /**
     * @api {delete} /api/files/file delete file
     *
     * @apiVersion 1.0.0
     *
     * @apiName deleteFile
     * @apiGroup files
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiBody {String} filename
     * @apiBody {String} [type]
     *
     * @apiSuccess {Object} operationResult
     */

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

                $search = [
                    "metadata.owner" => $params["_login"],
                ];

                if (@$params["type"]) {
                    $search["metadata.type"] = $params["type"];
                }

                if (@$params["filename"]) {
                    $search["filename"] = $params["filename"];
                }

                $list = $files->searchFiles($search);

                $file = false;

                foreach ($list as $f) {
                    $file = $files->streamToContents($files->getFileStream($f["id"]));
                    break;
                }

                return api::ANSWER($file, ($file !== false) ? "file" : false);
            }

            public static function POST($params) {
                return self::PUT($params);
            }

            public static function PUT($params) {
                $files = loadBackend("files");

                $success = true;

                $search = [
                    "metadata.owner" => $params["_login"],
                ];

                if (@$params["type"]) {
                    $search["metadata.type"] = $params["type"];
                }

                if (@$params["filename"]) {
                    $search["filename"] = $params["filename"];
                }

                $list = $files->searchFiles($search);

                foreach ($list as $f) {
                    $success = $success && $files->deleteFile($f["id"]);
                }

                $meta = [];

                if (@$params["metadata"]) {
                    $meta = $params["metadata"];
                }

                $meta["type"] = @$params["type"];
                $meta["owner"] = $params["_login"];

                $success = $success && $files->addFile(@$params["filename"], $files->contentsToStream(@$params["file"]), $meta);

                if ($success) {
                    return api::ANSWER(md5(@$params["file"]));
                } else {
                    return api::ANSWER(false);
                }
            }

            public static function DELETE($params) {
                $files = loadBackend("files");

                $success = 0;

                $search = [
                    "metadata.owner" => $params["_login"],
                ];

                if (@$params["type"]) {
                    $search["metadata.type"] = $params["type"];
                }
                if (@$params["filename"]) {
                    $search["filename"] = $params["filename"];
                }

                $list = $files->searchFiles($search);

                foreach ($list as $f) {
                    $success += (int)!!$files->deleteFile($f["id"]);
                }

                return api::ANSWER($success > 0);
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
