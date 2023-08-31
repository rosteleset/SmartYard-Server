<?php


/**
 * backends plog namespace
 */

namespace backends\plog {

    use backends\frs\frs;
    use Exception;
    use Selpol\Service\ClickhouseService;
    use Selpol\Task\Tasks\Plog\PlogCallTask;
    use Selpol\Task\Tasks\Plog\PlogOpenTask;

    /**
     * clickhouseService archive class
     */
    class clickhouse extends plog
    {
        private ClickhouseService $clickhouse;

        private int $max_call_length;  // максимальная длительность звонка в секундах
        private int $ttl_camshot_days;  // время жизни кадра события
        private int $back_time_shift_video_shot;  // сдвиг назад в секундах от времени события для получения кадра от медиа сервера

        public function __construct($config, $db, $redis, $login = false)
        {
            parent::__construct($config, $db, $redis, $login);

            $this->clickhouse = new ClickhouseService(
                $config['backends']['plog']['host'],
                $config['backends']['plog']['port'],
                $config['backends']['plog']['username'],
                $config['backends']['plog']['password'],
                $config['backends']['plog']['database']
            );

            $this->max_call_length = $config['backends']['plog']['max_call_length'];
            $this->ttl_camshot_days = $config['backends']['plog']['ttl_camshot_days'];
            $this->back_time_shift_video_shot = $config['backends']['plog']['back_time_shift_video_shot'];
        }

        /**
         * Получение кадра события на указанную дату+время и ip устройства или от FRS
         * @inheritDoc
         */
        public function getCamshot($domophone_id, $date, $event_id = false)
        {
            $files = backend('files');

            $camshot_data = [];

            $households = backend("households");

            $entrances = $households->getEntrances("domophoneId", ["domophoneId" => $domophone_id, "output" => "0"]);

            if ($entrances && $entrances[0]) {
                $cameras = $households->getCameras("id", $entrances[0]["cameraId"]);
                if ($cameras && $cameras[0]) {
                    $frs = backend("frs");
                    if ($frs) {
                        if ($event_id === false) {
                            $response = $frs->bestQualityByDate($cameras[0], $date);
                        } else {
                            $response = $frs->bestQualityByEventId($cameras[0], $event_id);
                        }

                        if ($response && $response[frs::P_CODE] == frs::R_CODE_OK && $response[frs::P_DATA]) {
                            $image_data = file_get_contents($response[frs::P_DATA][frs::P_SCREENSHOT]);
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

                    logger('plog')->debug('frs camshot', ['data' => $camshot_data]);

                    if (!$camshot_data) {
                        //получение кадра с DVR-серевера, если нет кадра от FRS
                        $prefix = $cameras[0]["dvrStream"];
                        if ($prefix) {
                            $ts_event = $date - $this->back_time_shift_video_shot;
                            $filename = "/tmp/" . uniqid('camshot_') . ".jpeg";
                            $urlOfScreenshot = backend("dvr")->getUrlOfScreenshot($cameras[0], $ts_event, true);

                            if (str_contains($urlOfScreenshot, '.mp4')) {
                                shell_exec("ffmpeg -y -i " . $urlOfScreenshot . " -vframes 1 $filename 1>/dev/null 2>/dev/null");
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
        }

        /**
         * @inheritDoc
         */
        public function addCallDoneData($date, $ip, $call_id = null)
        {
            try {
                task(new PlogCallTask($this->getDomophoneId($ip), $ip, $date, $call_id))->medium()->delay(15)->dispatch();
            } catch (Exception $e) {
                logger('task')->error('Error addCallDoneData' . PHP_EOL . $e);
            }
        }

        /**
         * @inheritDoc
         */
        public function addDoorOpenData($date, $ip, $event_type, $door, $detail)
        {
            try {
                task(new PlogOpenTask($this->getDomophoneId($ip), $event_type, $door, $date, $detail))->medium()->delay(15)->dispatch();
            } catch (Exception $e) {
                logger('task')->error('Error addDoorOpenData' . PHP_EOL . $e);
            }
        }

        /**
         * @inheritDoc
         */
        public function addDoorOpenDataById($date, $domophone_id, $event_type, $door, $detail)
        {
            try {
                task(new PlogOpenTask($domophone_id, $event_type, $door, $date, $detail))->medium()->delay(15)->dispatch();
            } catch (Exception $e) {
                logger('task')->error('Error addDoorOpenDataById' . PHP_EOL . $e);
            }
        }

        public function getSyslog(string $ip, int $date): false|array
        {
            $start_date = $date - $this->max_call_length;
            $query = "select date, msg, unit from syslog s where IPv4NumToString(s.ip) = '$ip' and s.date > $start_date and s.date <= $date order by date desc";

            return $this->clickhouse->select($query);
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
        public function getEventsByFlatsAndDomophone(array $flats_id, int $domophone_id, int $date)
        {
            $filterFlatsId = implode(',', $flats_id);
            $filterDate = date('Ymd', time() - $date * 24 * 60 * 60);

            $query = "
                    select
                        date
                    from
                        plog
                    where
                        not hidden
                        and toYYYYMMDD(FROM_UNIXTIME(date)) >= '$filterDate'
                        and flat_id in ($filterFlatsId)
                        and tupleElement(domophone, 'domophone_id') = $domophone_id
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
            $households = backend('households');
            $result = $households->getDomophones('ip', $ip);

            if ($result && $result[0]) {
                return $result[0]['domophoneId'];
            }

            return false;
        }

        private function getPlogHidden($flat_id)
        {
            $households = backend('households');
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
    }
}