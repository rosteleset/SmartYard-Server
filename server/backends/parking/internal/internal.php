<?php

    /**
     * backends parking namespace
     */

    namespace backends\parking {

        /**
         * internal.db parking class
         */

        class internal extends parking {

            // lifetime of a stored frame if it is never replaced or removed, seconds
            const FRAME_TTL = 30 * 86400;

            const MAX_FREE_SPACES = 1000;

            // configuration of a camera with the readiness of its current revision
            const PROCESSED_CONFIG = "
                select c.parking_config_id, c.revision, c.enabled, r.ready
                from parking_configs c
                left join parking_config_revisions r on r.parking_config_id = c.parking_config_id and r.revision = c.revision
                where c.camera_id = :camera_id
            ";

            // statuses of failed processing attempts the occupancy service reports
            const FAILURE_STATUSES = [ "cameraUnavailable", "detectionFailed", "stale" ];

            private $failure = false;

            /**
             * @inheritDoc
             */

            public function lastFailure() {
                return $this->failure;
            }

            private function fail($failure, $error) {
                $this->failure = $failure;
                setLastError($error);
                return false;
            }

            /**
             * @inheritDoc
             */

            public function getConfig($cameraId) {
                $this->failure = false;

                if (!checkInt($cameraId)) {
                    return $this->fail("unknown", "noId");
                }

                $configs = $this->db->get("select * from parking_configs where camera_id = :camera_id", [
                    "camera_id" => $cameraId,
                ]);

                if ($configs === false) {
                    return $this->fail("unknown", getLastError());
                }

                if (!count($configs)) {
                    return null;
                }

                $c = $configs[0];

                $areas = $this->db->get("select * from parking_areas where parking_config_id = :parking_config_id order by sort_order, parking_area_id", [
                    "parking_config_id" => $c["parking_config_id"],
                ]);

                if ($areas === false) {
                    return $this->fail("unknown", getLastError());
                }

                $points = $c["calibration_points"] !== null ? json_decode($c["calibration_points"], true) : null;

                $config = [
                    "enabled" => (int)$c["enabled"],
                    "detectionImageSize" => (int)$c["detection_image_size"],
                    "confidenceThreshold" => (float)$c["confidence_threshold"],
                    "processingInterval" => $c["processing_interval"] !== null ? (int)$c["processing_interval"] : null,
                    "frameWidth" => (int)$c["frame_width"],
                    "frameHeight" => (int)$c["frame_height"],
                    "calibration" => $points !== null ? [
                        "model" => $c["calibration_model"],
                        "points" => $points,
                        "checks" => json_decode($c["calibration_checks"], true),
                    ] : null,
                    "areas" => [],
                ];

                foreach ($areas as $area) {
                    $config["areas"][] = [
                        "parkingAreaId" => (int)$area["parking_area_id"],
                        "name" => $area["name"],
                        "enabled" => (int)$area["enabled"],
                        "imagePoints" => json_decode($area["image_points"], true),
                        "baseEdge" => (int)$area["base_edge"],
                        "spaceWidth" => (float)$area["space_width"],
                        "spaceLength" => (float)$area["space_length"],
                        "spaceAngle" => (float)$area["space_angle"],
                        "capacity" => $area["capacity"] !== null ? (int)$area["capacity"] : null,
                    ];
                }

                // problems and readiness are always computed from the stored data
                $analysis = geometry::analyze($config, $this->analyzeOptions());

                $metric = [];
                foreach ($analysis["areas"] as $area) {
                    $metric[$area["parkingAreaId"]] = $area["metricPoints"];
                }

                foreach ($config["areas"] as $i => $area) {
                    $config["areas"][$i]["metricPoints"] = @$metric[$area["parkingAreaId"]];
                }

                $config["homography"] = $c["homography"] !== null ? array_map("floatval", explode(",", trim($c["homography"], "{}"))) : null;
                $config["calibrationError"] = $c["calibration_error"] !== null ? (float)$c["calibration_error"] : null;
                $config["layoutVersion"] = geometry::LAYOUT_VERSION;
                $config["revision"] = (int)$c["revision"];
                $config["updatedAt"] = $c["updated_at"];
                $config["updatedBy"] = $c["updated_by"] !== null ? (int)$c["updated_by"] : null;
                $config["problems"] = $analysis["problems"];
                $config["valid"] = $analysis["valid"];
                $config["ready"] = $analysis["ready"];

                return $config;
            }

            /**
             * @inheritDoc
             */

            public function saveConfig($cameraId, $config) {
                $this->failure = false;

                if (!checkInt($cameraId)) {
                    return $this->fail("unknown", "noId");
                }

                if (!is_array($config) || !array_key_exists("revision", $config) || !is_int($config["revision"]) || $config["revision"] < 0) {
                    return $this->fail("parkingInvalidConfig", "invalidRevision");
                }

                $analysis = geometry::analyze($config, $this->analyzeOptions());

                if (!$analysis["valid"]) {
                    foreach ($analysis["problems"] as $problem) {
                        if ($problem["severity"] == "error") {
                            return $this->fail("parkingInvalidConfig", $problem["code"]);
                        }
                    }
                }

                $ownTransaction = !$this->db->inTransaction();
                if ($ownTransaction) {
                    $this->db->beginTransaction();
                }
                $this->db->exec("SAVEPOINT parking_config");

                try {
                    $statement = $this->db->prepare("select camera_id from cameras where camera_id = ? for key share");
                    $statement->execute([ $cameraId ]);

                    if (!$statement->fetchColumn()) {
                        throw new \RuntimeException("notFound");
                    }

                    $statement = $this->db->prepare("select parking_config_id, revision from parking_configs where camera_id = ? for update");
                    $statement->execute([ $cameraId ]);
                    $current = $statement->fetch(\PDO::FETCH_ASSOC);

                    // the configuration was changed (or deleted) after the client had loaded it
                    if ((int)@$current["revision"] !== $config["revision"]) {
                        throw new \RuntimeException("parkingRevisionConflict");
                    }

                    $revision = $config["revision"] + 1;
                    $uid = (is_int($this->uid) && $this->uid >= 0) ? $this->uid : null;

                    $calibration = $analysis["calibration"];
                    $homography = $analysis["homography"] ? "{" . implode(",", array_map(fn ($v) => sprintf("%.17g", $v), $analysis["homography"])) . "}" : null;

                    $values = [
                        "enabled" => $analysis["enabled"],
                        "detection_image_size" => $analysis["detectionImageSize"],
                        "confidence_threshold" => $analysis["confidenceThreshold"],
                        "processing_interval" => $analysis["processingInterval"],
                        "frame_width" => $analysis["frameWidth"],
                        "frame_height" => $analysis["frameHeight"],
                        "calibration_points" => $calibration ? json_encode($calibration["points"]) : null,
                        "calibration_checks" => json_encode($calibration ? $calibration["checks"] : []),
                        "homography" => $homography,
                        "calibration_error" => $analysis["calibrationError"],
                        "revision" => $revision,
                        "updated_by" => $uid,
                    ];

                    if ($current) {
                        $configId = (int)$current["parking_config_id"];
                        $statement = $this->db->prepare("update parking_configs set enabled = :enabled, detection_image_size = :detection_image_size, confidence_threshold = :confidence_threshold, processing_interval = :processing_interval, frame_width = :frame_width, frame_height = :frame_height, calibration_points = :calibration_points, calibration_checks = :calibration_checks, homography = :homography, calibration_error = :calibration_error, revision = :revision, updated_at = now(), updated_by = :updated_by where parking_config_id = :parking_config_id");
                        $statement->execute($values + [ "parking_config_id" => $configId ]);
                    } else {
                        $statement = $this->db->prepare("insert into parking_configs (camera_id, enabled, detection_image_size, confidence_threshold, processing_interval, frame_width, frame_height, calibration_points, calibration_checks, homography, calibration_error, revision, updated_by) values (:camera_id, :enabled, :detection_image_size, :confidence_threshold, :processing_interval, :frame_width, :frame_height, :calibration_points, :calibration_checks, :homography, :calibration_error, :revision, :updated_by) returning parking_config_id");
                        $statement->execute($values + [ "camera_id" => $cameraId ]);
                        $configId = (int)$statement->fetchColumn();
                    }

                    $statement = $this->db->prepare("select parking_area_id from parking_areas where parking_config_id = ?");
                    $statement->execute([ $configId ]);
                    $existing = array_flip(array_map("intval", $statement->fetchAll(\PDO::FETCH_COLUMN)));

                    $update = $this->db->prepare("update parking_areas set name = :name, sort_order = :sort_order, enabled = :enabled, image_points = :image_points, base_edge = :base_edge, space_width = :space_width, space_length = :space_length, space_angle = :space_angle, capacity = :capacity where parking_area_id = :parking_area_id");
                    $insert = $this->db->prepare("insert into parking_areas (parking_config_id, name, sort_order, enabled, image_points, base_edge, space_width, space_length, space_angle, capacity) values (:parking_config_id, :name, :sort_order, :enabled, :image_points, :base_edge, :space_width, :space_length, :space_angle, :capacity) returning parking_area_id");

                    $kept = [];
                    $areas = [];

                    foreach ($analysis["areas"] as $order => $area) {
                        $values = [
                            "name" => $area["name"],
                            "sort_order" => $order,
                            "enabled" => $area["enabled"],
                            "image_points" => json_encode($area["imagePoints"]),
                            "base_edge" => $area["baseEdge"],
                            "space_width" => $area["spaceWidth"],
                            "space_length" => $area["spaceLength"],
                            "space_angle" => $area["spaceAngle"],
                            "capacity" => $area["capacity"],
                        ];

                        if ($area["parkingAreaId"] !== null) {
                            // an area of another configuration can't be taken over
                            if (!array_key_exists($area["parkingAreaId"], $existing)) {
                                throw new \RuntimeException("invalidArea");
                            }

                            $update->execute($values + [ "parking_area_id" => $area["parkingAreaId"] ]);
                            $area["parkingAreaId"] = (int)$area["parkingAreaId"];
                        } else {
                            $insert->execute($values + [ "parking_config_id" => $configId ]);
                            $area["parkingAreaId"] = (int)$insert->fetchColumn();
                        }

                        $kept[$area["parkingAreaId"]] = true;
                        $areas[] = $area;
                    }

                    $delete = $this->db->prepare("delete from parking_areas where parking_area_id = ?");
                    foreach (array_keys($existing) as $areaId) {
                        if (!isset($kept[$areaId])) {
                            $delete->execute([ $areaId ]);
                        }
                    }

                    $snapshot = [
                        "snapshotVersion" => geometry::SNAPSHOT_VERSION,
                        "layoutVersion" => geometry::LAYOUT_VERSION,
                        "cameraId" => (int)$cameraId,
                        "enabled" => (bool)$analysis["enabled"],
                        "detectionImageSize" => $analysis["detectionImageSize"],
                        "confidenceThreshold" => $analysis["confidenceThreshold"],
                        "processingInterval" => $analysis["processingInterval"],
                        "frame" => [ "width" => $analysis["frameWidth"], "height" => $analysis["frameHeight"] ],
                        "calibration" => $calibration ? [
                            "model" => $calibration["model"],
                            "homography" => $analysis["homography"],
                            "points" => $calibration["points"],
                            "checks" => $calibration["checks"],
                            "error" => $analysis["calibrationError"],
                        ] : null,
                        "areas" => array_map(fn ($area) => [
                            "parkingAreaId" => $area["parkingAreaId"],
                            "name" => $area["name"],
                            "enabled" => (bool)$area["enabled"],
                            "imagePoints" => $area["imagePoints"],
                            "metricPoints" => $area["metricPoints"],
                            "baseEdge" => $area["baseEdge"],
                            "spaceWidth" => $area["spaceWidth"],
                            "spaceLength" => $area["spaceLength"],
                            "spaceAngle" => $area["spaceAngle"],
                            "capacity" => $area["capacity"],
                        ], $areas),
                    ];

                    $statement = $this->db->prepare("insert into parking_config_revisions (parking_config_id, revision, camera_id, snapshot, ready, created_by) values (?, ?, ?, ?, ?, ?)");
                    $statement->execute([ $configId, $revision, $cameraId, json_encode($snapshot), $analysis["ready"] ? 1 : 0, $uid ]);

                    $this->db->exec("RELEASE SAVEPOINT parking_config");
                    if ($ownTransaction) {
                        $this->db->commit();
                    }
                } catch (\Throwable $error) {
                    if ($ownTransaction) {
                        $this->db->rollBack();
                    } else {
                        $this->db->exec("ROLLBACK TO SAVEPOINT parking_config");
                        $this->db->exec("RELEASE SAVEPOINT parking_config");
                    }

                    $message = $error->getMessage();

                    // a concurrent first save of the same camera violates parking_configs_unique_camera
                    if ($error instanceof \PDOException && $error->getCode() == "23505") {
                        return $this->fail("parkingRevisionConflict", "parkingRevisionConflict");
                    }

                    switch ($message) {
                        case "notFound":
                        case "parkingRevisionConflict":
                            return $this->fail($message, $message);

                        case "invalidArea":
                            return $this->fail("parkingInvalidConfig", $message);
                    }

                    error_log(print_r($error, true));

                    return $this->fail("unknown", $message);
                }

                return $this->getConfig($cameraId);
            }

            /**
             * @inheritDoc
             */

            public function deleteConfig($cameraId) {
                $this->failure = false;

                if (!checkInt($cameraId)) {
                    return $this->fail("unknown", "noId");
                }

                $frames = $this->db->get("select frame_uuid from parking_results where camera_id = :camera_id", [ "camera_id" => $cameraId ]);

                if ($this->db->modify("delete from parking_configs where camera_id = :camera_id", [ "camera_id" => $cameraId ]) === false) {
                    return $this->fail("unknown", getLastError());
                }

                foreach ($frames ?: [] as $frame) {
                    $this->deleteFrame($frame["frame_uuid"]);
                }

                return true;
            }

            /**
             * @inheritDoc
             */

            public function camerasToProcess() {
                $this->failure = false;

                $settings = $this->settings();

                $cameras = $this->db->get("
                    select c.camera_id, c.revision, c.processing_interval
                    from parking_configs c
                    join parking_config_revisions r on r.parking_config_id = c.parking_config_id and r.revision = c.revision
                    where c.enabled = 1 and r.ready = 1
                    order by c.camera_id
                ");

                if ($cameras === false) {
                    return $this->fail("unknown", getLastError());
                }

                return array_map(fn ($c) => [
                    "cameraId" => (int)$c["camera_id"],
                    "revision" => (int)$c["revision"],
                    // an override saved before the minimum was raised is not allowed below it
                    "interval" => $c["processing_interval"] !== null ? max((int)$c["processing_interval"], $settings["minInterval"]) : $settings["interval"],
                ], $cameras);
            }

            /**
             * @inheritDoc
             */

            public function currentRevision($cameraId) {
                $this->failure = false;

                if (!checkInt($cameraId)) {
                    return $this->fail("unknown", "noId");
                }

                $revisions = $this->db->get("
                    select r.revision, r.ready, r.snapshot
                    from parking_configs c
                    join parking_config_revisions r on r.parking_config_id = c.parking_config_id and r.revision = c.revision
                    where c.camera_id = :camera_id
                ", [ "camera_id" => $cameraId ]);

                if ($revisions === false) {
                    return $this->fail("unknown", getLastError());
                }

                if (!count($revisions)) {
                    return null;
                }

                return json_decode($revisions[0]["snapshot"], true) + [
                    "revision" => (int)$revisions[0]["revision"],
                    "ready" => (bool)$revisions[0]["ready"],
                ];
            }

            /**
             * @inheritDoc
             */

            public function saveResult($cameraId, $result, $frame) {
                $this->failure = false;

                if (!checkInt($cameraId)) {
                    return $this->fail("unknown", "noId");
                }

                if (!self::validResult($result) || !is_string($frame) || $frame === "") {
                    return $this->fail("parkingInvalidResult", "parkingInvalidResult");
                }

                $files = loadBackend("files");

                if (!$files) {
                    return $this->fail("unknown", "noFilesBackend");
                }

                $current = $this->db->get(self::PROCESSED_CONFIG, [ "camera_id" => $cameraId ]);

                if ($current === false) {
                    return $this->fail("unknown", getLastError());
                }

                // computed with an outdated revision, or the configuration is gone or not processed any more
                // (the service learns about it on the next refresh of its list): not stored, it's not an error
                if (!self::processedWith($current[0] ?? null, $result["revision"])) {
                    return "discarded";
                }

                try {
                    $uuid = $files->addFile("parking_" . $cameraId . ".jpg", $files->contentsToStream($frame), [
                        "contentType" => "image/jpeg",
                        "storage" => $this->storage(),
                        "parkingCameraId" => (int)$cameraId,
                        // a frame lost between upload and commit is removed by the files backend
                        "expire" => time() + self::FRAME_TTL,
                    ]);
                } catch (\Throwable $error) {
                    error_log(print_r($error, true));
                    $this->saveFailed($cameraId, $current[0]["parking_config_id"], $result["revision"], $error->getMessage());

                    return $this->fail("unknown", $error->getMessage());
                }

                $previous = null;

                $ownTransaction = !$this->db->inTransaction();
                if ($ownTransaction) {
                    $this->db->beginTransaction();
                }
                $this->db->exec("SAVEPOINT parking_result");

                try {
                    $statement = $this->db->prepare(self::PROCESSED_CONFIG . " for update of c");
                    $statement->execute([ "camera_id" => $cameraId ]);
                    $config = $statement->fetch(\PDO::FETCH_ASSOC);

                    if (!self::processedWith($config, $result["revision"])) {
                        throw new \RuntimeException("discarded");
                    }

                    $statement = $this->db->prepare("select frame_uuid from parking_results where camera_id = ? for update");
                    $statement->execute([ $cameraId ]);
                    $previous = $statement->fetchColumn() ?: null;

                    $statement = $this->db->prepare("
                        insert into parking_results (camera_id, parking_config_id, revision, processed_at, frame_uuid, frame_width, frame_height, free_spaces)
                        values (:camera_id, :parking_config_id, :revision, now(), :frame_uuid, :frame_width, :frame_height, :free_spaces)
                        on conflict (camera_id) do update set
                            parking_config_id = excluded.parking_config_id, revision = excluded.revision, processed_at = excluded.processed_at,
                            frame_uuid = excluded.frame_uuid, frame_width = excluded.frame_width, frame_height = excluded.frame_height, free_spaces = excluded.free_spaces
                    ");
                    $this->writeStatus($cameraId, $config["parking_config_id"], $result["revision"], "ok", "");

                    $statement->execute([
                        "camera_id" => $cameraId,
                        "parking_config_id" => $config["parking_config_id"],
                        "revision" => $result["revision"],
                        "frame_uuid" => $uuid,
                        "frame_width" => $result["frameWidth"],
                        "frame_height" => $result["frameHeight"],
                        "free_spaces" => json_encode($result["freeSpaces"]),
                    ]);

                    $this->db->exec("RELEASE SAVEPOINT parking_result");
                    if ($ownTransaction) {
                        $this->db->commit();
                    }
                } catch (\Throwable $error) {
                    if ($ownTransaction) {
                        $this->db->rollBack();
                    } else {
                        $this->db->exec("ROLLBACK TO SAVEPOINT parking_result");
                        $this->db->exec("RELEASE SAVEPOINT parking_result");
                    }

                    $this->deleteFrame($uuid);

                    if ($error->getMessage() == "discarded") {
                        return "discarded";
                    }

                    error_log(print_r($error, true));
                    $this->saveFailed($cameraId, $current[0]["parking_config_id"], $result["revision"], $error->getMessage());

                    return $this->fail("unknown", $error->getMessage());
                }

                if ($previous && $previous !== $uuid) {
                    $this->deleteFrame($previous);
                }

                return "accepted";
            }

            /**
             * @inheritDoc
             */

            public function getResult($cameraId, $withFrame = false) {
                $this->failure = false;

                if (!checkInt($cameraId)) {
                    return $this->fail("unknown", "noId");
                }

                $results = $this->db->get("select * from parking_results where camera_id = :camera_id", [ "camera_id" => $cameraId ]);

                if ($results === false) {
                    return $this->fail("unknown", getLastError());
                }

                if (!count($results)) {
                    return null;
                }

                $r = $results[0];

                $result = [
                    "revision" => (int)$r["revision"],
                    "processedAt" => $r["processed_at"],
                    "frameWidth" => (int)$r["frame_width"],
                    "frameHeight" => (int)$r["frame_height"],
                    "freeSpaces" => json_decode($r["free_spaces"], true),
                ];

                if ($withFrame) {
                    $result["frame"] = null;

                    try {
                        $files = loadBackend("files");
                        $stream = $files ? $files->getFileStream($r["frame_uuid"]) : false;

                        if ($stream) {
                            $result["frame"] = base64_encode(stream_get_contents($stream));
                        }
                    } catch (\Throwable $error) {
                        // the frame is gone (e.g. tmpfs after a reboot): the next cycle brings a new one
                    }
                }

                return $result;
            }

            /**
             * @inheritDoc
             */

            public function parkingCameras($cameraIds) {
                $this->failure = false;

                if (!is_array($cameraIds)) {
                    return $this->fail("unknown", "noId");
                }

                $results = $this->parkingResults($cameraIds, false);

                if ($results === false) {
                    return false;
                }

                $cameras = [];

                foreach ($results as $r) {
                    $cameras[(int)$r["camera_id"]] = $this->parkingSummary($r);
                }

                return $cameras;
            }

            /**
             * @inheritDoc
             */

            public function parkingCamera($cameraId) {
                $this->failure = false;

                if (!checkInt($cameraId)) {
                    return $this->fail("unknown", "noId");
                }

                $results = $this->parkingResults([ $cameraId ], true);

                if ($results === false) {
                    return false;
                }

                if (!count($results)) {
                    return null;
                }

                $r = $results[0];
                $width = (int)$r["frame_width"];
                $height = (int)$r["frame_height"];

                $freeSpaces = [];

                foreach (json_decode($r["free_spaces"], true) as $item) {
                    $freeSpaces[$item["parkingAreaId"]] = array_merge($freeSpaces[$item["parkingAreaId"]] ?? [], $item["spaces"]);
                }

                $areas = [];

                // free spaces of an area missing from the revision are left out
                foreach (json_decode($r["areas"], true) as $area) {
                    if (!$area["enabled"]) {
                        continue;
                    }

                    $areas[] = [
                        "name" => $area["name"],
                        "capacity" => (int)$area["capacity"],
                        "points" => array_map(fn ($p) => [ "x" => $p["u"] * $width, "y" => $p["v"] * $height ], $area["imagePoints"]),
                        "freeSpaces" => $freeSpaces[$area["parkingAreaId"]] ?? [],
                    ];
                }

                return $this->parkingSummary($r) + [
                    "frameWidth" => $width,
                    "frameHeight" => $height,
                    "frameId" => $r["frame_uuid"],
                    "areas" => $areas,
                ];
            }

            /**
             * @inheritDoc
             */

            public function parkingFrameCamera($frameId) {
                $this->failure = false;

                if (!is_string($frameId) || !preg_match('/^[0-9A-Za-z-]{1,64}$/', $frameId)) {
                    return null;
                }

                $cameras = $this->db->get("select camera_id from parking_results where frame_uuid = :frame_uuid", [ "frame_uuid" => $frameId ]);

                if ($cameras === false) {
                    return $this->fail("unknown", getLastError());
                }

                if (!count($cameras)) {
                    return null;
                }

                $results = $this->parkingResults([ $cameras[0]["camera_id"] ], false);

                if ($results === false) {
                    return false;
                }

                // the result may have been replaced since the first query
                if (!count($results) || $results[0]["frame_uuid"] !== $frameId) {
                    return null;
                }

                return (int)$results[0]["camera_id"];
            }

            /**
             * latest results of the parking cameras among the given cameras, see parkingCameras()
             *
             * @param array $cameraIds
             * @param bool $detail add the frame size, the free spaces and the areas of the revision of the result
             * @return array|false rows ordered by camera
             */

            private function parkingResults($cameraIds, $detail) {
                $ids = array_values(array_unique(array_map("intval", array_filter($cameraIds, fn ($id) => checkInt($id)))));

                if (!count($ids)) {
                    return [];
                }

                $columns = $detail ? ", pr.frame_width, pr.frame_height, pr.free_spaces, rr.snapshot->'areas' areas" : "";

                // totals come from the revision of the result, which may be older than the current one
                $results = $this->db->get("
                    select
                        pr.camera_id,
                        pr.processed_at,
                        pr.frame_uuid,
                        (select coalesce(sum((a->>'capacity')::integer), 0) from jsonb_array_elements(rr.snapshot->'areas') a where (a->>'enabled')::boolean) capacity,
                        (select coalesce(sum(jsonb_array_length(f->'spaces')), 0) from jsonb_array_elements(pr.free_spaces) f) free
                        $columns
                    from parking_results pr
                    join parking_configs c on c.parking_config_id = pr.parking_config_id and c.camera_id = pr.camera_id
                    join parking_config_revisions cr on cr.parking_config_id = c.parking_config_id and cr.revision = c.revision
                    join parking_config_revisions rr on rr.parking_config_id = pr.parking_config_id and rr.revision = pr.revision
                    where c.enabled = 1 and cr.ready = 1 and pr.camera_id in (" . implode(",", $ids) . ")
                    order by pr.camera_id
                ");

                if ($results === false) {
                    return $this->fail("unknown", getLastError());
                }

                $files = loadBackend("files");

                // a parking camera must be able to show its frame (it is gone e.g. from tmpfs after a reboot)
                return array_values(array_filter($results, function ($r) use ($files) {
                    try {
                        return $files && $files->getFileInfo($r["frame_uuid"]);
                    } catch (\Throwable $error) {
                        return false;
                    }
                }));
            }

            private function parkingSummary($r) {
                return [
                    "capacity" => (int)$r["capacity"],
                    "free" => (int)$r["free"],
                    "processedAt" => date("Y-m-d H:i:s", strtotime($r["processed_at"])),
                ];
            }

            /**
             * @inheritDoc
             */

            public function saveStatus($cameraId, $status, $reason, $revision) {
                $this->failure = false;

                if (!checkInt($cameraId)) {
                    return $this->fail("unknown", "noId");
                }

                if (!in_array($status, self::FAILURE_STATUSES, true) || !is_string($reason) || !is_int($revision)) {
                    return $this->fail("parkingInvalidStatus", "parkingInvalidStatus");
                }

                $configs = $this->db->get("select parking_config_id from parking_configs where camera_id = :camera_id", [ "camera_id" => $cameraId ]);

                if ($configs === false) {
                    return $this->fail("unknown", getLastError());
                }

                // the configuration is gone: nothing to report on
                if (!count($configs)) {
                    return true;
                }

                try {
                    $this->writeStatus($cameraId, $configs[0]["parking_config_id"], $revision, $status, mb_substr($reason, 0, 1024));
                } catch (\Throwable $error) {
                    return $this->fail("unknown", $error->getMessage());
                }

                return true;
            }

            /**
             * @inheritDoc
             */

            public function getStatus($cameraId) {
                $this->failure = false;

                if (!checkInt($cameraId)) {
                    return $this->fail("unknown", "noId");
                }

                $configs = $this->db->get("
                    select c.enabled, c.updated_at, r.ready, s.status, s.reason, s.attempted_at, s.revision
                    from parking_configs c
                    left join parking_config_revisions r on r.parking_config_id = c.parking_config_id and r.revision = c.revision
                    left join parking_statuses s on s.camera_id = c.camera_id
                    where c.camera_id = :camera_id
                ", [ "camera_id" => $cameraId ]);

                if ($configs === false) {
                    return $this->fail("unknown", getLastError());
                }

                if (!count($configs)) {
                    return null;
                }

                $c = $configs[0];

                if (!(int)$c["enabled"] || !(int)$c["ready"]) {
                    return [
                        "status" => "notProcessed",
                        "reason" => (int)$c["enabled"] ? "notReady" : "disabled",
                        "time" => $c["updated_at"],
                        "revision" => null,
                    ];
                }

                if ($c["status"] === null) {
                    return null;
                }

                return [
                    "status" => $c["status"],
                    "reason" => $c["reason"],
                    "time" => $c["attempted_at"],
                    "revision" => (int)$c["revision"],
                ];
            }

            /**
             * @inheritDoc
             */

            public function processNow($cameraId) {
                $this->failure = false;

                if (!checkInt($cameraId)) {
                    return $this->fail("unknown", "noId");
                }

                $url = $this->serviceUrl();

                if ($url === false) {
                    return $this->fail("parkingServiceUnavailable", "parkingServiceNotConfigured");
                }

                $curl = curl_init();

                curl_setopt_array($curl, [
                    CURLOPT_URL => "$url/process/$cameraId",
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => "",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_CONNECTTIMEOUT => 5,
                    CURLOPT_TIMEOUT => self::SERVICE_TIMEOUT,
                ]);

                $body = curl_exec($curl);
                $code = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
                $error = curl_error($curl);

                curl_close($curl);

                if ($code == 404) {
                    return null;
                }

                $outcome = ($code == 200 && is_string($body)) ? json_decode($body, true) : null;

                if (!is_array($outcome) || !is_string(@$outcome["status"])) {
                    return $this->fail("parkingServiceUnavailable", $error ?: "parkingServiceAnswered$code");
                }

                return [
                    "status" => $outcome["status"],
                    "reason" => is_string(@$outcome["reason"]) ? $outcome["reason"] : null,
                ];
            }

            private function writeStatus($cameraId, $configId, $revision, $status, $reason) {
                $statement = $this->db->prepare("
                    insert into parking_statuses (camera_id, parking_config_id, revision, status, reason, attempted_at)
                    values (:camera_id, :parking_config_id, :revision, :status, :reason, now())
                    on conflict (camera_id) do update set
                        parking_config_id = excluded.parking_config_id, revision = excluded.revision, status = excluded.status,
                        reason = excluded.reason, attempted_at = excluded.attempted_at
                ");

                $statement->execute([
                    "camera_id" => $cameraId,
                    "parking_config_id" => $configId,
                    "revision" => $revision,
                    "status" => $status,
                    "reason" => $reason,
                ]);
            }

            // a result computed with $revision is stored only while it is the current revision and it is processed
            private static function processedWith($config, $revision) {
                return $config && (int)$config["revision"] === $revision && (int)$config["enabled"] === 1 && (int)$config["ready"] === 1;
            }

            // tmpfs unless set otherwise, gridfs when the tmpfs backend isn't configured
            private function storage() {
                $storage = @$this->config["backends"]["parking"]["storage"];

                if (in_array($storage, [ "tmpfs", "gridfs" ], true)) {
                    return $storage;
                }

                return loadBackend("tmpfs") ? "tmpfs" : "gridfs";
            }

            // a result that came but couldn't be stored is a failed attempt the administrator has to see
            private function saveFailed($cameraId, $configId, $revision, $reason) {
                try {
                    $this->writeStatus($cameraId, $configId, $revision, "saveFailed", mb_substr((string)$reason, 0, 1024));
                } catch (\Throwable $error) {
                    error_log(print_r($error, true));
                }
            }

            private function deleteFrame($uuid) {
                try {
                    $files = loadBackend("files");

                    if ($files) {
                        $files->deleteFile($uuid);
                    }
                } catch (\Throwable $error) {
                    // an already removed frame
                }
            }

            private static function validResult($result) {
                if (!is_array($result) || !is_int(@$result["revision"]) || $result["revision"] < 1) {
                    return false;
                }

                foreach ([ "frameWidth", "frameHeight" ] as $key) {
                    if (!is_int(@$result[$key]) || $result[$key] < 16 || $result[$key] > 16384) {
                        return false;
                    }
                }

                $areas = @$result["freeSpaces"];

                if (!is_array($areas) || !array_is_list($areas) || count($areas) > geometry::MAX_AREAS) {
                    return false;
                }

                $number = fn ($v) => (is_int($v) || is_float($v)) && is_finite($v);

                foreach ($areas as $area) {
                    if (!is_array($area) || !is_int(@$area["parkingAreaId"]) || !is_array(@$area["spaces"]) || !array_is_list($area["spaces"]) || count($area["spaces"]) > self::MAX_FREE_SPACES) {
                        return false;
                    }

                    foreach ($area["spaces"] as $space) {
                        if (!is_array($space) || !array_is_list($space) || count($space) != 4) {
                            return false;
                        }

                        foreach ($space as $point) {
                            if (!is_array($point) || !$number(@$point["x"]) || !$number(@$point["y"])) {
                                return false;
                            }
                        }
                    }
                }

                return true;
            }
        }
    }
