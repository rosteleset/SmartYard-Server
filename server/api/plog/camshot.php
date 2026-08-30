<?php

    /**
     * @api {get} /api/plog/camshot/:imageUuid get event camshot
     *
     * @apiVersion 1.0.0
     *
     * @apiName getPlogCamshot
     * @apiGroup plog
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiParam {String} imageUuid event image UUID
     *
     * @apiSuccess {Binary} image
     */

    namespace api\plog {

        use api\api;

        class camshot extends api {

            private static function resolveFileId($files, string $image_uuid) {
                $image_uuid = trim($image_uuid);
                if ($image_uuid === "") {
                    return false;
                }

                if (strpos($image_uuid, "-") !== false) {
                    return $files->fromGUIDv4($image_uuid);
                }

                if (preg_match('/^[a-f0-9]{24}$/i', $image_uuid)) {
                    return $image_uuid;
                }

                return $files->fromGUIDv4($image_uuid);
            }

            public static function GET($params) {
                $image_uuid = trim(@$params["uuid"] ?: @$params["_id"]);
                if (!$image_uuid) {
                    return api::ANSWER(false, "badRequest");
                }

                $files = loadBackend("files");
                if (!$files) {
                    return api::ANSWER(false, "notAcceptable");
                }

                $file_id = self::resolveFileId($files, $image_uuid);
                if (!$file_id) {
                    return api::ANSWER(false, "badRequest");
                }

                try {
                    $img = $files->getFile($file_id);
                } catch (\Throwable $e) {
                    error_log(print_r($e, true));
                    return api::ANSWER(false, "notFound");
                }

                if (!$img) {
                    return api::ANSWER(false, "notFound");
                }

                $content_type = "image/jpeg";
                $meta_data = $files->getFileMetadata($file_id);
                if (isset($meta_data->contentType)) {
                    $content_type = $meta_data->contentType;
                }

                header("Content-Type: $content_type");
                header("Cache-Control: private, max-age=3600");
                echo stream_get_contents($img["stream"]);
                exit;
            }

            public static function index() {
                return [
                    "GET" => "#same(addresses,house,GET)",
                ];
            }
        }
    }
