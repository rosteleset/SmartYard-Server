<?php

    /**
     * backends plog namespace
     */

    namespace backends\plog
    {

        use backends\files\files;

        /**
         * clickhouse archive class
         */
        class clickhouse extends plog
        {
            private $clickhouse;
            private $time_shift;  // сдвиг по времени в секундах от текущего

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

                $this->time_shift = $config['backends']['plog']['timeShift'];
            }

            /**
             * @inheritDoc
             */
            public function cron($part)
            {
                echo("__cron\n");
                if ($part == '5min') {
                    $this->processEvents();
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
            public function getCamshot($ip, $date, $event_id = false)
            {
                $mongo = loadBackend('files');
                $camshot_data = [];

                if ($event_id === false) {
                    //для теста
                    //$image_url = "http://192.168.13.173/cgi-bin/images_cgi?channel=0&user=admin&pwd=shoo8mo1";

                    //получение кадра из флюсоника
                    $households = loadBackend("households");

                    $domophone_id = 1;  // TODO: сделать получение domophone_id из бэкенда

                    $entrances = $households->getEntrances("domophoneId", [ "domophoneId" => $domophone_id, "output" => "0" ]);
                    if ($entrances && $entrances[0]) {
                        $cameras = $households->getCameras("id", $entrances[0]["cameraId"]);
                        if ($cameras && $cameras[0]) {
                            $prefix = $cameras[0]["flussonic"];
                            if ($prefix) {
                                $ts_event = strtotime($date) - 3;  // вычитаем три секунды для получения кадра с флюсоника
                                $filename = "/tmp/" . uniqid('camshot_') . ".jpg";
                                system("ffmpeg -y -i $prefix/index-$ts_event-10.m3u8 -vframes 1 $filename 1>/dev/null 2>/dev/null");
                                $camshot_data[self::COLUMN_IMAGE_UUID] = $this->BSONToGUIDv4($mongo->addFile("filename", file_get_contents($filename)));
                                system("rm $filename");
                                $camshot_data[self::COLUMN_PREVIEW] = 1;
                            }
                        }
                    }
                } else {
                    // TODO: получение кадра события от FRS
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
                    return $mongo->getFile($id)['contents'];
                } catch (\Exception $e) {

                }

                return [];
            }

            /**
             * @inheritDoc
             */
            public function writeEventData($event_data)
            {
                echo("__call writeEventData\n");
                $this->clickhouse->insert("plog", [$event_data]);
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

            //получение flat_id по RFID ключу на устройстве
            private function getFlatIdByRfid($rfid, $ip)
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
                            and hd.url like '%/$ip/%' or hd.url like '%/$ip'
                    where
                        r.rfid = '$rfid'
                        and r.access_type = 2
                __SQL__;

                // TODO: учесть случаи с несколькими квартирами
                $result = $this->db->query($query, \PDO::FETCH_ASSOC)->fetchAll();
                if (count($result)) {
                    return $result[0]['flat_id'];
                }

                return false;
            }

            //получение flat_id по коду открытия на устройстве
            private function getFlatIdByCode($code, $ip)
            {
                $query = <<< __SQL__
                    select
                        hf.house_flat_id
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
                            hd.url like '%/$ip/%' or hd.url like '%/$ip'
                __SQL__;

                // TODO: учесть случаи с несколькими квартирами
                $result = $this->db->query($query, \PDO::FETCH_ASSOC)->fetchAll();
                if (count($result)) {
                    return $result[0]['house_flat_id'];
                }

                return false;
            }

            private function updateRfidLastSeen($flat_id, $rfid, $date)
            {
                $query = <<< __SQL__
                    update
                        houses_rfids
                    set
                        last_seen = '$date'
                    where
                        access_type = 2
                        and access_to = $flat_id
                        and rfid = '$rfid'
                __SQL__;

                $this->db->query($query);
            }

            private function updateFlatLastOpened($flat_id, $date)
            {
                $query = <<< __SQL__
                    update
                        houses_flats
                    set
                        last_opened = '$date'
                    where
                        house_flat_id = $flat_id
                __SQL__;

                $this->db->query($query);
            }

            private function processEvents()
            {
                $end_date = date('Y-m-d H:i:s', time() - $this->time_shift);  //крайняя дата обработки

                //обработка таблицы plog_door_open
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

                    $plog_date = $row['date'];
                    $ip = $row["ip"];
                    $event_type = (int)$row['event'];

                    $event_data[self::COLUMN_DATE] = $plog_date;
                    $event_data[self::COLUMN_EVENT] = $event_type;
                    $event_data[self::COLUMN_DOMOPHONE_OUTPUT] = $row['door'];
                    $event_data[self::COLUMN_EVENT_UUID] = GUIDv4();

                    if ($event_type == self::EVENT_OPENED_BY_KEY) {
                        $event_data[self::COLUMN_OPENED] = 1;
                        $rfid_key = $row['detail'];
                        $event_data[self::COLUMN_RFID] = $rfid_key;
                        $flat_id = $this->getFlatIdByRfid($rfid_key, $ip);
                        if (!$flat_id) {
                            continue;
                        }
                        $event_data[plog::COLUMN_FLAT_ID] = $flat_id;
                        $this->updateRfidLastSeen($flat_id, $rfid_key, $event_data[self::COLUMN_DATE]);
                    }

                    if ($event_type == self::EVENT_OPENED_BY_CODE) {
                        $event_data[self::COLUMN_OPENED] = 1;
                        $open_code = $row['detail'];
                        $event_data[self::COLUMN_CODE] = $open_code;
                        $flat_id = $this->getFlatIdByCode($open_code, $ip);
                        if (!$flat_id) {
                            continue;
                        }
                    }

                    if ($event_type == self::EVENT_OPENED_BY_APP) {
                        $event_data[self::COLUMN_OPENED] = 1;
                        $user_phone = $row['detail'];
                        $event_data[self::COLUMN_USER_PHONE] = $user_phone;
                    }

                    if ($event_type == self::EVENT_OPENED_BY_FACE) {
                        $event_data[self::COLUMN_OPENED] = 1;
                        $event_id = $row['detail'];
                    }

                    //получение кадра события
                    $image_data = $this->getCamshot($ip, $plog_date, $event_id);
                    if ($image_data) {
                        $event_data[self::COLUMN_IMAGE_UUID] = $image_data[self::COLUMN_IMAGE_UUID];
                        $event_data[self::COLUMN_PREVIEW] = $image_data[self::COLUMN_PREVIEW];
                    }
                    $this->writeEventData($event_data);
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
            }
        }
    }
