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
            private $time_shift;  // сдвиг по времени в секундах от текущего
            private $max_call_length;  // максимальная длительность звонка в секундах
            private $ttl_temp_record;  // значение, которое прибавляется к текущему времени для получения expire
            private $ttl_camshot_days;  // время жизни кадра события

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
                    //для теста
                    //$image_url = "http://192.168.13.173/cgi-bin/images_cgi?channel=0&user=admin&pwd=shoo8mo1";

                    // TODO: получение кадра события от FRS по дате

                    //получение кадра с DVR-серевера, если нет кадра от FRS
                    $households = loadBackend("households");
                    $entrances = $households->getEntrances("domophoneId", [ "domophoneId" => $domophone_id, "output" => "0" ]);
                    if ($entrances && $entrances[0]) {
                        $cameras = $households->getCameras("id", $entrances[0]["cameraId"]);
                        if ($cameras && $cameras[0]) {
                            $prefix = $cameras[0]["dvrStream"];
                            if ($prefix) {
                                $ts_event = strtotime($date) - 3;  // вычитаем три секунды для получения кадра
                                $filename = "/tmp/" . uniqid('camshot_') . ".jpg";
                                system("ffmpeg -y -i $prefix/index-$ts_event-10.m3u8 -vframes 1 $filename 1>/dev/null 2>/dev/null");
                                $camshot_data[self::COLUMN_IMAGE_UUID] = $this->BSONToGUIDv4($mongo->addFile(
                                    "camshot",
                                    file_get_contents($filename),
                                    [
                                        ["contentType" => "image/jpeg"],
                                        ["expire" => new UTCDateTime((time() + $this->ttl_camshot_days * 86400) * 1000)],
                                    ]
                                ));
                                system("rm $filename");
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
            public function addCallDoneData($date, $ip, $call_id)
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
                            toYYYYMMDD(date) as day,
                            count(date) as events
                        from
                            plog
                        where
                            not hidden
                            and flat_id = $flat_id
                            and event in ($filter_events)
                        group by
                            toYYYYMMDD(date)
                        order by
                            toYYYYMMDD(date) desc
                    __SQL__;
                } else {
                    $query = <<< __SQL__
                        select
                            toYYYYMMDD(date) as day,
                            count(date) as events
                        from
                            plog
                        where
                            not hidden
                            and flat_id = $flat_id
                        group by
                            toYYYYMMDD(date)
                        order by
                            toYYYYMMDD(date) desc
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
                        domophone_id,
                        domophone_output,
                        domophone_output_description,
                        event,
                        opened,
                        toJSONString(face) face,
                        rfid,
                        code,
                        user_phone,
                        gate_phone,
                        preview
                    from
                        plog
                    where
                        not hidden
                        and toYYYYMMDD(date) = '$date'
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

            private function updateRfidLastSeen($flat_list, $rfid, $date)
            {
                $fl = implode(",", $flat_list);
                $query = <<< __SQL__
                    update
                        houses_rfids
                    set
                        last_seen = '$date'
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
                        last_opened = '$date'
                    where
                        house_flat_id in ($fl)
                __SQL__;

                $this->db->query($query);
            }

            private function processEvents()
            {
                $end_date = date('Y-m-d H:i:s', time() - $this->time_shift);  //крайняя дата обработки

                //обработка данных из таблицы plog_door_open
                $query = <<< __SQL__
                    select
                        *
                    from
                        plog_door_open
                    where
                        date <= '$end_date'
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
                    $event_data[self::COLUMN_DOMOPHONE_OUTPUT] = $row['door'];
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
                        $event_data[self::COLUMN_USER_PHONE] = $user_phone;
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
                        date <= '$end_date'
                __SQL__;
                $this->db->query($query);

                //обработка данных из таблицы plog_call_done
                $query = <<< __SQL__
                    select
                        *
                    from
                        plog_call_done
                    where
                        date <= '$end_date'
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
                    $event_data[self::COLUMN_EVENT_UUID] = GUIDv4();

                    unset($has_cms);
                    $call_id = (int)$row['call_id'];
                    if ($call_id == 0) {
                        unset($call_id);
                    }
                    unset($flat_id);
                    unset($flat_number);
                    $call_start_found = false;

                    //забираем данные из сислога для звонка
                    $query_end_date = $row['date'];
                    $query_start_date = date('Y-m-d H:i:s', strtotime($query_end_date) - $this->max_call_length);
                    $query = <<< __SQL__
                        select
                            date,
                            msg
                        from
                            syslog s
                        where
                            IPv4NumToString(s.ip) = '$ip'
                            and s.date > '$query_start_date'
                            and s.date <= '$query_end_date'
                        order by
                            date desc
                    __SQL__;
                    $result = $this->clickhouse->select($query);
                    foreach ($result as $row) {
                        $msg = $row['msg'];

                        //обработка звонка
                        $patterns_call = [
                            ["Calling sip: ", true, false, false, false],
                            ["Unable to call CMS apartment ", true, false, false, false],
                            ["CMS handset call started for apartment ", true, false, false, false],
                            ["SIP call | state ", false, false, false, false],
                            ["CMS handset talk started for apartment ", false, true, false, false],
                            ["SIP talk started for apartment ", false, true, false, false],
                            ["SIP call | CONFIRMED", false, true, false, false],
                            ["Opening door by CMS handset for apartment ", false, false, true, false],
                            ["Opening door by DTMF command", false, false, true, false],
                            ["All calls are done", false, false, false, true],
                            ["SIP call | DISCONNECTED", false, false, false, true],
                        ];
                        foreach ($patterns_call as [$pattern, $flag_start, $flag_talk_started, $flag_door_opened, $flag_finish]) {
                            unset($now_has_cms);
                            unset($now_flat_id);
                            unset($now_flat_number);
                            unset($now_call_id);

                            $parts = explode("|", $pattern);
                            $matched = true;
                            foreach ($parts as $p) {
                                $matched = $matched && (strpos($msg, $p) !== false);
                            }

                            if ($matched) {
                                $now_has_cms = (strpos($msg, 'CMS') != false);
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
                                    //парсим flat_id
                                    $p1 = strpos($msg, $pattern);
                                    $p2 = strpos($msg, "@", $p1 + strlen($pattern));
                                    $now_flat_id = intval(substr($msg, $p1 + strlen($pattern) + 1, $p2 -$p1 - strlen($pattern) - 1));
                                }

                                if (strpos($pattern, "SIP call ") !== false) {
                                    //парсим call_id
                                    $p1 = strpos($msg, $parts[0]);
                                    $p2 = strpos($msg, " ", $p1 + strlen($parts[0]));
                                    $now_call_id = intval(substr($msg, $p1 + strlen($pattern), $p2 -$p1 - strlen($pattern)));
                                }

                                $call_start_lost = isset($now_flat_id) && isset($flat_id) && $now_flat_id != $flat_id
                                    || isset($now_flat_number) && isset($flat_number) && $now_flat_number != $flat_number
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
                                if (isset($now_flat_number) && !isset($flat_number)) {
                                    $flat_number = $now_flat_number;
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
                        if ($call_start_found) {
                            break;
                        }
                    }

                    if (isset($flat_id)) {
                        $event_data[self::COLUMN_FLAT_ID] = $flat_id;
                    } else if (isset($flat_number)) {
                        $event_data[self::COLUMN_FLAT_ID] = $this->getFlatIdByNumber($flat_number, $domophone_id);
                    }

                    if (isset($event_data[self::COLUMN_FLAT_ID])) {
                        //получение кадра события
                        $image_data = $this->getCamshot($domophone_id, $event_data[self::COLUMN_DATE]);
                        if ($image_data) {
                            $event_data[self::COLUMN_IMAGE_UUID] = $image_data[self::COLUMN_IMAGE_UUID];
                            $event_data[self::COLUMN_PREVIEW] = $image_data[self::COLUMN_PREVIEW];
                            // TODO: доделать для случая наличия инфы о лице
                        }

                        $this->writeEventData($event_data);
                        $this->updateFlatLastOpened([$event_data[self::COLUMN_FLAT_ID]], $event_data[self::COLUMN_DATE]);
                    }
                }

                //удаление данных из таблицы plog_call_done
                $query = <<< __SQL__
                    delete
                    from
                        plog_call_done
                    where
                        date <= '$end_date'
                __SQL__;
                $this->db->query($query);
            }
        }
    }
