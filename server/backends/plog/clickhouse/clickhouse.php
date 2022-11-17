<?php

    /**
     * backends plog namespace
     */

    namespace backends\plog
    {
        /**
         * clickhouse archive class
         */
        class clickhouse extends plog
        {
            private $clickhouse;

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
                $this->image_url_prefix = $config['backends']['plog']['image_url_prefix'];
            }

            // TODO переделать на монго
            private $image_url_prefix;
            public function getImageUrlPrefix()
            {
                return $this->image_url_prefix;
            }

            /**
             * @inheritDoc
             */
            public function cron($part)
            {
                echo("__cron\n");
                if ($part == '5min') {
                    $this->processSysLog();
                    $this->db->modify("delete from plog_door_open where expire < " . time());
                    $this->db->modify("delete from plog_call_done where expire < " . time());
                } else {
                    return true;
                }
            }

            //получение кадра события на указанную дату+время и ip устройства
            public function getCamshot($ip, $date = false)
            {

            }

            public function writeEventData($event_data)
            {
                echo("call writeEventData\n");
                $this->clickhouse->insert("plog", [$event_data]);
            }

            public function getEventsDays(int $flat_id, $filter_events)
            {
                if ($filter_events) {
                    $query = "select toYYYYMMDD(date) as day, count(date) as events from plog where not hidden and flat_id = $flat_id and event in ($filter_events) group by toYYYYMMDD(date) order by toYYYYMMDD(date) desc";
                } else {
                    $query = "select toYYYYMMDD(date) as day, count(date) as events from plog where not hidden and flat_id = $flat_id group by toYYYYMMDD(date) order by toYYYYMMDD(date) desc";
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

            public function getDetailEventsByDay(int $flat_id, string $date)
            {
                $query = "select date, event_uuid, hidden, image_uuid, flat_id, domophone_id, domophone_output, domophone_output_description, event, opened, toJSONString(face) face, rfid, code, user_phone, gate_phone, preview from plog where not hidden and toYYYYMMDD(date) = '$date' and flat_id = $flat_id order by date desc";
                return $this->clickhouse->select($query);
            }

            //начальная дата обработки syslog
            private function getStartDate(): string
            {
                // TODO переделать получение начальной даты (Redis, по самому позднему событию в plog или как-то ещё)
                $query = 'select max(date) max_date from plog';
                $result = $this->clickhouse->select($query);
                if ($result) {
                    return $result[0]['max_date'];
                }
                return "2022-11-15 17:00:00";
            }

            //получение flat_id по RFID ключу
            private function getFlatIdByRfid($rfid)
            {
                $query = <<< __SQL__
                    select
                        f.house_flat_id
                    from
                        houses_rfids r
                    inner join houses_entrances_flats f
                        on r.access_to = f.house_flat_id
                    where
                        r.rfid = '$rfid'
                __SQL__;

                $result = $this->db->query($query, \PDO::FETCH_ASSOC)->fetchAll();
                if (count($result)) {
                    return $result[0]['house_flat_id'];
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
                        houses_domophones d
                        inner join houses_entrances he
                            on d.house_domophone_id = he.house_domophone_id
                        inner join houses_entrances_flats hef
                            on he.house_entrance_id = hef.house_entrance_id
                        inner join houses_flats hf
                            on hef.house_flat_id = hf.house_flat_id
                            and hf.open_code = '$code'
                        where
                            d.url like '%$ip%';
                __SQL__;

                $result = $this->db->query($query, \PDO::FETCH_ASSOC)->fetchAll();
                if (count($result)) {
                    return $result[0]['house_flat_id'];
                }

                return false;
            }

            private function updateRfidLastSeen($rfid, $date)
            {
                $query = <<< __SQL__
                    update
                        houses_rfids
                    set
                        last_seen = '$date'
                    where
                        rfid = '$rfid'
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

            private function processSysLog()
            {
                $start_date = $this->getStartDate();
                $end_date = date('Y-m-d H:i:s', time() - 5);  // на всякий случай, отнимаем 5 секунд от текущего времени
                $query = "select * from syslog where date > '$start_date' and date <= '$end_date' order by date";
                $result = $this->clickhouse->select($query);
                if (!$result) {
                    echo "no events\n";
                    return;
                }

                foreach ($result as $row) {
                    $log_date = $row["date"];
                    $ip = $row["ip"];
                    $msg = $row["msg"];
                    //echo("$log_date    $ip    $msg\n");

                    //обработка начала событий
                    $pattern_start_events = [
                        //pattern       event       is instant
                        ["Opening door by RFID ",self::EVENT_OPENED_BY_KEY, true],
                        ["Opening door by code ", self::EVENT_OPENED_BY_CODE, true],
                        ["Unable to call CMS apartment ", self::EVENT_UNANSWERED_CALL, false],
                        ["CMS handset call started for apartment ", self::EVENT_UNANSWERED_CALL, false],
                        ["Calling sip:", self::EVENT_UNANSWERED_CALL, false],
                    ];
                    foreach ($pattern_start_events as [$pattern, $event_type, $is_instant]) {
                        if (strpos($msg, $pattern) !== false) {  //есть событие
                            $event_data = [];
                            $event_data[self::COLUMN_DATE] = $log_date;
                            $event_data[self::COLUMN_EVENT_UUID] = GUIDv4();
                            $event_data[self::COLUMN_IMAGE_UUID] = GUIDv4();
                            $event_data[self::COLUMN_EVENT] = $event_type;

                            //обработка мгновенных событий
                            if ($is_instant) {
                                if ($event_type == self::EVENT_OPENED_BY_KEY) {
                                    $event_data[self::COLUMN_OPENED] = 1;

                                    //парсим RFID ключ
                                    $p1 = strpos($msg, $pattern);
                                    $p2 = strpos($msg, ",", $p1 + strlen($pattern));
                                    $rfid_key = substr($msg, strlen($pattern), $p2 -$p1 - strlen($pattern));
                                    $event_data[self::COLUMN_RFID] = $rfid_key;
                                    $flat_id = $this->getFlatIdByRfid($rfid_key);
                                    if (!$flat_id) {
                                        break;
                                    }
                                    $event_data[plog::COLUMN_FLAT_ID] = $flat_id;
                                    $this->updateRfidLastSeen($rfid_key, $log_date);
                                }

                                if ($event_type == self::EVENT_OPENED_BY_CODE) {
                                    $event_data[self::COLUMN_OPENED] = 1;

                                    //парсим код открытия
                                    $p1 = strpos($msg, $pattern);
                                    $p2 = strpos($msg, ",", $p1 + strlen($pattern));
                                    $open_code = substr($msg, strlen($pattern), $p2 -$p1 - strlen($pattern));
                                    $event_data[self::COLUMN_CODE] = $open_code;
                                    $flat_id = $this->getFlatIdByCode($open_code, $ip);
                                    if (!$flat_id) {
                                        break;
                                    }
                                }

                                if ($event_data[self::COLUMN_OPENED] === 1) {
                                    $this->updateFlatLastOpened($event_data[plog::COLUMN_FLAT_ID], $log_date);
                                }
                                $this->writeEventData($event_data);
                            } else {
                                //обработка длительных событий
                            }

                            break;
                        }
                    }
                }
            }
        }
    }
