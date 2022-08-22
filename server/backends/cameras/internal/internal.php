<?php

/**
 * backends cameras namespace
 */

namespace backends\cameras
{

    /**
     * internal.db cameras class
     */
    class internal extends cameras
    {
        /**
         * @inheritDoc
         */
        public function getCameras()
        {
            return $this->db->get("select * from cameras order by camera_id", false, [
                "camera_id" => "cameraId",
                "enabled" => "enabled",
                "model" => "model",
                "url" => "url",
                "stream" => "stream",
                "credentials" => "credentials",
                "comment" => "comment"
            ]);
        }

        /**
         * @inheritDoc
         */
        public function addCamera($enabled, $model, $url,  $stream, $credentials, $comment)
        {
            if (!$model) {
                return false;
            }

            $models = $this->getModels();

            if (!@$models[$model]) {
                return false;
            }

            if (!checkStr($url)) {
                return false;
            }

            return $this->db->insert("insert into cameras (enabled, model, url, stream, credentials, comment) values (:enabled, :model, :url, :stream, :credentials, :comment)", [
                "enabled" => (int)$enabled,
                "model" => $model,
                "url" => $url,
                "stream" => $stream,
                "credentials" => $credentials,
                "comment" => $comment,
            ]);
        }

        /**
         * @inheritDoc
         */
        public function modifyCamera($cameraId, $enabled, $model, $url, $stream, $credentials, $comment)
        {
            if (!checkInt($cameraId)) {
                setLastError("noId");
                return false;
            }

            if (!$model) {
                setLastError("noModel");
                return false;
            }

            $models = $this->getModels();

            if (!@$models[$model]) {
                setLastError("modelUnknown");
                return false;
            }

            if (!checkStr($url)) {
                return false;
            }

            return $this->db->modify("update cameras set enabled = :enabled, model = :model, url = :url, stream = :stream, credentials = :credentials, comment = :comment where camera_id = $cameraId", [
                "enabled" => (int)$enabled,
                "model" => $model,
                "url" => $url,
                "stream" => $stream,
                "credentials" => $credentials,
                "comment" => $comment,
            ]);
        }

        /**
         * @inheritDoc
         */
        public function deleteCamera($cameraId)
        {
            if (!checkInt($cameraId)) {
                setLastError("noId");
                return false;
            }

            return $this->db->modify("delete from cameras where camera_id = $cameraId");
        }

        /**
         * @inheritDoc
         */
        public function getModels()
        {
            $files = scandir(__DIR__ . "/../../../hw/cameras/models");

            $models = [];

            foreach ($files as $file) {
                if (substr($file, -5) === ".json") {
                    $models[$file] = json_decode(file_get_contents(__DIR__ . "/../../../hw/cameras/models/" . $file), true);
                }
            }

            return $models;
        }

        /**
         * @inheritDoc
         */
        public function getCamera($cameraId)
        {
            if (!checkInt($cameraId)) {
                return false;
            }

            return $this->db->get("select * from cameras where camera_id = $cameraId", false, [
                "camera_id" => "cameraId",
                "enabled" => "enabled",
                "model" => "model",
                "url" => "url",
                "stream" => "stream",
                "credentials" => "credentials",
                "comment" => "comment"
            ]);
        }
    }
}
