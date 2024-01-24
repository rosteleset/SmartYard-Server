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
        public function getCameras($by = false, $params = false)
        {
            $q = "select * from cameras order by camera_id";
            $p = false;

            switch ($by) {
                case "id":
                    $q = "select * from cameras where camera_id = :camera_id";
                    $p = [
                        "camera_id" => $params,
                    ];
                    break;

                case "common":
                    $q = "select * from cameras where common = 1";
                    break;
            }

            return $this->db->get($q, $p, [
                "camera_id" => "cameraId",
                "enabled" => "enabled",
                "model" => "model",
                "url" => "url",
                "stream" => "stream",
                "credentials" => "credentials",
                "name" => "name",
                "dvr_stream" => "dvrStream",
                "timezone" => "timezone",
                "lat" => "lat",
                "lon" => "lon",
                "direction" => "direction",
                "angle" => "angle",
                "distance" => "distance",
                "frs" => "frs",
                "md_left" => "mdLeft",
                "md_top" => "mdTop",
                "md_width" => "mdWidth",
                "md_height" => "mdHeight",
                "common" => "common",
                "comment" => "comment",
                "sound" => "sound"
            ]);
        }

        /**
         * @inheritDoc
         */
        public function getCamera($cameraId)
        {
            if (!checkInt($cameraId)) {
                return false;
            }

            $cams = $this->getCameras("id", $cameraId);

            if (count($cams) !== 1) {
                return false;
            }

            $camera = $cams[0];
            $modelFilePath = __DIR__ . "/../../../hw/ip/camera/models/" . $camera["model"];
            $camera["json"] = json_decode(file_get_contents($modelFilePath), true);

            return $camera;
        }

        /**
         * @inheritDoc
         */
        public function addCamera($enabled, $model, $url,  $stream, $credentials, $name, $dvrStream, $timezone, $lat, $lon, $direction, $angle, $distance, $frs, $mdLeft, $mdTop, $mdWidth, $mdHeight, $common, $comment, $sound)
        {
            if (!$model) {
                return false;
            }

            $configs = loadBackend("configs");
            $models = $configs->getCamerasModels();

            if (!@$models[$model]) {
                return false;
            }

            if (!checkStr($url)) {
                return false;
            }

            if (!checkInt($mdLeft) || !checkInt($mdTop) || !checkInt($mdWidth) || !checkInt($mdHeight)) {
                return false;
            }

            $cameraId = $this->db->insert("insert into cameras (enabled, model, url, stream, credentials, name, dvr_stream, timezone, lat, lon, direction, angle, distance, frs, md_left, md_top, md_width, md_height, common, comment, sound) values (:enabled, :model, :url, :stream, :credentials, :name, :dvr_stream, :timezone, :lat, :lon, :direction, :angle, :distance, :frs, :md_left, :md_top, :md_width, :md_height, :common, :comment, :sound)", [
                "enabled" => (int)$enabled,
                "model" => $model,
                "url" => $url,
                "stream" => $stream,
                "credentials" => $credentials,
                "name" => $name,
                "dvr_stream" => $dvrStream,
                "timezone" => $timezone,
                "lat" => $lat,
                "lon" => $lon,
                "direction" => $direction,
                "angle" => $angle,
                "distance" => $distance,
                "frs" => $frs,
                "md_left" => $mdLeft,
                "md_top" => $mdTop,
                "md_width" => $mdWidth,
                "md_height" => $mdHeight,
                "common" => $common,
                "comment" => $comment,
                "sound" => $sound,
            ]);

            $queue = loadBackend("queue");
            if ($queue) {
                $queue->changed("camera", $cameraId);
            }

            $this->updateDeviceIds($cameraId, $model, $url, $credentials);

            return $cameraId;
        }

        /**
         * @inheritDoc
         */
        public function modifyCamera($cameraId, $enabled, $model, $url, $stream, $credentials, $name, $dvrStream, $timezone, $lat, $lon, $direction, $angle, $distance, $frs, $mdLeft, $mdTop, $mdWidth, $mdHeight, $common, $comment, $sound)
        {
            if (!checkInt($cameraId)) {
                setLastError("noId");
                return false;
            }

            if (!$model) {
                setLastError("noModel");
                return false;
            }

            $configs = loadBackend("configs");
            $models = $configs->getCamerasModels();

            if (!@$models[$model]) {
                setLastError("modelUnknown");
                return false;
            }

            if (!checkStr($url)) {
                return false;
            }

            $r = $this->db->modify("update cameras set enabled = :enabled, model = :model, url = :url, stream = :stream, credentials = :credentials, name = :name, dvr_stream = :dvr_stream, timezone = :timezone, lat = :lat, lon = :lon, direction = :direction, angle = :angle, distance = :distance, frs = :frs, md_left = :md_left, md_top = :md_top, md_width = :md_width, md_height = :md_height, common = :common, comment = :comment, sound = :sound where camera_id = $cameraId", [
                "enabled" => (int)$enabled,
                "model" => $model,
                "url" => $url,
                "stream" => $stream,
                "credentials" => $credentials,
                "name" => $name,
                "dvr_stream" => $dvrStream,
                "timezone" => $timezone,
                "lat" => $lat,
                "lon" => $lon,
                "direction" => $direction,
                "angle" => $angle,
                "distance" => $distance,
                "frs" => $frs,
                "md_left" => $mdLeft,
                "md_top" => $mdTop,
                "md_width" => $mdWidth,
                "md_height" => $mdHeight,
                "common" => $common,
                "comment" => $comment,
                "sound" => (int)$sound,
            ]);

            if ($r) {
                $queue = loadBackend("queue");
                if ($queue) {
                    $queue->changed("camera", $cameraId);
                }

                $this->updateDeviceIds($cameraId, $model, $url, $credentials);
            }

            return $r;
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

            $queue = loadBackend("queue");
            if ($queue) {
                $queue->changed("camera", $cameraId);
            }

            return $this->db->modify("delete from cameras where camera_id = $cameraId");
        }

        /**
         * @inheritDoc
         */
        public function cron($part) {
            if ($part === "hourly") {
                $this->updateDevicesIds();
            }

            return true;
        }

        protected function updateDevicesIds() {
            $query = "select camera_id, model, url, credentials from cameras";
            $devices = $this->db->get($query);

            foreach ($devices as $device) {
                [
                    'camera_id' => $deviceId,
                    'model' => $model,
                    'url' => $url,
                    'credentials' => $credentials
                ] = $device;

                $this->updateDeviceIds($deviceId, $model, $url, $credentials);
            }
        }

        protected function updateDeviceIds($deviceId, $model, $url, $credentials) {
            if ($model === 'sputnik.json') {
                $device = loadDevice('camera', $model, $url, $credentials);

                if ($device) {
                    $query = "update cameras
                              set sub_id = :sub_id
                              where camera_id = " . $deviceId;
                    $this->db->modify($query, ["sub_id" => $device->uuid]);
                }
            } else {
                $ip = gethostbyname(parse_url($url, PHP_URL_HOST));

                if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                    $query = "update cameras set ip = :ip where camera_id = " . $deviceId;
                    $this->db->modify($query, ["ip" => $ip]);
                }
            }
        }
    }
}
