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
            }

            return $this->db->get($q, $p, [
                "camera_id" => "cameraId",
                "enabled" => "enabled",
                "model" => "model",
                "url" => "url",
                "stream" => "stream",
                "credentials" => "credentials",
                "name" => "name",
                "publish" => "publish",
                "dvr_stream" => "dvrStream",
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
                "comment" => "comment"
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

            if (count($cams) === 1) {
                return $cams[0];
            } else {
                return false;
            }
        }

        /**
         * @inheritDoc
         */
        public function addCamera($enabled, $model, $url,  $stream, $credentials, $name, $dvrStream, $lat, $lon, $direction, $angle, $distance, $frs, $mdLeft, $mdTop, $mdWidth, $mdHeight, $common, $comment)
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

            if (!checkInt($md_left) || !checkInt($md_top) || !checkInt($md_width) || !checkInt($md_height)) {
                return false;
            }

            return $this->db->insert("insert into cameras (enabled, model, url, stream, credentials, name, dvr_stream, lat, lon, direction, angle, distance, frs, md_left, md_top, md_width, md_height, common, comment) values (:enabled, :model, :url, :stream, :credentials, :name, :dvr_stream, :lat, :lon, :direction, :angle, :distance, :frs, :md_left, :md_top, :md_width, :md_height, :common, :comment)", [
                "enabled" => (int)$enabled,
                "model" => $model,
                "url" => $url,
                "stream" => $stream,
                "credentials" => $credentials,
                "name" => $name,
                "dvr_stream" => $dvrStream,
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
            ]);
        }

        /**
         * @inheritDoc
         */
        public function modifyCamera($cameraId, $enabled, $model, $url, $stream, $credentials, $name, $dvrStream, $lat, $lon, $direction, $angle, $distance, $frs, $mdLeft, $mdTop, $mdWidth, $mdHeight, $common, $comment)
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

            return $this->db->modify("update cameras set enabled = :enabled, model = :model, url = :url, stream = :stream, credentials = :credentials, name = :name, dvr_stream = :dvr_stream, lat = :lat, lon = :lon, direction = :direction, angle = :angle, distance = :distance, frs = :frs, md_left = :md_left, md_top = :md_top, md_width = :md_width, md_height = :md_height, common = :common, comment = :comment where camera_id = $cameraId", [
                "enabled" => (int)$enabled,
                "model" => $model,
                "url" => $url,
                "stream" => $stream,
                "credentials" => $credentials,
                "name" => $name,
                "dvr_stream" => $dvrStream,
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
        public function addDownloadRecord($cameraId, $subscriberId, $start, $finish)
        {
            $dvr_files_path = @$this->config["backends"]["cameras"]["dvr_files_path"] ?: false;
            $dvr_files_location_prefix = @$this->config["backends"]["cameras"]["dvr_files_location_prefix"] ?: false;
            $dvr_files_ttl = @$this->config["backends"]["cameras"]["dvr_files_ttl"] ?: 259200;

            if (!checkInt($cameraId) || !checkInt($subscriberId) || !checkInt($start) || !checkInt($finish) || !$dvr_files_path || !$dvr_files_location_prefix) {
                return false;
            }

            $filename = GUIDv4() . '.mp4';
            
            return $this->db->insert("insert into camera_records (camera_id, subscriber_id, start, finish, filename, expire, state) values (:camera_id, :subscriber_id, :start, :finish, :filename, :expire, :state)", [
                "camera_id" => (int)$cameraId,
                "subscriber_id" => (int)$subscriberId,
                "start" => (int)$start,
                "finish" => (int)$finish,
                "filename" => $filename,
                "expire" => time() + $dvr_files_ttl,
                "state" => 0 //0 = created, 1 = in progress, 2 = completed, 3 = error
            ]);
        }

        /**
         * @inheritDoc
         */
        public function checkDownloadRecord($cameraId, $subscriberId, $start, $finish)
        {
            if (!checkInt($cameraId) || !checkInt($subscriberId) || !checkInt($start) || !checkInt($finish)) {
                return false;
            }
            return $this->db->get(
                "select record_id from camera_records where camera_id = :camera_id and subscriber_id = :subscriber_id AND start = :start AND finish = :finish",
                [
                    ":camera_id" => (int)$cameraId,
                    ":subscriber_id" => (int)$subscriberId,
                    ":start" => (int)$start,
                    ":finish" => (int)$finish
                ],
                [
                    "record_id" => "id",
                ],
                [
                    "singlify"
                ]
            );
        }
        
        /**
         * @inheritDoc
         */
        public function cron($part) {
            if ($part === "hourly") {
                $cameras = $this->db->get("select camera_id, url from cameras");

                foreach ($cameras as $camera) {
                    $ip = gethostbyname(parse_url($camera['url'], PHP_URL_HOST));

                    if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                        $this->db->modify("update cameras set ip = :ip where camera_id = " . $camera['camera_id'], [
                            "ip" => $ip,
                        ]);
                    }
                }

                return true;
            }
            if ($part === "minutely") {
                // TODO: загрузка записей DVR для загрузки и уведомление пользователя.

                return true;
            }
            if ($part === "daily") {
                // TODO: очистка старых записей из списка загрузки DVR-роликов

                return true;
            }
        }
    }
}
