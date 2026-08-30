<?php

    /**
     * @api {get} /api/plog/events get event log for flat and day
     *
     * @apiVersion 1.0.0
     *
     * @apiName getPlogEvents
     * @apiGroup plog
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiQuery {Number} flatId
     * @apiQuery {String} day date Y-m-d
     *
     * @apiSuccess {Object[]} events
     */

    namespace api\plog {

        use api\api;
        use backends\plog\plog;

        class events extends api {

            private static function normalizeRfId(string $rfid): string {
                $rfid = strtoupper(preg_replace('/[:-]|\s+/', '', trim($rfid)));

                if ($rfid === "") {
                    return str_repeat("0", 14);
                }

                return str_pad($rfid, 14, "0", STR_PAD_LEFT);
            }

            public static function GET($params) {
                $flat_id = (int)@$params["flatId"];
                $entrance_id = (int)@$params["entranceId"];
                $house_id = (int)@$params["houseId"];
                $day = trim(@$params["day"]);
                $scope_count = ($flat_id ? 1 : 0) + ($entrance_id ? 1 : 0) + ($house_id ? 1 : 0);

                if ($scope_count !== 1 || !$day) {
                    return api::ANSWER(false, "badRequest");
                }

                $households = loadBackend("households");
                if (!$households) {
                    return api::ANSWER(false, "notFound");
                }

                if ($flat_id) {
                    if (!$households->getFlat($flat_id)) {
                        return api::ANSWER(false, "notFound");
                    }
                } elseif ($entrance_id) {
                    if (!$households->getEntrance($entrance_id)) {
                        return api::ANSWER(false, "notFound");
                    }
                } else {
                    $addresses = loadBackend("addresses");
                    if (!$addresses || !$addresses->getHouse($house_id)) {
                        return api::ANSWER(false, "notFound");
                    }
                }

                $plog = loadBackend("plog");
                if (!$plog) {
                    return api::ANSWER(false, "notAcceptable");
                }

                try {
                    $date = date("Ymd", strtotime($day));
                    if ($flat_id) {
                        $result = $plog->getDetailEventsByDay($flat_id, $date);
                    } elseif ($entrance_id) {
                        $result = $plog->getDetailEventsByDayAndEntrance($entrance_id, $date);
                    } else {
                        $result = $plog->getDetailEventsByDayAndHouse($house_id, $date);
                    }
                    $events = self::formatEvents($result ?: [], $params);
                } catch (\Throwable $e) {
                    error_log(print_r($e, true));
                    return api::ANSWER(false, "internal");
                }

                return api::ANSWER($events, "events");
            }

            private static function formatEvents(array $result, array $params = []): array {
                $events = [];
                $rfid_filter = trim((string)@$params["rfId"]);
                if ($rfid_filter !== "") {
                    $rfid_filter = self::normalizeRfId($rfid_filter);
                }

                foreach ($result as $row) {
                    if ($rfid_filter !== "") {
                        if ((int)$row[plog::COLUMN_EVENT] !== plog::EVENT_OPENED_BY_KEY) {
                            continue;
                        }

                        if (self::normalizeRfId((string)$row[plog::COLUMN_RFID]) !== $rfid_filter) {
                            continue;
                        }
                    }

                    $domophone = json_decode($row[plog::COLUMN_DOMOPHONE]);
                    if (!isset($domophone->domophone_id) || !isset($domophone->domophone_output)) {
                        continue;
                    }

                    $event = [
                        "date" => date("Y-m-d H:i:s", $row[plog::COLUMN_DATE]),
                        "uuid" => $row[plog::COLUMN_EVENT_UUID],
                        "previewType" => $row[plog::COLUMN_PREVIEW],
                        "flatId" => strval((int)$row[plog::COLUMN_FLAT_ID]),
                        "objectId" => strval($domophone->domophone_id),
                        "objectType" => "0",
                        "objectMechanizma" => strval($domophone->domophone_output),
                        "mechanizmaDescription" => (isset($domophone->domophone_description) && $domophone->domophone_description !== false)
                            ? $domophone->domophone_description
                            : "",
                        "event" => strval((int)$row[plog::COLUMN_EVENT]),
                        "detailX" => [],
                    ];

                    if (isset($domophone->house_id) && $domophone->house_id > 0) {
                        $event["houseId"] = $domophone->house_id;
                    }
                    if (isset($domophone->entrance_id) && $domophone->entrance_id > 0) {
                        $event["entranceId"] = $domophone->entrance_id;
                    }
                    if (isset($domophone->camera_id) && $domophone->camera_id > 0) {
                        $event["cameraId"] = $domophone->camera_id;
                    }

                    $face = json_decode($row[plog::COLUMN_FACE], false);
                    if (isset($face->width) && $face->width > 0 && isset($face->height) && $face->height > 0) {
                        $event["detailX"]["face"] = [
                            "left" => strval($face->left),
                            "top" => strval($face->top),
                            "width" => strval($face->width),
                            "height" => strval($face->height),
                        ];
                    }
                    if (isset($face->faceId) && $face->faceId > 0) {
                        $event["detailX"]["faceId"] = strval($face->faceId);
                    }

                    $phones = json_decode($row[plog::COLUMN_PHONES]);
                    $event_type = (int)$row[plog::COLUMN_EVENT];

                    switch ($event_type) {
                        case plog::EVENT_UNANSWERED_CALL:
                        case plog::EVENT_ANSWERED_CALL:
                            $event["detailX"]["opened"] = ($row[plog::COLUMN_OPENED] == 1) ? "t" : "f";
                            break;

                        case plog::EVENT_OPENED_BY_KEY:
                            $event["detailX"]["key"] = strval($row[plog::COLUMN_RFID]);
                            break;

                        case plog::EVENT_OPENED_BY_APP:
                            if ($phones && $phones->user_phone) {
                                $event["detailX"]["phone"] = strval($phones->user_phone);
                            }
                            break;

                        case plog::EVENT_OPENED_BY_CODE:
                            $event["detailX"]["code"] = strval($row[plog::COLUMN_CODE]);
                            break;

                        case plog::EVENT_OPENED_GATES_BY_CALL:
                            if ($phones && $phones->user_phone) {
                                $event["detailX"]["phoneFrom"] = strval($phones->user_phone);
                            }
                            if ($phones && $phones->gate_phone) {
                                $event["detailX"]["phoneTo"] = strval($phones->gate_phone);
                            }
                            break;

                        case plog::EVENT_OPENED_BY_VEHICLE:
                            $event["detailX"]["vehicle"] = json_decode($row[plog::COLUMN_VEHICLE]);
                            break;
                    }

                    $image_uuid = trim((string)@$row[plog::COLUMN_IMAGE_UUID]);
                    if ($image_uuid !== "" && $image_uuid !== "00000000-0000-0000-0000-000000000000") {
                        $event["imageUuid"] = $image_uuid;
                        $frontend = rtrim(@$params["_config"]["api"]["frontend"] ?: "", "/");
                        if ($frontend) {
                            $event["preview"] = $frontend . "/plog/camshot?uuid=" . rawurlencode($image_uuid);
                        }
                    }

                    $events[] = $event;
                }

                return $events;
            }

            public static function index() {
                return [
                    "GET" => "#same(addresses,house,GET)",
                ];
            }
        }
    }
