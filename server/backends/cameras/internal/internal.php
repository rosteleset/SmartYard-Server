<?php

    /**
     * backends cameras namespace
     */

    namespace backends\cameras {

        use Exception;

        /**
         * internal.db cameras class
         */

        class internal extends cameras {

            /**
             * @inheritDoc
             */

            public function getCameras($by = false, $query = false, $withStatus = false) {
                $q = "select * from cameras order by camera_id";
                $p = false;

                switch ($by) {
                    case "id":
                        $q = "select * from cameras where camera_id = :camera_id";
                        $p = [
                            "camera_id" => (int)$query,
                        ];
                        break;

                    case "common":
                        $q = "select * from cameras where common = 1";
                        break;

                    case "owned_by_flats":
                        $q = "select * from cameras where camera_id in (select camera_id from houses_cameras_flats)";
                        break;

                    case "owned_by_houses":
                        $q = "select * from cameras where camera_id in (select camera_id from houses_cameras_houses)";
                        break;

                    case "owned_by_subscribers":
                        $q = "select * from cameras where camera_id in (select camera_id from houses_cameras_subscribers)";
                        break;

                    case "not_installed":
                        $q = "select * from cameras where coalesce(common, 0) = 0 and camera_id not in (select camera_id from houses_cameras_flats) and camera_id not in (select camera_id from houses_cameras_houses) and camera_id not in (select camera_id from houses_cameras_subscribers)";
                        break;

                    case "tree":
                        $q = "select * from cameras where tree like concat(:tree, '%')";
                        $p = [
                            "tree" => $query,
                        ];
                        break;
                }

                $monitoring = loadBackend("monitoring");

                $cameras = $this->db->get($q, $p, [
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
                    "frs_mode" => "frsMode",
                    "md_area" => "mdArea",
                    "rc_area" => "rcArea",
                    "common" => "common",
                    "comments" => "comments",
                    "sound" => "sound",
                    "ip" => "ip",
                    "monitoring" => "monitoring",
                    "webrtc" => "webrtc",
                    "ext" => "ext",
                ]);

                foreach($cameras as $key => $camera) {
                    $cameras[$key]["mdArea"] = json_decode($camera["mdArea"]);
                    $cameras[$key]["rcArea"] = json_decode($camera["rcArea"]);
                    $cameras[$key]["ext"] = json_decode($camera["ext"]);
                }

                if ($monitoring && $withStatus) {
                    $targetHosts = [];

                    foreach ($cameras as $camera) {
                        $targetHosts[] = [
                            'hostId' => $camera['cameraId'],
                            'enabled' => $camera['enabled'],
                            'ip' => $camera['ip'] ?? null,
                            'url' => $camera['url'] ?? null,
                            'dvrStream' => $camera['dvrStream']
                        ];
                    }

                    //get status
                    $targetStatus = $monitoring->devicesStatus("camera", $targetHosts);

                    if ($targetStatus) {
                        foreach ($cameras as &$camera) {
                            $camera["status"] = $targetStatus[$camera["cameraId"]]['status'];
                        }
                    }
                }

                return $cameras;
            }

            /**
             * @inheritDoc
             */

            public function getCamera($cameraId) {
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

            public function addCamera($enabled, $model, $url,  $stream, $credentials, $name, $dvrStream, $timezone, $lat, $lon, $direction, $angle, $distance, $frs, $frsMode, $mdArea, $rcArea, $common, $comments, $sound, $monitoring, $webrtc, $ext, $tree = '') {
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

                if (!checkInt($frsMode) || $frsMode < 0 || $frsMode > 2) {
                    return false;
                }

                $cameraId = $this->db->insert("insert into cameras (enabled, model, url, stream, credentials, name, dvr_stream, timezone, lat, lon, direction, angle, distance, frs, frs_mode, md_area, rc_area, common, comments, sound, monitoring, webrtc, ext) values (:enabled, :model, :url, :stream, :credentials, :name, :dvr_stream, :timezone, :lat, :lon, :direction, :angle, :distance, :frs, :frs_mode, :md_area, :rc_area, :common, :comments, :sound, :monitoring, :webrtc, :ext, :tree)", [
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
                    "frs_mode" => $frsMode,
                    "md_area" => json_encode($mdArea),
                    "rc_area" => json_encode($rcArea),
                    "common" => $common,
                    "comments" => $comments,
                    "sound" => (int)$sound,
                    "monitoring" => (int)$monitoring,
                    "webrtc" => (int)$webrtc,
                    "ext" => json_encode($ext),
                    "tree" => $tree ?: '',
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

            public function modifyCamera($cameraId, $enabled, $model, $url, $stream, $credentials, $name, $dvrStream, $timezone, $lat, $lon, $direction, $angle, $distance, $frs, $frsMode, $mdArea, $rcArea, $common, $comments, $sound, $monitoring, $webrtc, $ext, $tree = '') {
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

                if (!checkInt($frsMode) || $frsMode < 0 || $frsMode > 2) {
                    return false;
                }

                $r = $this->db->modify("update cameras set enabled = :enabled, model = :model, url = :url, stream = :stream, credentials = :credentials, name = :name, dvr_stream = :dvr_stream, timezone = :timezone, lat = :lat, lon = :lon, direction = :direction, angle = :angle, distance = :distance, frs = :frs, frs_mode = :frs_mode, md_area = :md_area, rc_area = :rc_area, common = :common, comments = :comments, sound = :sound, monitoring = :monitoring, webrtc = :webrtc, ext = :ext, tree = :tree where camera_id = $cameraId", [
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
                    "frs_mode" => $frsMode,
                    "md_area" => json_encode($mdArea),
                    "rc_area" => json_encode($rcArea),
                    "common" => $common,
                    "comments" => $comments,
                    "sound" => (int)$sound,
                    "monitoring" => (int)$monitoring,
                    "webrtc" => (int)$webrtc,
                    "ext" => json_encode($ext),
                    "tree" => $tree ?: '',
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

            public function deleteCamera($cameraId) {
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
                $devices = $this->db->get("select camera_id, model, url, credentials from cameras");

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
                        $this->db->modify("update cameras set sub_id = :sub_id where camera_id = " . $deviceId, [
                            "sub_id" => $device->uuid
                        ]);
                    }
                } else {
                    $ip = gethostbyname(parse_url($url, PHP_URL_HOST));

                    if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                        $this->db->modify("update cameras set ip = :ip where camera_id = " . $deviceId, [
                            "ip" => $ip
                        ]);
                    }
                }
            }

            public function getSnapshot(int $cameraId): ?string {
                $cameraData = $this->getCamera($cameraId);
                $snapshotUrl = $cameraData['ext']->snapshotUrl ?? null;

                if ($snapshotUrl) {
                    $snapshot = @file_get_contents($snapshotUrl);

                    if ($snapshot === false) {
                        error_log("Error getting snapshot from '$snapshotUrl' using direct URL");
                        return null;
                    }

                    return $snapshot;
                }

                try {
                    $device = loadDevice(
                        type: 'camera',
                        model: $cameraData['model'],
                        url: $cameraData['url'],
                        password: $cameraData['credentials'],
                    );

                    return $device->getCamshot();
                } catch (Exception) {
                    error_log("Error getting snapshot from '{$cameraData['url']}' using device method");
                    return null;
                }
            }

            /**
             * @inheritDoc
             */

            public function addLeaf($parent, $newName) {
                if (!checkStr($tree) || !checkStr($name)) {
                    return false;
                }

                $tree = $this->db->get("select * from core_devices_tree");

                $depth = count(explode(".", $parent));

                $newLeaf = 0;

                $f = false;

                if ($parent) {
                    foreach ($tree as $leaf) {
                        if (substr($leaf["tree"], 0, strlen($parent)) == $parent) {
                            $f = true;
                            break;
                        }
                    }
                }

                if (!$f) {
                    return false;
                }

                foreach ($tree as $leaf) {
                    $name = $leaf["name"];
                    $leaf = $leaf["tree"];
                    $ct = explode(".", $leaf);
                    if (count($ct) == $depth + 1) {
                        if ($name == $newName) {
                            return false;
                        }
                        if ($newLeaf < $ct[count($ct) - 2]) {
                            $newLeaf = $ct[count($ct) - 2];
                        }
                    }
                }

                $newLeaf++;

                $tree = $parent . $newLeaf . '.';

                $this->db->modify("insert into core_devices_tree (tree, name) values (:tree, :name)", [
                    "tree" => $tree,
                    "name" => $newName,
                ]);

                return $tree;
            }

            /**
             * @inheritDoc
             */

            public function modifyLeaf($tree, $name) {
                if (!checkStr($tree) || !checkStr($name)) {
                    return false;
                }

                return $this->db->modify("update core_devices_tree set name = :name where tree = :tree", [
                    "tree" => $tree,
                    "name" => $name,
                ]);
            }

            /**
             * @inheritDoc
             */

            public function deleteTree($tree) {
                if (!checkStr($tree)) {
                    return false;
                }

                $leafs = $this->db->get("select * from core_devices_tree");

                $c = 0;

                foreach ($leafs as $leaf) {
                    if (substr($leaf["tree"], 0, strlen($tree)) == $tree) {
                        $c += $this->db->modify("delete from core_devices_tree where tree = :tree", [
                            "tree" => $leaf["tree"],
                        ]);
                    }
                }

                return $c;
            }

            /**
             * @inheritDoc
             */

            public function getTree() {
                return $this->db->get("select * from core_devices_tree");
            }
        }
    }
