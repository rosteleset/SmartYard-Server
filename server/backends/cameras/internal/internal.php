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
                "http_port" => "httpPort",
                "rtsp_port" => "rtspPort",
                "credentials" => "credentials",
                "comment" => "comment"
            ]);
        }

        /**
         * @inheritDoc
         */
        public function addCamera($enabled, $model, $ip, $httpPort,  $rtspPort, $credentials, $comment)
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

            $httpPort = (int)$httpPort;

            if ($httpPort < 0 || $httpPort >= 65536) {
                return false;
            }

            if (!$httpPort) {
                $httpPort = 80;
            }

            $rtspPort = (int)$rtspPort;

            if ($rtspPort < 0 || $rtspPort >= 65536) {
                return false;
            }

            if (!$rtspPort) {
                $rtspPort = 554;
            }

            $ip = long2ip($ip);

            return $this->db->insert("insert into cameras (enabled, model, ip, http_port, rtsp_port, credentials, comment) values (:enabled, :model, :ip, :http_port, :rtsp_port, :credentials, :comment)", [
                "enabled" => (int)$enabled,
                "model" => $model,
                "ip" => $ip,
                "http_port" => $httpPort,
                "rtsp_port" => $rtspPort,
                "credentials" => $credentials,
                "comment" => $comment,
            ]);
        }

        /**
         * @inheritDoc
         */
        public function modifyCamera($cameraId, $enabled, $model, $ip, $httpPort, $rtspPort, $credentials, $comment)
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

            $httpPort = (int)$httpPort;

            if ($httpPort < 0 || $httpPort >= 65536) {
                return false;
            }

            if (!$httpPort) {
                $httpPort = 80;
            }

            $rtspPort = (int)$rtspPort;

            if ($rtspPort < 0 || $rtspPort >= 65536) {
                return false;
            }

            if (!$rtspPort) {
                $rtspPort = 554;
            }

            $ip = long2ip($ip);

            return $this->db->modify("update cameras set enabled = :enabled, model = :model, ip = :ip, http_port = :http_port, rtsp_port = :rtsp_port, credentials = :credentials, comment = :comment where camera_id = $cameraId", [
                "enabled" => (int)$enabled,
                "model" => $model,
                "ip" => $ip,
                "http_port" => $httpPort,
                "rtsp_port" => $rtspPort,
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
