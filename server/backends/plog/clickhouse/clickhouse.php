<?php

    /**
     * backends plog namespace
     */

    namespace backends\plog
    {

        use backends\frs\frs;
        use http\Exception\InvalidArgumentException;
        use PDO;

        /**
         * clickhouse archive class
         */
        class clickhouse extends plog
        {
            private \clickhouse $clickhouse;
            private $time_shift;  // сдвиг по времени в секундах от текущего для обработки событий
            private $max_call_length;  // максимальная длительность звонка в секундах
            private $ttl_temp_record;  // значение, которое прибавляется к текущему времени для получения expire
            private $ttl_camshot_days;  // время жизни кадра события
            private $back_time_shift_video_shot;  // сдвиг назад в секундах от времени события для получения кадра от медиа сервера
            private $cron_process_events_scheduler;

            public function __construct($config, $db, $redis, $login = false)
            {
                parent::__construct($config, $db, $redis, $login);

                require_once __DIR__ . '/../../../utils/clickhouse.php';

                $this->clickhouse = new \clickhouse(
                    @$config['clickhouse']['host']?:'127.0.0.1',
                    @$config['clickhouse']['port']?:8123,
                    @$config['clickhouse']['username']?:'default',
                    @$config['clickhouse']['password']?:'qqq',
                    @$config['clickhouse']['database']?:'default'
                );

                $this->time_shift = $config['backends']['plog']['time_shift'];
                $this->max_call_length = $config['backends']['plog']['max_call_length'];
                $this->ttl_temp_record = $config['backends']['plog']['ttl_temp_record'];
                $this->ttl_camshot_days = $config['backends']['plog']['ttl_camshot_days'];
                $this->back_time_shift_video_shot = $config['backends']['plog']['back_time_shift_video_shot'];
                $this->cron_process_events_scheduler = $config['backends']['plog']['cron_process_events_scheduler'];
            }

            /**
             * @inheritDoc
             */
            public function cron($part)
            {
                if ($part === $this->cron_process_events_scheduler) {
                    echo("__process events\n");
                    $this->processEvents();
                    $this->db->modify("delete from plog_door_open where expire < " . time());
                    $this->db->modify("delete from plog_call_done where expire < " . time());
                    return true;
                }

                return true;
            }

            //получение кадра события на указанную дату+время и ip устройства или от FRS
            /**
             * @inheritDoc
             */
            public function getCamshot($domophone_id, $date, $event_id = false)
            {
                $files = loadBackend('files');
                $camshot_data = [];

                $households = loadBackend("households");
                $entrances = $households->getEntrances("domophoneId", [ "domophoneId" => $domophone_id, "output" => "0" ]);
                if ($entrances && $entrances[0]) {
                    $cameras = $households->getCameras("id", $entrances[0]["cameraId"]);
                    if ($cameras && $cameras[0]) {
                        try {
                            $frs = loadBackend("frs");
                            if ($frs) {
                                if ($event_id === false) {
                                    $response = $frs->bestQualityByDate($cameras[0], $date);
                                } else {
                                    $response = $frs->bestQualityByEventId($cameras[0], $event_id);
                                }

                                if ($response && $response[frs::P_CODE] == frs::R_CODE_OK && $response[frs::P_DATA]) {
                                    $image_data = false;
                                    $urlOfScreenshot = $response[frs::P_DATA][frs::P_SCREENSHOT];
                                    if (filter_var($urlOfScreenshot, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED) !== false) {
                                        $image_data = file_get_contents($urlOfScreenshot);
                                    }
                                    if ($image_data) {
                                        $headers = implode("\n", $http_response_header);
                                        $content_type = "image/jpeg";
                                        if (preg_match_all("/^content-type\s*:\s*(.*)$/mi", $headers, $matches)) {
                                            $content_type = end($matches[1]);
                                        }
                                        $camshot_data[self::COLUMN_IMAGE_UUID] = $files->toGUIDv4($files->addFile(
                                            "camshot",
                                            $files->contentsToStream($image_data),
                                            [
                                                "contentType" => $content_type,
                                                "expire" => time() + $this->ttl_camshot_days * 86400,
                                            ]
                                        ));
                                        $camshot_data[self::COLUMN_PREVIEW] = self::PREVIEW_FRS;
                                        $camshot_data[self::COLUMN_FACE] = [
                                            frs::P_FACE_LEFT => $response[frs::P_DATA][frs::P_FACE_LEFT],
                                            frs::P_FACE_TOP => $response[frs::P_DATA][frs::P_FACE_TOP],
                                            frs::P_FACE_WIDTH => $response[frs::P_DATA][frs::P_FACE_WIDTH],
                                            frs::P_FACE_HEIGHT => $response[frs::P_DATA][frs::P_FACE_HEIGHT],
                                        ];
                                    }
                                }
                            }
                        } catch (\Exception $e) {
                            $camshot_data = [];
                            error_log(print_r($e, true));
                        }

                        if (!$camshot_data) {
                            try {
                                //получение кадра с DVR-серевера, если нет кадра от FRS
                                $prefix = $cameras[0]["dvrStream"];
                                if ($prefix) {
                                    $ts_event = $date - $this->back_time_shift_video_shot;
                                    $filename = "/tmp/" . uniqid('camshot_') . ".jpg";
                                    $urlOfScreenshot = loadBackend("dvr")->getUrlOfScreenshot($cameras[0], $ts_event);
                                    if (filter_var($urlOfScreenshot, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED) === false) {
                                        throw new \InvalidArgumentException("Invalid URL $urlOfScreenshot");
                                    }
                                    if (pathinfo(parse_url($urlOfScreenshot, PHP_URL_PATH), PATHINFO_EXTENSION) === 'mp4') {
                                        system("ffmpeg -y -i " . $urlOfScreenshot . " -vframes 1 $filename 1>/dev/null 2>/dev/null");
                                    } else {
                                        file_put_contents($filename, file_get_contents($urlOfScreenshot));
                                    }
                                    if (file_exists($filename)) {
                                        $camshot_data[self::COLUMN_IMAGE_UUID] = $files->toGUIDv4($files->addFile(
                                            "camshot",
                                            fopen($filename, 'rb'),
                                            [
                                                "contentType" => "image/jpeg",
                                                "expire" => time() + $this->ttl_camshot_days * 86400,
                                            ]
                                        ));
                                        unlink($filename);
                                        $camshot_data[self::COLUMN_PREVIEW] = self::PREVIEW_DVR;
                                    } else {
                                        $camshot_data[self::COLUMN_PREVIEW] = self::PREVIEW_NONE;
                                    }
                                } else {
                                    $camshot_data[self::COLUMN_PREVIEW] = self::PREVIEW_NONE;
                                }
                            } catch (\Exception $e) {
                                $camshot_data = [];
                                $camshot_data[self::COLUMN_PREVIEW] = self::PREVIEW_NONE;
                                error_log(print_r($e, true));
                            }
                        }
                    }
                }

                return $camshot_data;
            }

            /**
             * @inheritDoc
             */
            public function writeEventData($event_data, $flat_list = [])
            {
                echo("__call writeEventData\n");
                try {
                    if (count($flat_list)) {
                        foreach ($flat_list as $flat_id) {
                            $hidden = $this->getPlogHidden($flat_id);
                            if ($hidden < 0) {
                                continue;
                            }
                            $event_data[self::COLUMN_HIDDEN] = $hidden;
                            $event_data[self::COLUMN_FLAT_ID] = $flat_id;
                            $this->clickhouse->insert("plog", [$event_data]);
                        }
                    } else {
                        $hidden = $this->getPlogHidden($event_data[self::COLUMN_FLAT_ID]);
                        if ($hidden < 0) {
                            return;
                        }
                        $event_data[self::COLUMN_HIDDEN] = $hidden;
                        $this->clickhouse->insert("plog", [$event_data]);
                    }
                } catch (\Exception $e) {
                    error_log(print_r($e, true));
                    return;
                }
            }

            /**
             * @inheritDoc
             */
            public function addCallDoneData($date, $ip, $call_id = null)
            {
                $expire = $date + $this->ttl_temp_record;

                $query = "insert into plog_call_done(date, ip, call_id, expire) values(:date, :ip, :call_id, :expire)";
                return $this->db->insert($query, [
                    ":date" => $date,
                    ":ip" => $ip,
                    ":call_id" => $call_id,
                    ":expire" => $expire,
                ]);
            }

            /**
             * @inheritDoc
             */
            public function addDoorOpenData($date, $ip, $event_type, $door, $detail)
            {
                $expire = time() + $this->ttl_temp_record;

                $query = "insert into plog_door_open(date, ip, event, door, detail, expire) values(:date, :ip, :event, :door, :detail, :expire)";
                return $this->db->insert($query, [
                    ":date" => $date,
                    ":ip" => $ip,
                    ":event" => $event_type,
                    ":door" => $door,
                    ":detail" => $detail,
                    ":expire" => $expire,
                ]);
            }

            /**
             * @inheritDoc
             */
            public function addDoorOpenDataById($date, $domophone_id, $event_type, $door, $detail)
            {
                $households = loadBackend('households');
                $ip = $households->getDomophone($domophone_id)['ip'];

                return $this->addDoorOpenData($date, $ip, $event_type, $door, $detail);
            }

            /**
             * @inheritDoc
             */
            public function getEventsDays(int $flat_id, $filter_events)
            {
                if ($filter_events) {
                    $query = "
                        select
                            toYYYYMMDD(FROM_UNIXTIME(date)) as day,
                            count(day) as events
                        from
                            plog
                        where
                            not hidden
                            and flat_id = $flat_id
                            and event in ($filter_events)
                        group by
                            day
                        order by
                            day desc
                    ";
                } else {
                    $query = "
                        select
                            toYYYYMMDD(FROM_UNIXTIME(date)) as day,
                            count(day) as events
                        from
                            plog
                        where
                            not hidden
                            and flat_id = $flat_id
                        group by
                            day
                        order by
                            day desc
                    ";
                }

                $result = $this->clickhouse->select($query);
                if (count($result)) {
                    foreach ($result as &$d) {
                        $d['day'] = substr($d['day'], 0, 4) . '-' . substr($d['day'], 4, 2) . '-' . substr($d['day'], 6, 2);
                    }
                    return $result;
                }

                return false;
            }

            /**
             * @inheritDoc
             */
            public function getDetailEventsByDay(int $flat_id, string $date)
            {
                $query = "
                    select
                        date,
                        event_uuid,
                        hidden,
                        image_uuid,
                        flat_id,
                        toJSONString(domophone) domophone,
                        event,
                        opened,
                        toJSONString(face) face,
                        rfid,
                        code,
                        toJSONString(phones) phones,
                        preview
                    from
                        plog
                    where
                        not hidden
                        and toYYYYMMDD(FROM_UNIXTIME(date)) = '$date'
                        and flat_id = $flat_id
                    order by
                        date desc
                ";

                return $this->clickhouse->select($query);
            }

            /**
             * @inheritDoc
             */
            public function getEventDetails(string $uuid)
            {
                $query = "
                    select
                        date,
                        event_uuid,
                        hidden,
                        image_uuid,
                        flat_id,
                        toJSONString(domophone) domophone,
                        event,
                        opened,
                        toJSONString(face) face,
                        rfid,
                        code,
                        toJSONString(phones) phones,
                        preview
                    from
                        plog
                    where
                        event_uuid = '$uuid'
                ";

                return $this->clickhouse->select($query)[0];
            }

            public function getDomophoneId($ip)
            {
                $households = loadBackend('households');
                $result = $households->getDomophones('ip', $ip);
                if ($result && $result[0]) {
                    return $result[0]['domophoneId'];
                }

                return false;
            }

            private function getDomophoneDescription($domophone_id, $domophone_output)
            {
                $households = loadBackend('households');
                $result = $households->getEntrances('domophoneId', ['domophoneId' => $domophone_id, 'output' => $domophone_output]);
                if ($result && $result[0]) {
                    return $result[0]['entrance'];
                }

                return false;
            }

            private function getFlatId($item) {
                return $item['flatId'];
            }

            //получение списка flat_id по RFID ключу на домофоне
            private function getFlatIdByRfid($rfid, $domophone_id)
            {
                $households = loadBackend('households');
                $flats1 = array_map('self::getFlatId', $households->getFlats('rfId', ['rfId' => $rfid]));
                $flats2 = array_map('self::getFlatId', $households->getFlats('domophoneId', $domophone_id));
                return array_intersect($flats1, $flats2);
            }

            //получение списка flat_id по коду открытия на устройстве
            private function getFlatIdByCode($code, $domophone_id)
            {
                $households = loadBackend('households');
                $flats1 = array_map('self::getFlatId', $households->getFlats('openCode', ['openCode' => $code]));
                $flats2 = array_map('self::getFlatId', $households->getFlats('domophoneId', $domophone_id));
                return array_intersect($flats1, $flats2);
            }

            //получение списка flat_id по телефону пользователя на устройстве
            private function getFlatIdByUserPhone($user_phone, $domophone_id)
            {
                $households = loadBackend('households');
                $result = $households->getSubscribers('mobile', $user_phone);
                if ($result && $result[0]) {
                    $flats1 = array_map('self::getFlatId', $households->getFlats('subscriberId', ['id' => $user_phone]));
                    $flats2 = array_map('self::getFlatId', $households->getFlats('domophoneId', $domophone_id));
                    return array_intersect($flats1, $flats2);
                }

                return false;
            }

            //получение flat_id по номеру квартиры на устройстве
            private function getFlatIdByNumber($flat_number, $domophone_id)
            {
                $households = loadBackend('households');
                $result = $households->getFlats('apartment', ['domophoneId' => $domophone_id, 'flatNumber' => $flat_number]);
                if ($result && $result[0]) {
                    return $result[0]['flatId'];
                }

                return false;
            }

            //получение flat_id по префиксу калитки и номеру квартиры
            private function getFlatIdByPrefixAndNumber($prefix, $flat_number, $domophone_id)
            {
                $households = loadBackend('households');
                $result = $households->getFlats('flatIdByPrefix', [
                    'prefix' => $prefix,
                    'flatNumber' => $flat_number,
                    'domophoneId' => $domophone_id,
                ]);
                if ($result && $result[0]) {
                    return $result[0]['flatId'];
                }

                return false;
            }

            // Get flat ID by domophone ID
            private function getFlatIdByDomophoneId($domophone_id)
            {
                $households = loadBackend('households');
                $result = $households->getFlats('domophoneId', $domophone_id);

                // Only if one apartment is linked
                if ($result && count($result) === 1 && $result[0]) {
                    return $result[0]['flatId'];
                }

                return false;
            }

            private function getEntranceCount($flat_id)
            {
                $households = loadBackend('households');
                $result = $households->getEntrances('flatId', $flat_id);
                if ($result)
                    return count($result);

                return 0;
            }

            private function getPlogHidden($flat_id) {
                $households = loadBackend('households');
                $flat = $households->getFlat($flat_id);
                if ($flat['plog'] == self::ACCESS_RESTRICTED_BY_ADMIN) {
                    //ignore event
                    return -1;
                }
                $hidden = 0;
                if ($flat['plog'] == self::ACCESS_DENIED) {
                    $hidden = 1;
                }

                return $hidden;
            }

            private function processEvents()
            {
                $end_date = time() - $this->time_shift;  //крайняя дата обработки

                //обработка данных из таблицы plog_door_open
                $query = "
                    select
                        *
                    from
                        plog_door_open
                    where
                        date <= $end_date
                    order by
                        date
                ";
                $result = $this->db->query($query, PDO::FETCH_ASSOC)->fetchAll();
                foreach ($result as $row) {
                    $event_data = [];
                    $event_id = false;
                    $flat_list = [];
                    unset($face_id);

                    $plog_date = $row['date'];
                    $domophone_id = $this->getDomophoneId($row["ip"]);
                    $event_type = (int)$row['event'];

                    $event_data[self::COLUMN_DATE] = $plog_date;
                    $event_data[self::COLUMN_EVENT] = $event_type;
                    $event_data[self::COLUMN_DOMOPHONE]['domophone_id'] = $domophone_id;
                    $event_data[self::COLUMN_DOMOPHONE]['domophone_output'] = $row['door'];
                    $event_data[self::COLUMN_DOMOPHONE]['domophone_description'] = $this->getDomophoneDescription(
                        $event_data[self::COLUMN_DOMOPHONE]['domophone_id'],
                        $event_data[self::COLUMN_DOMOPHONE]['domophone_output']
                    );
                    $event_data[self::COLUMN_EVENT_UUID] = GUIDv4();

                    if ($event_type == self::EVENT_OPENED_BY_KEY) {
                        $event_data[self::COLUMN_OPENED] = 1;
                        $rfid_key = $row['detail'];
                        $event_data[self::COLUMN_RFID] = $rfid_key;
                        $flat_list = $this->getFlatIdByRfid($rfid_key, $domophone_id);
                        if (!$flat_list) {
                            continue;
                        }
                    }

                    if ($event_type == self::EVENT_OPENED_BY_CODE) {
                        $event_data[self::COLUMN_OPENED] = 1;
                        $open_code = $row['detail'];
                        $event_data[self::COLUMN_CODE] = $open_code;
                        $flat_list = $this->getFlatIdByCode($open_code, $domophone_id);
                        if (!$flat_list) {
                            continue;
                        }
                    }

                    if ($event_type == self::EVENT_OPENED_BY_APP) {
                        $event_data[self::COLUMN_OPENED] = 1;
                        $user_phone = $row['detail'];
                        $event_data[self::COLUMN_PHONES]['user_phone'] = $user_phone;
                        $flat_list = $this->getFlatIdByUserPhone($user_phone, $domophone_id);
                        if (!$flat_list) {
                            continue;
                        }
                    }

                    if ($event_type == self::EVENT_OPENED_BY_FACE) {
                        $event_data[self::COLUMN_OPENED] = 1;
                        $details = explode("|", $row['detail']);
                        $face_id = $details[0];
                        $event_id = $details[1];
                        $households = loadBackend('households');
                        $entrance = $households->getEntrances("domophoneId",[
                            "domophoneId" => $domophone_id,
                            "output" => $row['door']
                        ])[0];
                        $frs = loadBackend("frs");
                        if ($frs)
                            $flat_list = $frs->getFlatsByFaceId($face_id, $entrance["entranceId"]);
                        if (!$flat_list) {
                            continue;
                        }
                    }

                    //получение кадра события
                    $image_data = $this->getCamshot($domophone_id, $plog_date, $event_id);
                    if ($image_data) {
                        if (isset($image_data[self::COLUMN_IMAGE_UUID])) {
                            $event_data[self::COLUMN_IMAGE_UUID] = $image_data[self::COLUMN_IMAGE_UUID];
                        }
                        $event_data[self::COLUMN_PREVIEW] = $image_data[self::COLUMN_PREVIEW];
                        if (isset($image_data[self::COLUMN_FACE])) {
                            $event_data[self::COLUMN_FACE] = $image_data[self::COLUMN_FACE];
                            if (isset($face_id)) {
                                $event_data[self::COLUMN_FACE][frs::P_FACE_ID] = $face_id;
                            }
                        }
                    }
                    $this->writeEventData($event_data, $flat_list);
                }

                //удаление данных из таблицы plog_door_open
                $query = "
                    delete
                    from
                        plog_door_open
                    where
                        date <= $end_date
                ";
                $this->db->query($query);

                //обработка данных из таблицы plog_call_done
                $query = "
                    select
                        *
                    from
                        plog_call_done
                    where
                        date <= $end_date
                    order by
                        date
                ";
                $result = $this->db->query($query, PDO::FETCH_ASSOC)->fetchAll();
                foreach ($result as $row) {
                    $ip = $row['ip'];
                    $domophone_id = $this->getDomophoneId($row["ip"]);

                    $event_data = [];
                    $event_data[self::COLUMN_DATE] = $row['date'];
                    $event_data[self::COLUMN_EVENT] = self::EVENT_UNANSWERED_CALL;
                    $event_data[self::COLUMN_DOMOPHONE]['domophone_id'] = $domophone_id;
                    $event_data[self::COLUMN_DOMOPHONE]['domophone_output'] = 0;
                    $event_data[self::COLUMN_DOMOPHONE]['domophone_description'] = $this->getDomophoneDescription(
                        $event_data[self::COLUMN_DOMOPHONE]['domophone_id'],
                        $event_data[self::COLUMN_DOMOPHONE]['domophone_output']
                    );
                    $event_data[self::COLUMN_EVENT_UUID] = GUIDv4();

                    $call_id = (int)$row['call_id'];
                    if ($call_id == 0) {
                        unset($call_id);
                    }
                    unset($sip_call_id);
                    unset($flat_id);
                    unset($flat_number);
                    unset($prefix);
                    $call_from_panel = 0;
                    $call_start_found = false;

                    //забираем данные из сислога для звонка
                    $query_end_date = $row['date'];
                    $query_start_date = $query_end_date - $this->max_call_length;
                    $query = "
                        select
                            date,
                            msg,
                            unit
                        from
                            syslog s
                        where
                            IPv4NumToString(s.ip) = '$ip'
                            and s.date > $query_start_date
                            and s.date <= $query_end_date
                        order by
                            date desc
                    ";
                    $result = $this->clickhouse->select($query);
                    foreach ($result as $item) {
                        $msg = $item['msg'];
                        $unit = $item['unit'];

                        // Call processing for Beward panel
                        if ($unit == 'beward') {
                            $patterns_call = [
                                //pattern         start  talk  open   call_from_panel
                                ["Calling sip:", true, false, false, 1],
                                ["Unable to call CMS apartment ", true, false, false, 0],
                                ["CMS handset call started for apartment ", true, false, false, 0],
                                ["SIP call | state ", false, false, false, 0],
                                ["CMS handset talk started for apartment ", false, true, false, 0],
                                ["SIP talk started for apartment ", false, true, false, 1],
                                ["SIP call | CONFIRMED", false, true, false, 0],
                                ["Opening door by CMS handset for apartment ", false, false, true, 0],
                                ["Opening door by DTMF command", false, false, true, 0],
                                ["All calls are done", false, false, false, 0],
                                ["SIP call | DISCONNECTED", false, false, false, 0],
                                ["SIP call | CALLING", false, false, false, 1],
                                ["Incoming DTMF ", false, false, false, 1],
                                ["Send DTMF ", false, false, false, -1],
                            ];
                            foreach ($patterns_call as [$pattern, $flag_start, $flag_talk_started, $flag_door_opened, $now_call_from_panel]) {
                                unset($now_flat_id);
                                unset($now_flat_number);
                                unset($now_call_id);
                                unset($now_sip_call_id);

                                $parts = explode("|", $pattern);
                                $matched = true;
                                foreach ($parts as $p) {
                                    $matched = $matched && (strpos($msg, $p) !== false);
                                }

                                if ($matched) {
                                    if ($now_call_from_panel > 0) {
                                        $call_from_panel = 1;
                                    } elseif ($now_call_from_panel < 0) {
                                        $call_from_panel = -1;
                                        break;
                                    }

                                    if (strpos($msg, "[") !== false) {
                                        //парсим call_id
                                        $p1 = strpos($msg, "[");
                                        $p2 = strpos($msg, "]", $p1 + 1);
                                        $now_call_id = intval(substr($msg, $p1 + 1, $p2 -$p1 - 1));
                                    }

                                    if (strpos($pattern, "apartment") !== false) {
                                        //парсим номер квартиры
                                        $p1 = strpos($msg, $pattern);
                                        $p2 = strpos($msg, ".", $p1 + strlen($pattern));
                                        if (!$p2)
                                            $p2 = strpos($msg, ",", $p1 + strlen($pattern));
                                        if (!$p2)
                                            $p2 = strlen($msg);
                                        $now_flat_number = intval(substr($msg, $p1 + strlen($pattern), $p2 -$p1 - strlen($pattern)));
                                    }

                                    if (strpos($pattern, "Calling sip:") !== false) {
                                        $p1 = strpos($msg, $pattern);
                                        $p2 = strpos($msg, "@", $p1 + strlen($pattern));
                                        $sip = substr($msg, $p1 + strlen($pattern), $p2 -$p1 - strlen($pattern));
                                        if ($sip[0] == "1") {
                                            //звонок с панели, имеющей КМС, доп. панели или калитки без префикса
                                            //парсим flat_id
                                            $p1 = strpos($msg, $pattern);
                                            $p2 = strpos($msg, "@", $p1 + strlen($pattern));
                                            $now_flat_id = intval(substr($msg, $p1 + strlen($pattern) + 1, $p2 -$p1 - strlen($pattern) - 1));
                                        } else {
                                            //звонок с префиксом, первые четыре цифры - префикс с лидирующими нулями, остальные - номер квартиры (калитка)
                                            $prefix = intval(substr($sip, 0, 4));
                                            $now_flat_number = intval(substr($sip, 4));
                                        }
                                    }

                                    if (strpos($pattern, "SIP call ") !== false) {
                                        //парсим sip_call_id
                                        $p1 = strpos($msg, $parts[0]);
                                        $p2 = strpos($msg, " ", $p1 + strlen($parts[0]));
                                        $now_sip_call_id = intval(substr($msg, $p1 + strlen($parts[0]), $p2 -$p1 - strlen($parts[0])));
                                    }

                                    $call_start_lost = isset($now_flat_id) && isset($flat_id) && $now_flat_id != $flat_id
                                        || isset($now_flat_number) && isset($flat_number) && $now_flat_number != $flat_number
                                        || isset($now_sip_call_id) && isset($sip_call_id) && $now_sip_call_id != $sip_call_id
                                        || isset($now_call_id) && isset($call_id) && $now_call_id != $call_id;

                                    if ($call_start_lost) {
                                        break;
                                    }

                                    $event_data[self::COLUMN_DATE] = $item['date'];

                                    if (isset($now_call_id) && !isset($call_id)) {
                                        $call_id = $now_call_id;
                                    }
                                    if (isset($now_sip_call_id) && !isset($sip_call_id)) {
                                        $sip_call_id = $now_sip_call_id;
                                    }
                                    if (isset($now_flat_number) && !isset($flat_number)) {
                                        $flat_number = $now_flat_number;
                                    }
                                    if (isset($now_flat_id) && !isset($flat_id)) {
                                        $flat_id = $now_flat_id;
                                    }
                                    if ($flag_talk_started) {
                                        $event_data[self::COLUMN_EVENT] = self::EVENT_ANSWERED_CALL;
                                    }
                                    if ($flag_door_opened) {
                                        $event_data[self::COLUMN_OPENED] = 1;
                                    }
                                    if ($flag_start) {
                                        $call_start_found = true;
                                        break;
                                    }
                                }
                            }
                        }

                        // Call processing for IS panel
                        if ($unit == "is") {
                            $patterns_call = [
                                // pattern         start  talk  open   call_from_panel
                                ["/Calling sip:\d+@.* through account/", true, false, false, 1],
                                ["/CMS handset is not connected for apartment \d+, aborting CMS call/", true, false, false, 0],
                                ["/CMS handset call started for apartment \d+/", true, false, false, 0],
                                ["/CMS handset talk started for apartment \d+/", false, true, false, 0],
                                ["/Baresip event CALL_RINGING/", true, false, false, 1],
                                ["/Baresip event CALL_ESTABLISHED/", false, true, false, 0],
                                ["/Opening door by CMS handset for apartment \d+/", false, false, true, 0],
                                ["/Open from handset!/", false, false, true, 0],
                                ["/Open main door by DTMF/", false, false, true, 1],
                                ["/CMS handset call done for apartment \d+, handset is down/", false, false, false, 0],
                                ["/SIP call done for apartment \d+, handset is down/", false, false, false, 1],
                                ["/All calls are done for apartment \d+/", false, false, false, 1],

                                // Incoming call patterns
                                ["/Baresip event CALL_INCOMING/", false, false, false, -1],
                                ["/Incoming call to sip:\d+@.* \(\d+\)/", false, false, false, -1],
                            ];

                            foreach ($patterns_call as [$pattern, $flag_start, $flag_talk_started, $flag_door_opened, $now_call_from_panel]) {
                                unset($now_flat_id);
                                unset($now_flat_number);
                                unset($now_call_id);
                                unset($now_sip_call_id);

                                if (preg_match($pattern, $msg)) {
                                    // Check if call started from this panel
                                    if ($now_call_from_panel > 0) {
                                        $call_from_panel = 1;
                                    } elseif ($now_call_from_panel < 0) {
                                        $call_from_panel = -1;
                                        break;
                                    }

                                    // Get message parts
                                    $msg_parts = array_map('trim', preg_split("/[,@:]|\s(?=\d)/", $msg));

                                    // Get flat number and prefix
                                    if (isset($msg_parts[1])) {
                                        $number = $msg_parts[1];

                                        if (strlen($number) < 5) { // Apartment - ordinary panel
                                            $now_flat_number = $number;
                                        } else { // Gate panel - prefix and apartment
                                            $prefix = substr($number, 0, 4);
                                            $now_flat_number = substr($number, 4);
                                        }
                                    }

                                    $call_start_lost = isset($now_flat_id) && isset($flat_id) && $now_flat_id != $flat_id
                                        || isset($now_flat_number) && isset($flat_number) && $now_flat_number != $flat_number
                                        || isset($now_sip_call_id) && isset($sip_call_id) && $now_sip_call_id != $sip_call_id
                                        || isset($now_call_id) && isset($call_id) && $now_call_id != $call_id;

                                    if ($call_start_lost) {
                                        break;
                                    }

                                    $event_data[self::COLUMN_DATE] = $item["date"];

                                    if (isset($now_call_id) && !isset($call_id)) {
                                        $call_id = $now_call_id;
                                    }
                                    if (isset($now_sip_call_id) && !isset($sip_call_id)) {
                                        $sip_call_id = $now_sip_call_id;
                                    }
                                    if (isset($now_flat_number) && !isset($flat_number)) {
                                        $flat_number = $now_flat_number;
                                    }
                                    if (isset($now_flat_id) && !isset($flat_id)) {
                                        $flat_id = $now_flat_id;
                                    }
                                    if ($flag_talk_started) {
                                        $event_data[self::COLUMN_EVENT] = self::EVENT_ANSWERED_CALL;
                                    }
                                    if ($flag_door_opened) {
                                        $event_data[self::COLUMN_OPENED] = 1;
                                    }
                                    if ($flag_start) {
                                        $call_start_found = true;
                                        break;
                                    }
                                }
                            }
                        }

                        // Call processing for QTECH panel
                        if ($unit == "qtech") {
                            $patterns_call = [
                                // pattern         start  talk  open   call_from_panel
                                ["/Prefix:\d+,Replace Number:\d+, Status:\d+/", true, false, false, 1],
                                ["/Prefix:\d+,Analog Number:\d+, Status:\d+/", true, false, false, 1],
                                ["/\d+:Call Established, Number:\d+/", false, true, false, 0],
                                ["/\d+:Open Door By Intercom,Apartment No \d+/", false, false, true, 1],
                                ["/\d+:\d+:Open Door By DTMF, DTMF Symbol \d+ ,Apartment No \d+/", false, false, true, 1],
                            ];

                            foreach ($patterns_call as [$pattern, $flag_start, $flag_talk_started, $flag_door_opened, $now_call_from_panel]) {
                                unset($now_flat_id);
                                unset($now_flat_number);
                                unset($now_call_id);
                                unset($now_sip_call_id);

                                if (preg_match($pattern, $msg) !== 0) {
                                    // Check if call started from this panel
                                    if ($now_call_from_panel > 0) {
                                        $call_from_panel = 1;
                                    }

                                    // Get message parts separated by ":" and ","
                                    $msg_parts = array_map("trim", preg_split("/[:,]/", $msg));

                                    // Get flat number, flat ID and prefix from call started events
                                    if ($msg_parts[0] === "Prefix") {
                                        $number = $msg_parts[1]; // Caller (apartment or panel SIP number)
                                        $replacing_number = $msg_parts[3]; // Call destination

                                        if ($number <= 9999) { // Apartment - ordinary panel
                                            $now_flat_number = $number;

                                            if ($msg_parts[2] === "Replace Number") { // Get flat ID
                                                $now_flat_id = substr($replacing_number, 1);
                                            }
                                        } else { // Panel SIP number - gate panel
                                            $prefix = substr($replacing_number, 0, 4);
                                            $now_flat_number = substr($replacing_number, 4);
                                        }
                                    }

                                    // Get flat number, flat ID and prefix from call established events
                                    if ($msg_parts[1] === "Call Established") {
                                        $number = $msg_parts[0]; // Call destination
                                        $number_len = strlen($number);

                                        if ($number_len === 10) { // Get flat ID
                                            $now_flat_id = substr($number, 1);
                                        } elseif ($number_len < 9 && $number_len > 4) { // Get prefix and flat number
                                            $prefix = substr($number, 0, 4);
                                            $now_flat_number = substr($number, 4);
                                        } else { // Get flat number
                                            $now_flat_number = $number;
                                        }
                                    }

                                    // Get flat number from CMS door open event
                                    if ($msg_parts[1] === "Open Door By Intercom") {
                                        $now_flat_number = $msg_parts[0];
                                    }

                                    // Get flat number from DTMF door open event
                                    if ($msg_parts[2] === "Open Door By DTMF") {
                                        $number = $msg_parts[1];

                                        if ($number <= 9999) { // Apartment - ordinary panel
                                            $now_flat_number = $number;
                                        }
                                    }

                                    $call_start_lost = isset($now_flat_id) && isset($flat_id) && $now_flat_id != $flat_id
                                        || isset($now_flat_number) && isset($flat_number) && $now_flat_number != $flat_number
                                        || isset($now_sip_call_id) && isset($sip_call_id) && $now_sip_call_id != $sip_call_id
                                        || isset($now_call_id) && isset($call_id) && $now_call_id != $call_id;

                                    if ($call_start_lost) {
                                        break;
                                    }

                                    $event_data[self::COLUMN_DATE] = $item["date"];

                                    if (isset($now_call_id) && !isset($call_id)) {
                                        $call_id = $now_call_id;
                                    }
                                    if (isset($now_sip_call_id) && !isset($sip_call_id)) {
                                        $sip_call_id = $now_sip_call_id;
                                    }
                                    if (isset($now_flat_number) && !isset($flat_number)) {
                                        $flat_number = $now_flat_number;
                                    }
                                    if (isset($now_flat_id) && !isset($flat_id)) {
                                        $flat_id = $now_flat_id;
                                    }
                                    if ($flag_talk_started) {
                                        $event_data[self::COLUMN_EVENT] = self::EVENT_ANSWERED_CALL;
                                    }
                                    if ($flag_door_opened) {
                                        $event_data[self::COLUMN_OPENED] = 1;
                                    }
                                    if ($flag_start) {
                                        $call_start_found = true;
                                        break;
                                    }
                                }
                            }
                        }

                        if ($unit == "akuvox") {
                            $patterns_call = [
                                // pattern         start  talk  open   call_from_panel
                                ["SIP_LOG:MSG_S2P_TRYING", true, false, false, 1],
                                ["SIP_LOG:MSG_S2P_RINGBACK", true, false, false, 1],
                                ["SIP_LOG:MSG_S2P_ESTABLISHED_CALL", false, true, false, 1],
                                ["DTMF_LOG:Receive", false, false, true, 1],
                                ["DTMF_LOG:From", false, false, true, 1],
                                ["DTMF_LOG:Successful", false, false, true, 1],
                                ["SIP_LOG:Call Finished", false, false, false, 1],
                                ["SIP_LOG:Call Failed", false, false, false, 1],
                            ];

                            foreach ($patterns_call as [$pattern, $flag_start, $flag_talk_started, $flag_door_opened, $now_call_from_panel]) {
                                unset($now_flat_id);
                                unset($now_flat_number);
                                unset($now_call_id);
                                unset($now_sip_call_id);

                                if (strpos($msg, $pattern) !== false) {
                                    // Get call ID
                                    if (strpos($msg, 'SIP_LOG') !== false) {
                                        $now_call_id = explode('=', $msg)[1];
                                    }

                                    // Get flat ID
                                    if (strpos($msg, 'DTMF_LOG:From') !== false) {
                                        $number = explode(' ', $msg)[1];
                                        $now_flat_id = substr($number, 1);
                                    }

                                    $call_start_lost = isset($now_flat_id) && isset($flat_id) && $now_flat_id != $flat_id
                                        || isset($now_call_id) && isset($call_id) && $now_call_id != $call_id;

                                    if ($call_start_lost) {
                                        break;
                                    }

                                    $event_data[self::COLUMN_DATE] = $item["date"];

                                    if (isset($now_call_id) && !isset($call_id)) {
                                        $call_id = $now_call_id;
                                    }
                                    if (isset($now_flat_id) && !isset($flat_id)) {
                                        $flat_id = $now_flat_id;
                                    }
                                    if ($flag_talk_started) {
                                        $event_data[self::COLUMN_EVENT] = self::EVENT_ANSWERED_CALL;
                                    }
                                    if ($flag_door_opened) {
                                        $event_data[self::COLUMN_OPENED] = 1;
                                    }
                                    if ($flag_start) {
                                        $call_start_found = true;
                                        break;
                                    }
                                }
                            }
                        }

                        if ($call_start_found) {
                            break;
                        }

                        if ($call_from_panel < 0) {
                            break;
                        }
                    }

                    if ($call_from_panel < 0) {
                        //начало звонка было точно не с этой панели - игнорируем звонок
                        continue;
                    }

                    if (isset($flat_id)) {
                        $event_data[self::COLUMN_FLAT_ID] = $flat_id;
                    } elseif (isset($prefix) && isset($flat_number)) {
                        $event_data[self::COLUMN_FLAT_ID] = $this->getFlatIdByPrefixAndNumber($prefix, $flat_number, $domophone_id);
                    } elseif (isset($flat_number)) {
                        $event_data[self::COLUMN_FLAT_ID] = $this->getFlatIdByNumber($flat_number, $domophone_id);
                    } else {
                        $event_data[self::COLUMN_FLAT_ID] = $this->getFlatIdByDomophoneId($domophone_id);
                    }

                    if (!isset($event_data[self::COLUMN_FLAT_ID])) {
                        //не удалось получить flat_id - игнорируем звонок
                        continue;
                    }

                    if ($call_from_panel == 0) {
                        //нет точных данных о том, что начало звонка было с этой панели
                        //проверяем, мог ли звонок идти с другой панели
                        $entrance_count = $this->getEntranceCount($event_data[self::COLUMN_FLAT_ID]);
                        if ($entrance_count > 1) {
                            //в квартиру можно позвонить с нескольких домофонов,
                            //в данном случае считаем, что начальный звонок был с другого домофона - игнорируем звонок
                            continue;
                        }
                    }

                    //получение кадра события
                    $image_data = $this->getCamshot($domophone_id, $event_data[self::COLUMN_DATE]);
                    if ($image_data) {
                        if (isset($image_data[self::COLUMN_IMAGE_UUID])) {
                            $event_data[self::COLUMN_IMAGE_UUID] = $image_data[self::COLUMN_IMAGE_UUID];
                        }
                        $event_data[self::COLUMN_PREVIEW] = $image_data[self::COLUMN_PREVIEW];
                        // TODO: доделать для случая наличия инфы о лице
                    }

                    //сохраняем событие
                    $this->writeEventData($event_data);
                }

                //удаление данных из таблицы plog_call_done
                $query = "
                    delete
                    from
                        plog_call_done
                    where
                        date <= $end_date
                ";
                $this->db->query($query);
            }
        }
    }
