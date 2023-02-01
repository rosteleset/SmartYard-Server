<?php

    /**
     * backends frs namespace
     */

    namespace backends\frs
    {
        class internal extends frs
        {
            //private methods
            private function camshotUrl($cam)
            {
                // TODO: create method in a suitable backend
                return $cam[self::CAMERA_URL] . "cgi-bin/images_cgi?channel=0&user=admin&pwd=" . $cam[self::CAMERA_CREDENTIALS];
            }

            private function callback($cam)
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
                if (substr($data[self::P_FACE_IMAGE], 0, 5) === "data:") {
                    if (preg_match_all("/^data\:(.*)\;/i", $image_data, $matches)) {
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
                $data = json_encode($params);

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
                if ($response_code > self::R_CODE_OK && !$response)
                    return ["code" => $response_code];
                else
                    return json_decode($response, true);
            }

            /**
             * @inheritDoc
             */
            public function addStream($cam, $faces = [], $params = [])
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
            public function removeFaces($cam, $faces)
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
            public function attachFaceId($face_id, $flat_id, $house_subscriber_id)
            {
                $query = "
                    select
                        face_id
                    from
                        frs_links_faces
                    where
                        flat_id = :flat_id
                        and house_subscriber_id = :house_subscriber_id
                        and face_id = :face_id
                ";
                $r = $this->db->get($query, [
                    ":flat_id" => $flat_id,
                    ":house_subscriber_id" => $house_subscriber_id,
                    ":face_id" => $face_id,
                ], [], [self::PDO_SINGLIFY]);
                if ($r)
                    return true;

                $query = "
                    insert into frs_links_faces(face_id, flat_id, house_subscriber_id)
                    values(:face_id, :flat_id, :house_subscriber_id)
                ";
                return $this->db->insert($query, [
                    ":face_id" => $face_id,
                    ":house_subscriber_id" => $house_subscriber_id,
                    ":flat_id" => $flat_id,
                ]);
            }

            /**
             * @inheritDoc
             */
            public function detachFaceId($face_id, $house_subscriber_id)
            {
                $query = "select flat_id from frs_links_faces where face_id = :face_id and house_subscriber_id = :house_subscriber_id";
                $r = $this->db->get($query, [
                    ":face_id" => $face_id,
                    ":house_subscriber_id" => $house_subscriber_id],
                    [], [self::PDO_SINGLIFY]);
                if (!$r) {
                    return false;
                }
                $flat_id = $r["flat_id"];
                $query = "delete from frs_links_faces where face_id = :face_id and house_subscriber_id = :house_subscriber_id";
                $r =  $this->db->modify($query, [
                    ":face_id" => $face_id,
                    ":house_subscriber_id" => $house_subscriber_id,
                ]);

                //detach face_id from video streams
                $households = loadBackend("households");
                $entrances = $households->getEntrances("flatId", $flat_id);
                $cameras = loadBackend("cameras");
                foreach ($entrances as $entrance) {
                    $cam = $cameras->getCamera($entrance["cameraId"]);
                    $this->removeFaces($cam, [$face_id]);
                }

                return $r;
            }

            /**
             * @inheritDoc
             */
            public function detachFaceIdFromFlat($face_id, $flat_id)
            {
                $query = "delete from frs_links_faces where face_id = :face_id and flat_id = :flat_id";

                return $this->db->modify($query, [
                    ":face_id" => $face_id,
                    ":flat_id" => $flat_id,
                ]);
            }

            /**
             * @inheritDoc
             */
            public function getEntranceByCameraId($camera_id)
            {
                // TODO: move this to suitable backend
                $query = "select he.house_entrance_id from houses_entrances he where he.camera_id = " . $camera_id;
                $r = $this->db->get($query, [], ["house_entrance_id" => "entranceId"], [self::PDO_SINGLIFY]);
                $households = loadBackend("households");
                return $households->getEntrance($r["entranceId"]);
            }

            /**
             * @inheritDoc
             */
            public function getFlatsByFaceId($face_id, $entrance_id)
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
                        and flf.face_id = :face_id
                ";
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
            public function isLikedFlag($flat_id, $subscriber_id, $face_id, $event_uuid, $is_owner)
            {
                $is_liked1 = false;
                if ($event_uuid !== null) {
                    $query = "select face_id from frs_links_faces where event_uuid = :event_uuid";
                    $r = $this->db->get($query, [":event_uuid" => $event_uuid], [], self::PDO_SINGLIFY);
                    if ($r) {
                        $registered_face_id = $r['face_id'];
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
            public function listFaces($flat_id, $subscriber_id, $is_owner = false)
            {
                $query1 = "
                    select
                      ff.face_id,
                      ff.face_uuid
                    from
                      frs_links_faces lf
                      inner join frs_faces ff
                        on lf.face_id = ff.face_id
                    where
                      lf.flat_id = :flat_id
                    order by
                      ff.face_id
                ";
                $query2 = "
                    select
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
                      ff.face_id
                ";
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
        }
    }
