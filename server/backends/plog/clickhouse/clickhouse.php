<?php

    /**
     * backends plog namespace
     */

    namespace backends\plog
    {

        use backends\files\files;
        use MongoDB\BSON\UTCDateTime;

        /**
         * clickhouse archive class
         */
        class clickhouse extends plog
        {
            private $clickhouse;
            private $time_shift;  // сдвиг по времени в секундах от текущего для обработки событий
            private $max_call_length;  // максимальная длительность звонка в секундах
            private $ttl_temp_record;  // значение, которое прибавляется к текущему времени для получения expire
            private $ttl_camshot_days;  // время жизни кадра события
            private $back_time_shift_video_shot;  // сдвиг назад в секундах от времени события для получения кадра от медиа сервера

            function __construct($config, $db, $redis)
            {
                parent::__construct($config, $db, $redis);

                require_once __DIR__ . '/../../../utils/clickhouse.php';

                $this->clickhouse = new \clickhouse(
                    $config['backends']['plog']['host'],
                    $config['backends']['plog']['port'],
                    $config['backends']['plog']['username'],
                    $config['backends']['plog']['password'],
                    $config['backends']['plog']['database']
                );

                $this->time_shift = $config['backends']['plog']['time_shift'];
                $this->max_call_length = $config['backends']['plog']['max_call_length'];
                $this->ttl_temp_record = $config['backends']['plog']['ttl_temp_record'];
                $this->ttl_camshot_days = $config['backends']['plog']['ttl_camshot_days'];
                $this->back_time_shift_video_shot = $config['backends']['plog']['back_time_shift_video_shot'];
            }

            /**
             * @inheritDoc
             */
            public function cron($part)
            {
                echo("__cron\n");
                $this->processEvents();

                if ($part == '5min') {
                    $this->db->modify("delete from plog_door_open where expire < " . time());
                    $this->db->modify("delete from plog_call_done where expire < " . time());
                } else {
                    return true;
                }
            }

            //получение кадра события на указанную дату+время и ip устройства или от FRS
            /**
             * @inheritDoc
             */
            public function getCamshot($domophone_id, $date, $event_id = false)
            {
                $mongo = loadBackend('files');
                $camshot_data = [];

                if ($event_id === false) {
                    // TODO: получение кадра события от FRS по дате

                    //получение кадра с DVR-серевера, если нет кадра от FRS
                    $households = loadBackend("households");
                    $entrances = $households->getEntrances("domophoneId", [ "domophoneId" => $domophone_id, "output" => "0" ]);
                    if ($entrances && $entrances[0]) {
                        $cameras = $households->getCameras("id", $entrances[0]["cameraId"]);
                        if ($cameras && $cameras[0]) {
                            $prefix = $cameras[0]["dvrStream"];
                            if ($prefix) {
                                $ts_event = $date - $this->back_time_shift_video_shot;
                                $filename = "/tmp/" . uniqid('camshot_') . ".jpg";
                                if (getDVRServer($prefix)['type'] == 'nimble') {
                                    system("ffmpeg -y -i $prefix/dvr_thumbnail_$ts_event.mp4 -vframes 1 $filename 1>/dev/null 2>/dev/null");
                                } else {
                                    system("ffmpeg -y -i $prefix/$ts_event-preview.mp4 -vframes 1 $filename 1>/dev/null 2>/dev/null");
                                }
                                $camshot_data[self::COLUMN_IMAGE_UUID] = $this->BSONToGUIDv4($mongo->addFile(
                                    "camshot",
                                    file_get_contents($filename),
                                    [
                                        ["contentType" => "image/jpeg"],
                                        ["expire" => time() + $this->ttl_camshot_days * 86400],
                                    ]
                                ));
                                unlink($filename);
                                $camshot_data[self::COLUMN_PREVIEW] = 1;
                            }
                        }
                    }
                } else {
                    // TODO: получение кадра события от FRS по event_id
                }

                return $camshot_data;
            }

            //получение кадра события из коллекции файлов
            /**
             * @inheritDoc
             */
            public function getEventImage($image_uuid)
            {
                $mongo = loadBackend('files');
                try {
                    $id = substr(str_replace('-', '', $image_uuid), 8);
                    return $mongo->getFile($id);
                } catch (\Exception $e) {

                }

                return [];
            }

            /**
             * @inheritDoc
             */
            public function writeEventData($event_data, $flat_list = [])
            {
                echo("__call writeEventData\n");
                if (count($flat_list)) {
                    foreach ($flat_list as $flat_id) {
                        $event_data[self::COLUMN_FLAT_ID] = $flat_id;
                        $this->clickhouse->insert("plog", [$event_data]);
                    }
                } else {
                    $this->clickhouse->insert("plog", [$event_data]);
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
                $query = "select ip from houses_domophones where house_domophone_id = $domophone_id";
                $result = $this->db->query($query, \PDO::FETCH_ASSOC)->fetchAll();
                if (count($result)) {
                    $ip =  $result[0]['ip'];
                } else {
                    return false;
                }

                return $this->addDoorOpenData($date, $ip, $event_type, $door, $detail);
            }

            /**
             * @inheritDoc
             */
            public function getEventsDays(int $flat_id, $filter_events)
            {
                if ($filter_events) {
                    $query = <<< __SQL__
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
                    __SQL__;
                } else {
                    $query = <<< __SQL__
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
                    __SQL__;
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
                $query = <<< __SQL__
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
                __SQL__;

                return $this->clickhouse->select($query);
            }

            private function BSONToGUIDv4($bson)
            {
                $hex = '00000000' . $bson;
                return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split($hex, 4));
            }

            public function getDomophoneId($ip)
            {
                $query = <<< __SQL__
                    select
                        hd.house_domophone_id
                    from
                        houses_domophones hd
                    where
                        hd.ip = '$ip'
                __SQL__;

                $result = $this->db->query($query, \PDO::FETCH_ASSOC)->fetchAll();
                if (count($result)) {
                    return $result[0]['house_domophone_id'];
                }

                return false;
            }

            private function getDomophoneDescription($domophone_id, $domophone_output)
            {
                $query = <<< __SQL__
                    select
                        he.entrance description
                    from
                        houses_entrances he
                    where
                        he.house_domophone_id = $domophone_id and he.domophone_output = $domophone_output
                __SQL__;

                $result = $this->db->query($query, \PDO::FETCH_ASSOC)->fetchAll();
                if (count($result)) {
                    return $result[0]['description'];
                }

                return false;
            }

            //получение списка flat_id по RFID ключу на домофоне
            private function getFlatIdByRfid($rfid, $domophone_id)
            {
                $query = <<< __SQL__
                    select
                        r.access_to flat_id
                    from
                        houses_rfids r
                        inner join houses_entrances_flats hef
                            on hef.house_flat_id = r.access_to
                        inner join houses_entrances he
                            on he.house_entrance_id = hef.house_entrance_id
                        inner join houses_domophones hd
                            on hd.house_domophone_id = he.house_domophone_id
                            and hd.house_domophone_id = $domophone_id
                    where
                        r.rfid = '$rfid'
                        and r.access_type = 2
                __SQL__;

                $result = $this->db->query($query, \PDO::FETCH_ASSOC)->fetchAll();
                if (count($result)) {
                    return array_map(function ($row) {
                        return $row['flat_id'];
                    }, $result);
                }

                return false;
            }

            //получение списка flat_id по коду открытия на устройстве
            private function getFlatIdByCode($code, $domophone_id)
            {
                $query = <<< __SQL__
                    select
                        hf.house_flat_id flat_id
                    from
                        houses_domophones hd
                        inner join houses_entrances he
                            on hd.house_domophone_id = he.house_domophone_id
                        inner join houses_entrances_flats hef
                            on he.house_entrance_id = hef.house_entrance_id
                        inner join houses_flats hf
                            on hef.house_flat_id = hf.house_flat_id
                            and hf.open_code = '$code'
                        where
                            hd.house_domophone_id = $domophone_id
                __SQL__;

                $result = $this->db->query($query, \PDO::FETCH_ASSOC)->fetchAll();
                if (count($result)) {
                    return array_map(function ($row) {
                        return $row['flat_id'];
                    }, $result);
                }

                return false;
            }

            //получение списка flat_id по телефону пользователя на устройстве
            private function getFlatIdByUserPhone($user_phone, $domophone_id)
            {
                $query = <<< __SQL__
                    select
                        hfs.house_flat_id flat_id
                    from
                        houses_subscribers_mobile hsm
                        inner join houses_flats_subscribers hfs
                            on hsm.house_subscriber_id = hfs.house_subscriber_id
                        inner join houses_entrances_flats hef
                            on hfs.house_flat_id = hef.house_flat_id
                        inner join houses_entrances he
                            on hef.house_entrance_id = he.house_entrance_id
                            and he.house_domophone_id = $domophone_id
                        where
                            hsm.id = '$user_phone'
                __SQL__;

                $result = $this->db->query($query, \PDO::FETCH_ASSOC)->fetchAll();
                if (count($result)) {
                    return array_map(function ($row) {
                        return $row['flat_id'];
                    }, $result);
                }

                return false;
            }

            //получение flat_id по номеру квартиры на устройстве
            private function getFlatIdByNumber($flat_number, $domophone_id)
            {
                $query = <<< __SQL__
                    select
                    hef.house_flat_id flat_id
                    from
                        houses_entrances he
                        inner join houses_entrances_flats hef
                            on he.house_entrance_id = hef.house_entrance_id
                    where
                        he.house_domophone_id = $domophone_id
                        and hef.apartment = $flat_number
                __SQL__;

                $result = $this->db->query($query, \PDO::FETCH_ASSOC)->fetchAll();
                if (count($result)) {
                    return $result[0]['flat_id'];
                }

                return false;
            }

            //получение flat_id по префиксу калитки и номеру квартиры
            private function getFlatIdByPrefixAndNumber($prefix, $flat_number, $domophone_id)
            {
                $query = <<< __SQL__
                    select
                        hef.house_flat_id flat_id
                    from
                        houses_entrances he
                        inner join houses_houses_entrances hhe
                            on he.house_entrance_id = hhe.house_entrance_id
                            and hhe.prefix = $prefix
                        inner join houses_entrances_flats hef
                            on he.house_entrance_id = hef.house_entrance_id
                            and hef.apartment = $flat_number
                        inner join houses_flats hf
                            on hef.house_flat_id = hf.house_flat_id
                            and hf.address_house_id = hhe.address_house_id
                    where
                        he.house_domophone_id = $domophone_id
                        and he.domophone_output = 0
                __SQL__;

                $result = $this->db->query($query, \PDO::FETCH_ASSOC)->fetchAll();
                if (count($result)) {
                    return $result[0]['flat_id'];
                }

                return false;
            }

            private function getEntranceCount($flat_id)
            {
                $query = <<< __SQL__
                    select
                        count(*) entrance_count
                    from
                        houses_entrances_flats hef
                        inner join houses_entrances he
                            on hef.house_entrance_id = he.house_entrance_id
                            and he.domophone_output = 0
                    where
                        hef.house_flat_id = $flat_id
                __SQL__;
                $result = $this->db->query($query, \PDO::FETCH_ASSOC)->fetchAll();

                if ($result && count($result)) {
                    return $result[0]['entrance_count'];
                }

                return 0;
            }

            private function updateRfidLastSeen($flat_list, $rfid, $date)
            {
                $fl = implode(",", $flat_list);
                $query = <<< __SQL__
                    update
                        houses_rfids
                    set
                        last_seen = $date
                    where
                        access_type = 2
                        and access_to in ($fl)
                        and rfid = '$rfid'
                __SQL__;

                $this->db->query($query);
            }

            private function updateFlatLastOpened($flat_list, $date)
            {
                $fl = implode(",", $flat_list);
                $query = <<< __SQL__
                    update
                        houses_flats
                    set
                        last_opened = $date
                    where
                        house_flat_id in ($fl)
                __SQL__;

                $this->db->query($query);
            }

            private function processEvents()
            {
                $end_date = time() - $this->time_shift;  //крайняя дата обработки

                //обработка данных из таблицы plog_door_open
                $query = <<< __SQL__
                    select
                        *
                    from
                        plog_door_open
                    where
                        date <= $end_date
                    order by
                        date
                __SQL__;
                $result = $this->db->query($query, \PDO::FETCH_ASSOC)->fetchAll();
                foreach ($result as $row) {
                    $event_data = [];
                    $event_id = false;
                    $flat_list = [];

                    $plog_date = $row['date'];
                    $domophone_id = $this->getDomophoneId($row["ip"]);  // TODO: сделать получение domophone_id из бэкенда
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
                        $this->updateRfidLastSeen($flat_list, $rfid_key, $plog_date);
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
                        $event_id = $row['detail'];
                        // TODO: доделать обработку
                    }

                    //получение кадра события
                    $image_data = $this->getCamshot($domophone_id, $plog_date, $event_id);
                    if ($image_data) {
                        $event_data[self::COLUMN_IMAGE_UUID] = $image_data[self::COLUMN_IMAGE_UUID];
                        $event_data[self::COLUMN_PREVIEW] = $image_data[self::COLUMN_PREVIEW];
                        // TODO: доделать для случая наличия инфы о лице
                    }
                    $this->writeEventData($event_data, $flat_list);
                }

                //удаление данных из таблицы plog_door_open
                $query = <<< __SQL__
                    delete
                    from
                        plog_door_open
                    where
                        date <= $end_date
                __SQL__;
                $this->db->query($query);

                //обработка данных из таблицы plog_call_done
                $query = <<< __SQL__
                    select
                        *
                    from
                        plog_call_done
                    where
                        date <= $end_date
                    order by
                        date
                __SQL__;
                $result = $this->db->query($query, \PDO::FETCH_ASSOC)->fetchAll();
                foreach ($result as $row) {
                    $ip = $row['ip'];
                    $domophone_id = $this->getDomophoneId($row["ip"]);  // TODO: сделать получение domophone_id из бэкенда

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

                    unset($has_cms);
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
                    $query = <<< __SQL__
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
                    __SQL__;
                    $result = $this->clickhouse->select($query);
                    foreach ($result as $row) {
                        $msg = $row['msg'];
                        $unit = $row['unit'];

                        //обработка звонка
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
                                unset($now_has_cms);
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
                                    $now_has_cms = (strpos($msg, 'CMS') != false);
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

                                    $event_data[self::COLUMN_DATE] = $row['date'];

                                    if ($now_has_cms && !isset($has_cms)) {
                                        $has_cms = true;
                                    }
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
                        $event_data[self::COLUMN_IMAGE_UUID] = $image_data[self::COLUMN_IMAGE_UUID];
                        $event_data[self::COLUMN_PREVIEW] = $image_data[self::COLUMN_PREVIEW];
                        // TODO: доделать для случая наличия инфы о лице
                    }

                    //сохраняем событие
                    $this->writeEventData($event_data);
                }

                //удаление данных из таблицы plog_call_done
                $query = <<< __SQL__
                    delete
                    from
                        plog_call_done
                    where
                        date <= $end_date
                __SQL__;
                $this->db->query($query);
            }
        }
    }
