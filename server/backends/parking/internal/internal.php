<?php

    /**
     * backends parking namespace
     */

    namespace backends\parking {

        /**
         * internal.db parking class
         */

        class internal extends parking {

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
                $analysis = geometry::analyze($config);

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

                $analysis = geometry::analyze($config);

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
                        $statement = $this->db->prepare("update parking_configs set enabled = :enabled, frame_width = :frame_width, frame_height = :frame_height, calibration_points = :calibration_points, calibration_checks = :calibration_checks, homography = :homography, calibration_error = :calibration_error, revision = :revision, updated_at = now(), updated_by = :updated_by where parking_config_id = :parking_config_id");
                        $statement->execute($values + [ "parking_config_id" => $configId ]);
                    } else {
                        $statement = $this->db->prepare("insert into parking_configs (camera_id, enabled, frame_width, frame_height, calibration_points, calibration_checks, homography, calibration_error, revision, updated_by) values (:camera_id, :enabled, :frame_width, :frame_height, :calibration_points, :calibration_checks, :homography, :calibration_error, :revision, :updated_by) returning parking_config_id");
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

                if ($this->db->modify("delete from parking_configs where camera_id = :camera_id", [ "camera_id" => $cameraId ]) === false) {
                    return $this->fail("unknown", getLastError());
                }

                return true;
            }
        }
    }
