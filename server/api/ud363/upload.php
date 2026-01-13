<?php

    /**
     * @api {get} /api/ud363/upload get upload slot
     *
     * @apiVersion 1.0.0
     *
     * @apiName getUploadSlot
     * @apiGroup ud363
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiQuery {String} name filename
     * @apiQuery {String} date filedate
     * @apiQuery {String} type mimetype
     * @apiQuery {Number} size filesize
     *
     * @apiSuccess {Object} operationResult
     */

    /**
     * @api {post} /api/ud363/upload put file part
     *
     * @apiVersion 1.0.0
     *
     * @apiName postFilePart
     * @apiGroup ud363
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiBody {String} slot fileslot
     * @apiBody {String} part filepart
     *
     * @apiSuccess {Object} operationResult
     */

    /**
     * ud363 api
     */

    namespace api\ud363 {

        use api\api;

        /**
         * upload method
         */

        class upload extends api {

            public static function GET($params) {
                return true;
            }

            public static function POST($params) {
/*
                $targetDir = "uploads/";
                $fileName = $_POST['file_name'];
                $targetFile = $targetDir . basename($fileName);

                // Получаем временный файл чанка
                $chunk = $_FILES['file_chunk']['tmp_name'];

                // Читаем содержимое чанка и дописываем в целевой файл
                $content = file_get_contents($chunk);
                file_put_contents($targetFile, $content, FILE_APPEND);

                echo json_encode(["status" => "success"]);
*/
                return true;
            }

            public static function index() {
                if (loadBackend("ud363")) {
                    return [
                        "GET" => "#common",
                        "POST" => "#common",
                    ];
                } else {
                    return false;
                }
            }
        }
    }
