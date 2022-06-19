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
                "ip" => "ip",
                "port" => "port",
                "rtsp_stream" => "rtspStream",
                "credentials" => "credentials",
                "comment" => "comment"
            ]);
        }

        /**
         * @inheritDoc
         */
        public function addCamera($enabled, $model, $ip, $port,  $stream, $credentials, $comment)
        {
            if (!$model) {
                return false;
            }

            $models = $this->getModels();

            if (!@$models[$model]) {
                return false;
            }

            $ip = ip2long($ip);

            if (!$ip) {
                return false;
            }

            $port = (int)$port;

            if ($port < 0 || $port >= 65536) {
                return false;
            }

            if (!$port) {
                $port = 80;
            }

            $ip = long2ip($ip);

            return $this->db->insert("insert into cameras (enabled, model, ip, port, stream, credentials, comment) values (:enabled, :model, :ip, :port, :stream, :credentials, :comment)", [
                "enabled" => (int)$enabled,
                "model" => $model,
                "ip" => $ip,
                "port" => $port,
                "stream" => $stream,
                "credentials" => $credentials,
                "comment" => $comment,
            ]);
        }

        /**
         * @inheritDoc
         */
        public function modifyCamera($cameraId, $enabled, $model, $ip, $port, $stream, $credentials, $comment)
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

            $ip = ip2long($ip);

            if (!$ip) {
                setLastError("noIp");
                return false;
            }

            $port = (int)$port;

            if ($port < 0 || $port >= 65536) {
                return false;
            }

            if (!$port) {
                $port = 80;
            }

            $ip = long2ip($ip);

            return $this->db->modify("update cameras set enabled = :enabled, model = :model, ip = :ip, port = :port, stream = :stream, credentials = :credentials, comment = :comment where camera_id = $cameraId", [
                "enabled" => (int)$enabled,
                "model" => $model,
                "ip" => $ip,
                "port" => $port,
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
    }
}
