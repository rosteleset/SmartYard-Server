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

            private function addFace($data)
            {
                $query = "select face_id from frs_faces where face_id = :face_id";
                $r = $this->db->get($query, [":face_id" => $data[self::P_FACE_ID]], [], [self::PDO_SINGLIFY]);
                if ($r)
                    return $data[self::P_FACE_ID];

                $image_data = $data[self::P_FACE_IMAGE];
                $files = loadBackend('files');
                $face_uuid = $files->toGUIDv4($files->addFile(
                    "face_image",
                    $files->contentsToStream($image_data),
                    [
                        "contentType" => "image/jpeg",
                        "faceId" => $data[self::P_FACE_ID],
                    ]
                ));
                $query = "insert into frs_faces(face_id, face_uuid) values(:face_id, :face_uuid)";
                $this->db->insert($query, [
                    ":face_id" => $data[self::P_FACE_ID],
                    ":face_uuid" => $face_uuid
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
            public function apiCall($baseUrl, $method, $params)
            {
                $l = strlen($baseUrl);
                if ($l <= 1)
                    return false;

                if ($baseUrl[$l - 1] !== "/")
                    $baseUrl .= "/";
                $l = strlen($method);
                if ($l > 0 && $method[0] === "/")
                    $method = substr($method, 1);
                $api_url = $baseUrl . "api/" . $method;

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
            public function bestQualityByDate($cam, $date, $eventUuid = "")
            {
                $method_params = [
                    self::P_STREAM_ID => $cam[self::CAMERA_ID],
                    self::P_DATE => date('Y-m-d H:i:s', $date)
                ];
                if ($eventUuid)
                    $method_params[self::P_EVENT_UUID] = $eventUuid;

                return $this->apiCall($cam[self::CAMERA_FRS], self::M_BEST_QUALITY, $method_params);
            }

            /**
             * @inheritDoc
             */
            public function bestQualityByEventId($cam, $eventId, $eventUuid = "")
            {
                $method_params = [
                    self::P_STREAM_ID => $cam[self::CAMERA_ID],
                    self::P_EVENT_ID => $eventId
                ];
                if ($eventUuid)
                    $method_params[self::P_EVENT_UUID] = $eventUuid;

                return $this->apiCall($cam[self::CAMERA_FRS], self::M_BEST_QUALITY, $method_params);
            }

            /**
             * @inheritDoc
             */
            public function registerFace($cam, $eventUuid, $left = 0, $top = 0, $width = 0, $height = 0)
            {
                $plog = loadBackend("plog");
                if (!$plog)
                    return false;

                $event_data = $plog->getEventDetails($eventUuid);
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
                        self::P_FACE_ID => $this->addFace($response[self::P_DATA])
                    ];
                }

                return $response;
            }

            /**
             * @inheritDoc
             */
            public function motionDetection($cam, bool $isStart)
            {
                $method_params = [
                    self::P_STREAM_ID => $cam[self::CAMERA_ID],
                    self::P_START => $isStart ? "t" : "f"
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
                // TODO: Implement detachFaceId() method.
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

            public function isLikedFlag($flat_id, $subscriber_id, $face_id, $is_owner)
            {
                $query = "select face_id from frs_links_faces where flat_id = ". $flat_id . " and face_id = ". $face_id;
                if (!$is_owner) {
                    $query .= " and house_subscriber_id = " . $subscriber_id;
                }
                return count($this->db->get($query)) > 0;
            }
        }
    }
