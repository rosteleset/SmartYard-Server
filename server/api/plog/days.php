<?php

    /**
     * @api {get} /api/plog/days get event log days
     *
     * @apiVersion 1.0.0
     *
     * @apiName getPlogDays
     * @apiGroup plog
     *
     * @apiHeader {String} Authorization authentication token
     *
     * @apiQuery {Number} [flatId]
     * @apiQuery {Number} [entranceId]
     * @apiQuery {Number} [houseId]
     * @apiQuery {String} [events] comma-separated event type filter
     *
     * @apiSuccess {Object[]} days
     */

    namespace api\plog {

        use api\api;
        use backends\plog\plog;

        class days extends api {

            private static function normalizeRfId(string $rfid): string {
                $rfid = strtoupper(preg_replace('/[:-]|\s+/', '', trim($rfid)));

                if ($rfid === "") {
                    return str_repeat("0", 14);
                }

                return str_pad($rfid, 14, "0", STR_PAD_LEFT);
            }

            private static function filterEvents($params) {
                if (!@$params["events"]) {
                    return false;
                }

                $filter_events = explode(",", $params["events"]);
                $t = [];
                foreach ($filter_events as $e) {
                    $t[(int)$e] = 1;
                }
                $filter_events = [];
                foreach ($t as $e => $one) {
                    $filter_events[] = $e;
                }

                return implode(",", $filter_events);
            }

            public static function GET($params) {
                $flat_id = (int)@$params["flatId"];
                $entrance_id = (int)@$params["entranceId"];
                $house_id = (int)@$params["houseId"];
                $scope_count = ($flat_id ? 1 : 0) + ($entrance_id ? 1 : 0) + ($house_id ? 1 : 0);

                if ($scope_count !== 1) {
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

                $filter_events = self::filterEvents($params);
                $rfid_filter = trim((string)@$params["rfId"]);

                if ($flat_id) {
                    $result = $plog->getEventsDays($flat_id, $filter_events ?: ($rfid_filter !== "" ? "3" : false));
                } elseif ($entrance_id) {
                    $result = $plog->getEventsDaysByEntrance($entrance_id, $filter_events);
                } else {
                    $result = $plog->getEventsDaysByHouse($house_id, $filter_events);
                }

                if ($flat_id && $rfid_filter !== "" && $result) {
                    $rfid_filter = self::normalizeRfId($rfid_filter);
                    $days = [];

                    foreach ($result as $day) {
                        $events = $plog->getDetailEventsByDay($flat_id, date("Ymd", strtotime($day["day"])));
                        $count = 0;

                        foreach ($events ?: [] as $row) {
                            if ((int)$row[plog::COLUMN_EVENT] !== plog::EVENT_OPENED_BY_KEY) {
                                continue;
                            }

                            if (self::normalizeRfId((string)$row[plog::COLUMN_RFID]) === $rfid_filter) {
                                $count++;
                            }
                        }

                        if ($count > 0) {
                            $days[] = [
                                "day" => $day["day"],
                                "events" => $count,
                            ];
                        }
                    }

                    $result = $days;
                }

                return api::ANSWER($result ?: [], "days");
            }

            public static function index() {
                return [
                    "GET" => "#same(addresses,house,GET)",
                ];
            }
        }
    }
