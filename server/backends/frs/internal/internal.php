<?php

    /**
     * backends frs namespace
     */

    namespace backends\frs
    {

        use Exception;

        class internal extends frs
        {
            const KEY_SYNCING = 'frs_syncing';
            const KEY_FACES = 'frs_ignore_faces';
            const KEY_FACE_UUIDS = 'frs_ignore_face_uuids';

            //private methods
            private function camshotUrl($cam): string
            {
                return $this->config["api"]["internal"] . "/frs/camshot/" . $cam[self::CAMERA_ID];
            }

            private function callback($cam): string
            {
                return $this->config["api"]["internal"] . "/frs/callback?stream_id=" . $cam[self::CAMERA_ID];
            }

            private function addFace($data, $event_uuid)
            {
                $query = "select face_id from frs_faces where face_id = :face_id";
                $r = $this->db->get($query, [":face_id" => $data[self::P_FACE_ID]], [], [self::PDO_SINGLIFY]);
                if ($r)
                    return $data[self::P_FACE_ID];

                $content_type = "image/jpeg";
                $image_data = file_get_contents($data[self::P_FACE_IMAGE]);
                if (str_starts_with($data[self::P_FACE_IMAGE], "data:")) {
                    if (preg_match_all("/^data:(.*);/i", $image_data, $matches)) {
                        $content_type = end($matches[1]);
                    }
                } else {
                    $headers = implode("\n", $http_response_header);
                    if (preg_match_all("/^content-type\s*:\s*(.*)$/mi", $headers, $matches)) {
                        $content_type = end($matches[1]);
                    }
                }
                $files = loadBackend('files');
                $face_uuid = $files->toGUIDv4($files->addFile(
                    "face_image",
                    $files->contentsToStream($image_data),
                    [
                        "contentType" => $content_type,
                        "faceId" => $data[self::P_FACE_ID],
                    ]
                ));
                $query = "insert into frs_faces(face_id, face_uuid, event_uuid) values(:face_id, :face_uuid, :event_uuid)";
                $this->db->insert($query, [
                    ":face_id" => $data[self::P_FACE_ID],
                    ":face_uuid" => $face_uuid,
                    ":event_uuid" => $event_uuid,
                ]);

                $this->ignoreSyncingFaceId($data[self::P_FACE_ID]);
                $this->ignoreSyncingFaceUuid($face_uuid);

                return $data[self::P_FACE_ID];
            }

            /**
             * @inheritDoc
             */
            public function servers()
            {
                return $this->config["backends"]["frs"]["servers"];
            }

            //FRS API methods calls

            /**
             * @inheritDoc
             */
            public function apiCall($base_url, $method, $params)
            {
                $l = strlen($base_url);
                if ($l <= 1)
                    return false;

                if ($base_url[$l - 1] !== "/")
                    $base_url .= "/";
                $l = strlen($method);
                if ($l > 0 && $method[0] === "/")
                    $method = substr($method, 1);
                $api_url = $base_url . "api/" . $method;
                $curl = curl_init();
                if ($params)
                    $data = json_encode($params);
                else
                    $data = "";
                $options = [
                    CURLOPT_URL => $api_url,
                    CURLOPT_POST => 1,
                    CURLOPT_POSTFIELDS=> $data,
                    CURLOPT_RETURNTRANSFER => 1,
                    CURLOPT_HTTPHEADER => ['Expect:', 'Accept: application/json', 'Content-Type: application/json']
                ];
                curl_setopt_array($curl, $options);
                $response = curl_exec($curl);
                $response_code = curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
                curl_close($curl);
                if ($response_code == 0 || $response_code >= 204) {
                    return [self::P_CODE => $response_code > 0 ? $response_code : 500];
                } else {
                    return json_decode($response, true);
                }
            }

            /**
             * @inheritDoc
             */
            public function addStream($cam, array $faces = [], array $params = [])
            {
                $method_params = [
                    self::P_STREAM_ID => $cam[self::CAMERA_ID],
                    self::P_URL => $this->camshotUrl($cam),
                    self::P_CALLBACK_URL => $this->callback($cam)
                ];
                if ($faces)
                    $method_params[self::P_FACE_IDS] = $faces;
                if ($params)
                    $method_params[self::P_PARAMS] = $params;

                return $this->apiCall($cam[self::CAMERA_FRS], self::M_ADD_STREAM, $method_params);
            }

            /**
             * @inheritDoc
             */
            public function bestQualityByDate($cam, $date, $event_uuid = "")
            {
                $method_params = [
                    self::P_STREAM_ID => $cam[self::CAMERA_ID],
                    self::P_DATE => date('Y-m-d H:i:s', $date)
                ];
                if ($event_uuid)
                    $method_params[self::P_EVENT_UUID] = $event_uuid;

                return $this->apiCall($cam[self::CAMERA_FRS], self::M_BEST_QUALITY, $method_params);
            }

            /**
             * @inheritDoc
             */
            public function bestQualityByEventId($cam, $event_id, $event_uuid = "")
            {
                $method_params = [
                    self::P_STREAM_ID => $cam[self::CAMERA_ID],
                    self::P_EVENT_ID => $event_id
                ];
                if ($event_uuid)
                    $method_params[self::P_EVENT_UUID] = $event_uuid;

                return $this->apiCall($cam[self::CAMERA_FRS], self::M_BEST_QUALITY, $method_params);
            }

            /**
             * @inheritDoc
             */
            public function registerFace($cam, $event_uuid, $left = 0, $top = 0, $width = 0, $height = 0)
            {
                $plog = loadBackend("plog");
                if (!$plog)
                    return false;

                $event_data = $plog->getEventDetails($event_uuid);
                if (!$event_data)
                    return false;

                $image_url =$this->config["api"]["mobile"] . "/address/plogCamshot/" . $event_data['image_uuid'];
                $method_params = [
                    self::P_STREAM_ID => $cam[self::CAMERA_ID],
                    self::P_URL => $image_url
                ];
                if ($width > 0 && $height > 0) {
                    $method_params[self::P_FACE_LEFT] = $left;
                    $method_params[self::P_FACE_TOP] = $top;
                    $method_params[self::P_FACE_WIDTH] = $width;
                    $method_params[self::P_FACE_HEIGHT] = $height;
                }

                $response = $this->apiCall($cam[self::CAMERA_FRS], self::M_REGISTER_FACE, $method_params);
                if ($response && $response[self::P_CODE] == self::R_CODE_OK && $response[self::P_DATA]) {
                    return [
                        self::P_FACE_ID => $this->addFace($response[self::P_DATA], $event_uuid)
                    ];
                }

                return $response;
            }

            /**
             * @inheritDoc
             */
            public function removeFaces($cam, array $faces)
            {
                $method_params = [
                    self::P_STREAM_ID => $cam[self::CAMERA_ID],
                    self::P_FACE_IDS => $faces
                ];

                return $this->apiCall($cam[self::CAMERA_FRS], self::M_REMOVE_FACES, $method_params);
            }

            /**
             * @inheritDoc
             */
            public function motionDetection($cam, bool $is_start)
            {
                $method_params = [
                    self::P_STREAM_ID => $cam[self::CAMERA_ID],
                    self::P_START => $is_start ? "t" : "f"
                ];

                return $this->apiCall($cam[self::CAMERA_FRS], self::M_MOTION_DETECTION, $method_params);
            }

            //RBT methods

            /**
             * @inheritDoc
             */
            public function cron($part): bool
            {
                $result = true;
                if ($part === @$this->config['backends']['frs']['cron_sync_data_scheduler']) {
                    if (!$this->startSyncing())
                        return false;
                    try {
                        $result = $this->syncData();
                    } catch (Exception $e) {
                        error_log(print_r($e, true));
                        $result = false;
                    }
                    if (!$this->stopSyncing())
                        return false;
                }
                return $result;
            }

            private function deleteFaceId($face_id): void
            {
                $query = "delete from frs_links_faces where face_id = :face_id";
                $this->db->modify($query, [":face_id" => $face_id]);

                $query = "select face_uuid from frs_faces where face_id = :face_id";
                $r = $this->db->get($query, [":face_id" => $face_id], [], [self::PDO_SINGLIFY]);
                if ($r) {
                    $files = loadBackend("files");
                    $files->deleteFile($files->fromGUIDv4($r["face_uuid"]));
                    $this->ignoreSyncingFaceUuid($r["face_uuid"]);
                }

                $query = "delete from frs_faces where face_id = :face_id";
                if ($this->db->modify($query, [":face_id" => $face_id]) !== false) {
                    $this->ignoreSyncingFaceId($face_id);
                }
            }

            private function startSyncing(): bool
            {
                try {
                    $this->redis->sAdd(self::KEY_SYNCING, 1);
                    return true;
                } catch (Exception $e) {
                    error_log(print_r($e, true));
                }

                return false;
            }

            private function stopSyncing(): bool
            {
                try {
                    $this->redis->del(self::KEY_SYNCING);
                    $this->redis->del(self::KEY_FACES);
                    $this->redis->del(self::KEY_FACE_UUIDS);
                    return true;
                } catch (Exception $e) {
                    error_log(print_r($e, true));
                }

                return false;
            }

            private function isSyncing(): bool
            {
                try {
                    return ($this->redis->exists(self::KEY_SYNCING) === 1);
                } catch (Exception $e) {
                    error_log(print_r($e, true));
                }

                return false;
            }

            private function ignoreSyncingFaceId($face_id): void
            {
                if ($this->isSyncing()) {
                    try {
                        $this->redis->sAdd(self::KEY_FACES, $face_id);
                    } catch (Exception $e) {
                        error_log(print_r($e, true));
                    }
                }
            }

            private function ignoreSyncingFaceUuid($face_uuid): void
            {
                if ($this->isSyncing()) {
                    try {
                        $this->redis->sAdd(self::KEY_FACE_UUIDS, $face_uuid);
                    } catch (Exception $e) {
                        error_log(print_r($e, true));
                    }
                }
            }

            private function checkIgnoredSyncingFace($face_id): bool
            {
                $result = false;
                try {
                    $result = $this->redis->sIsMember(self::KEY_FACES, $face_id);
                } catch (Exception $e) {
                    error_log(print_r($e, true));
                }

                return $result;
            }

            private function getIgnoredSyncingFaces()
            {
                $result = [];
                try {
                    $result = $this->redis->sMembers(self::KEY_FACES);
                } catch (Exception $e) {
                    error_log(print_r($e, true));
                }

                return $result;
            }

            private function getIgnoredSyncingFaceUuids()
            {
                $result = [];
                try {
                    $result = $this->redis->sMembers(self::KEY_FACE_UUIDS);
                } catch (Exception $e) {
                    error_log(print_r($e, true));
                }

                return $result;
            }

            private function syncData(): bool
            {
                if (!is_array($this->servers())) {
                    return false;
                }

                //syncing all faces
                $frs_all_faces = [];
                foreach ($this->servers() as $frs_server) {
                    $all_faces = $this->apiCall($frs_server[self::FRS_BASE_URL], self::M_LIST_ALL_FACES, null);
                    if ($all_faces[self::P_CODE] > 204)
                        return false;
                    if ($all_faces && array_key_exists(self::P_DATA, $all_faces)) {
                        $frs_all_faces = array_merge($frs_all_faces, $all_faces[self::P_DATA]);
                    }
                }

                $query = "select face_id, face_uuid from frs_faces order by 1";
                $result = $this->db->get($query, [], []);
                if ($result === false)
                    return false;

                $rbt_all_faces = [];
                $rbt_all_face_uuids = [];
                foreach ($result as $row) {
                    $rbt_all_faces[] = $row["face_id"];
                    $rbt_all_face_uuids[] = $row["face_uuid"];
                }

                //delete face photos from files backend if they don't exist in frs backend
                $files = loadBackend("files");
                if ($files) {
                    $face_uuids = array_map(function ($item) use($files) {
                        return $files->toGUIDv4($item['id']);
                    }, $files->searchFiles(["filename" => "face_image"]));
                    $diff_faces_photo = array_diff($face_uuids, $rbt_all_face_uuids, $this->getIgnoredSyncingFaceUuids());
                    foreach ($diff_faces_photo as $face_uuid) {
                        $files->deleteFile($files->fromGUIDv4($face_uuid));
                    }
                }

                //delete unmatched faces in RBT
                $diff_faces = array_diff($rbt_all_faces, $frs_all_faces, $this->getIgnoredSyncingFaces());
                if ($diff_faces) {
                    $query = "delete from frs_links_faces where face_id in (" . implode(",", $diff_faces) . ")";
                    if ($this->db->modify($query) === false)
                        return false;

                    foreach ($diff_faces as $f_id) {
                        $query = "select face_uuid from frs_faces where face_id = :face_id";
                        $r = $this->db->get($query, [":face_id" => $f_id], [], [self::PDO_SINGLIFY]);
                        if ($r && $files) {
                            $files->deleteFile($files->fromGUIDv4($r["face_uuid"]));
                        }
                    }

                    $query = "delete from frs_faces where face_id in (" . implode(",", $diff_faces) . ")";
                    if ($this->db->modify($query) === false)
                        return false;
                }

                //delete unmatched faces in FRS
                $diff_faces = array_diff($frs_all_faces, $rbt_all_faces, $this->getIgnoredSyncingFaces());
                if ($diff_faces) {
                    foreach ($this->servers() as $frs_server) {
                        $this->apiCall($frs_server[self::FRS_BASE_URL], self::M_DELETE_FACES, [self::P_FACE_IDS => $diff_faces]);
                    }
                }

                $frs_all_data = [];
                foreach ($this->servers() as $frs_server) {
                    $frs_all_data[$frs_server[self::FRS_BASE_URL]] = [];
                    $streams = $this->apiCall($frs_server[self::FRS_BASE_URL], self::M_LIST_STREAMS, null);
                    if ($streams[self::P_CODE] > 204)
                        return false;
                    if ($streams && isset($streams[self::P_DATA]) && is_array($streams[self::P_DATA]))
                        foreach ($streams[self::P_DATA] as $item)
                        {
                            if (array_key_exists(self::P_FACE_IDS, $item))
                                $frs_all_data[$frs_server[self::FRS_BASE_URL]][$item[self::P_STREAM_ID]] = $item[self::P_FACE_IDS];
                            else
                                $frs_all_data[$frs_server[self::FRS_BASE_URL]][$item[self::P_STREAM_ID]] = [];
                        }
                }

                $rbt_all_data = [];
                $query = "
                    select
                      c.frs,
                      c.camera_id
                    from
                      cameras c
                    where
                      length(c.frs) > 1
                    order by
                      1, 2";
                $rbt_data = $this->db->get($query);
                if ($rbt_data === false)
                    return false;

                if (is_array($rbt_data))
                    foreach ($rbt_data as $item) {
                        $frs_base_url = $item['frs'];
                        $stream_id = $item['camera_id'];
                        $rbt_all_data[$frs_base_url][$stream_id] = [];
                    }

                $query = "
                    select distinct
                      c.frs,
                      c.camera_id,
                      flf.face_id,
                      ff.face_uuid
                    from
                      frs_links_faces flf
                      left join frs_faces ff
                        on flf.face_id = ff.face_id
                      inner join houses_entrances_flats hef
                        on hef.house_flat_id = flf.flat_id
                      inner join houses_entrances he
                        on hef.house_entrance_id = he.house_entrance_id
                      inner join cameras c
                        on he.camera_id = c.camera_id
                    where
                       length(c.frs) > 1
                    order by
                      1, 2, 3";
                $rbt_data = $this->db->get($query);
                if ($rbt_data === false)
                    return false;

                if (is_array($rbt_data))
                    foreach ($rbt_data as $item) {
                        $face_id = $item['face_id'];
                        if ($this->checkIgnoredSyncingFace($face_id))
                            continue;

                        $frs_base_url = $item['frs'];
                        $stream_id = $item['camera_id'];
                        $face_uuid = $item['face_uuid'];

                        if ($face_uuid === null) {
                            //face image doesn't exist in RBT, so delete it everywhere
                            $this->deleteFaceId($face_id);
                            $this->apiCall($frs_base_url, self::M_DELETE_FACES, [self::P_FACE_IDS => [$face_id]]);
                        } else {
                            $rbt_all_data[$frs_base_url][$stream_id][] = $face_id;
                        }
                    }

                foreach ($rbt_all_data as $base_url => $data) {

                    //syncing video streams
                    $diff_streams = array_diff_key($data, $frs_all_data[$base_url]);
                    foreach ($diff_streams as $stream_id => $faces) {
                        $cam = loadBackend("cameras")->getCamera($stream_id);
                        if ($cam) {
                            $method_params = [
                                self::P_STREAM_ID => $stream_id,
                                self::P_URL => $this->camshotUrl($cam),
                                self::P_CALLBACK_URL => $this->callback($cam)
                            ];
                            if ($faces) {
                                $method_params[self::P_FACE_IDS] = $faces;
                            }
                            $this->apiCall($base_url, self::M_ADD_STREAM, $method_params);
                        }

                    }

                    $diff_streams = array_diff_key($frs_all_data[$base_url], $data);
                    foreach (array_keys($diff_streams) as $stream_id) {
                        $this->apiCall($base_url, self::M_REMOVE_STREAM, [self::P_STREAM_ID => $stream_id]);
                    }

                    //syncing faces by video streams
                    $common_streams = array_intersect_key($data, $frs_all_data[$base_url]);
                    foreach ($common_streams as $stream_id => $rbt_faces) {
                        $diff_faces = array_diff($rbt_faces, $frs_all_data[$base_url][$stream_id], $this->getIgnoredSyncingFaces());
                        if ($diff_faces) {
                            $this->apiCall($base_url, self::M_ADD_FACES, [self::P_STREAM_ID => $stream_id, self::P_FACE_IDS => $diff_faces]);
                        }

                        $diff_faces = array_diff($frs_all_data[$base_url][$stream_id], $rbt_faces, $this->getIgnoredSyncingFaces());
                        if ($diff_faces)
                            $this->apiCall($base_url, self::M_REMOVE_FACES, [self::P_STREAM_ID => $stream_id, self::P_FACE_IDS => $diff_faces]);
                    }
                }

                //delete all unattached faces in RBT
                $query = "select f.face_id from frs_faces f where f.face_id not in (select fl.face_id from frs_links_faces fl)";
                $result = $this->db->get($query, [], []);
                if ($result === false)
                    return false;

                foreach ($result as $row) {
                    $face_id = $row["face_id"];
                    if ($this->checkIgnoredSyncingFace($face_id))
                        continue;

                    $this->deleteFaceId($face_id);
                }

                return true;
            }

            /**
             * @inheritDoc
             */
            public function attachFaceId($face_id, $flat_id, $house_subscriber_id): bool
            {
                $query = "
                    select
                        face_id
                    from
                        frs_links_faces
                    where
                        flat_id = :flat_id
                        and house_subscriber_id = :house_subscriber_id
                        and face_id = :face_id";
                $r = $this->db->get($query, [
                    ":flat_id" => $flat_id,
                    ":house_subscriber_id" => $house_subscriber_id,
                    ":face_id" => $face_id,
                ], [], [self::PDO_SINGLIFY]);
                if ($r)
                    return true;

                $query = "
                    insert into frs_links_faces(face_id, flat_id, house_subscriber_id)
                    values(:face_id, :flat_id, :house_subscriber_id)";
                $r = $this->db->insert($query, [
                    ":face_id" => $face_id,
                    ":house_subscriber_id" => $house_subscriber_id,
                    ":flat_id" => $flat_id,
                ]);

                if ($r !== false) {
                    $this->ignoreSyncingFaceId($face_id);
                }

                if ($r === false)
                    return false;

                return true;
            }

            /**
             * @inheritDoc
             */
            public function detachFaceId($face_id, $house_subscriber_id): bool
            {
                $query = "select flat_id from frs_links_faces where face_id = :face_id and house_subscriber_id = :house_subscriber_id";
                $r = $this->db->get($query, [
                    ":face_id" => $face_id,
                    ":house_subscriber_id" => $house_subscriber_id],
                    [], [self::PDO_SINGLIFY]);
                if (!$r) {
                    return false;
                }

                $query = "delete from frs_links_faces where face_id = :face_id and house_subscriber_id = :house_subscriber_id";
                $r =  $this->db->modify($query, [
                    ":face_id" => $face_id,
                    ":house_subscriber_id" => $house_subscriber_id,
                ]);

                if ($r !== false) {
                    $this->ignoreSyncingFaceId($face_id);
                }

                if ($r === false)
                    return false;

                return true;
            }

            /**
             * @inheritDoc
             */
            public function detachFaceIdFromFlat($face_id, $flat_id): bool
            {
                $query = "delete from frs_links_faces where face_id = :face_id and flat_id = :flat_id";
                $r = $this->db->modify($query, [
                    ":face_id" => $face_id,
                    ":flat_id" => $flat_id,
                ]);

                if ($r !== false) {
                    $this->ignoreSyncingFaceId($face_id);
                }

                if ($r === false)
                    return false;

                return true;
            }

            /**
             * @inheritDoc
             */
            public function getFlatsByFaceId($face_id, $entrance_id): array
            {
                // TODO: perhaps some of this data should be retrieved from households backend
                $query = "
                    select
                        flf.flat_id
                    from
                        houses_entrances_flats hef
                        inner join frs_links_faces flf
                            on hef.house_flat_id = flf.flat_id
                    where
                        hef.house_entrance_id = :entrance_id
                        and flf.face_id = :face_id";
                $r = $this->db->get($query, [
                    ":entrance_id" => $entrance_id,
                    ":face_id" => $face_id,
                ], [
                    "flat_id" => "flatId",
                ]);

                $result = [];
                if ($r) {
                    foreach ($r as $row) {
                        $result[] = $row["flatId"];
                    }
                }

                return $result;
            }

            /**
             * @inheritDoc
             */
            public function isLikedFlag($flat_id, $subscriber_id, $face_id, $event_uuid, $is_owner): bool
            {
                $is_liked1 = false;
                if ($event_uuid !== null) {
                    $query = "select face_id from frs_faces where event_uuid = :event_uuid";
                    $r = $this->db->get($query, [":event_uuid" => $event_uuid], [], [self::PDO_SINGLIFY]);
                    if ($r) {
                        $registered_face_id = $r["face_id"];
                        $query = "select face_id from frs_links_faces where flat_id = ". $flat_id . " and face_id = ". $registered_face_id;
                        if (!$is_owner) {
                            $query .= " and house_subscriber_id = " . $subscriber_id;
                        }
                        $is_liked1 = count($this->db->get($query)) > 0;
                    }
                }
                $is_liked2 = false;
                if ($face_id !== null) {
                    $query = "select face_id from frs_links_faces where flat_id = ". $flat_id . " and face_id = ". $face_id;
                    if (!$is_owner) {
                        $query .= " and house_subscriber_id = " . $subscriber_id;
                    }
                    $is_liked2 = count($this->db->get($query)) > 0;
                }

                return $is_owner && $is_liked1 || $is_liked2;
            }

            /**
             * @inheritDoc
             */
            public function listFaces($flat_id, $subscriber_id, $is_owner = false): array
            {
                $query1 = "
                    select distinct
                      ff.face_id,
                      ff.face_uuid
                    from
                      frs_links_faces lf
                      inner join frs_faces ff
                        on lf.face_id = ff.face_id
                    where
                      lf.flat_id = :flat_id
                    order by
                      ff.face_id";
                $query2 = "
                    select distinct
                      ff.face_id,
                      ff.face_uuid
                    from
                      frs_links_faces lf
                      inner join frs_faces ff
                        on lf.face_id = ff.face_id
                    where
                      lf.flat_id = :flat_id
                      and lf.house_subscriber_id = :subscriber_id
                    order by
                      ff.face_id";
                if ($is_owner) {
                    $query = $query1;
                    $r = $this->db->get($query, [":flat_id" => $flat_id], []);
                } else {
                    $query = $query2;
                    $r = $this->db->get($query, [":flat_id" => $flat_id, ":subscriber_id" => $subscriber_id], []);
                }

                $list_faces = [];
                foreach ($r as $row) {
                    $list_faces[] = [self::P_FACE_ID => $row['face_id'], self::P_FACE_IMAGE => $row['face_uuid']];
                }

                return $list_faces;
            }

            /**
             * @inheritDoc
             */
            public function getRegisteredFaceId($event_uuid)
            {
                $query = "select face_id from frs_faces where event_uuid = :event_uuid";
                $r = $this->db->get($query, [":event_uuid" => $event_uuid], [], [self::PDO_SINGLIFY]);
                if ($r)
                    return $r["face_id"];

                return false;
            }
        }
    }
