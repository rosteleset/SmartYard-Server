<?php

/**
 * backends events namespace
 */

namespace backends\events
{
    use DateTimeInterface;

    class internal extends events
    {
        protected $con;

        public function __construct($config, $db, $redis)
        {
            parent::__construct($config, $db, $redis);

            $this->con = new \mysqli(
                $config["clickhouse"]["host"],
                $config["clickhouse"]["username"],
                $config["clickhouse"]["password"],
                "default",
                $config["clickhouse"]["port"]
            );
        }

        public function getEventsDays(int $flat_id, $filter_events)
        {
            if ($filter_events) {
                $query = "select toYYYYMMDD(date) as day, count(date) as events from plog where not hidden and flat_id = $flat_id and object_type = 0 and event in ($filter_events) group by toYYYYMMDD(date) order by toYYYYMMDD(date) desc";
            } else {
                $query = "select toYYYYMMDD(date) as day, count(date) as events from plog where not hidden and flat_id = $flat_id and object_type = 0 group by toYYYYMMDD(date) order by toYYYYMMDD(date) desc";
            }

            $resp = mysqli_fetch_all($this->con->query($query), MYSQLI_ASSOC);
            if (count($resp)) {
                foreach ($resp as &$d) {
                    $d['day'] = substr($d['day'], 0, 4) . '-' . substr($d['day'], 4, 2) . '-' . substr($d['day'], 6, 2);
                }
                return $resp;
            }

            return false;
        }

        public function getDetailEventsByDay(int $flat_id, string $date)
        {
            $query = "select * from plog where not hidden and toYYYYMMDD(date) = '$date' and flat_id = $flat_id and object_type = 0 order by date desc";
            $resp = mysqli_fetch_all($this->con->query($query), MYSQLI_ASSOC);
            $events_details = [];
            foreach ($resp as &$row) {
                $e_details = [];
                $e_details['date'] = $row['date'];
                $e_details['uuid'] = $row['uuid'];
                $e_details['previewType'] = $row['preview'];
                $e_details['preview'] = $row['image'];
                $e_details['objectId'] = $row['object_id'];
                $e_details['objectType'] = $row['object_type'];
                $e_details['objectMechanizma'] = $row['object_mechanizma'];
                $e_details['mechanizmaDescription'] = $row['mechanizma_description'];
                if ($row['face_width'] > 0 && $row['face_height']) {
                    $e_details['detailX']['face'] = [
                        'left' => $row['face_left'],
                        'top' => $row['face_top'],
                        'width' => $row['face_width'],
                        'height' => $row['face_height']
                    ];
                }
                $e_details['event'] = $row['event'];
                switch ((int)$row['event']) {
                    case 1:
                    case 2:
                        $e_details['detailX']['opened'] = ($row['opened'] == 1) ? 't' : 'f';
                        break;

                    case 3:
                        $e_details['detailX']['key'] = $row['rfid_key'];
                        break;

                    case 4:
                        $e_details['detailX']['phone'] = $row['phone'];
                        break;

                    case 5:
                        $e_details['detailX']['faceId'] = $row['face_id'];
                        break;

                    case 6:
                        $e_details['detailX']['code'] = $row['code'];
                        break;

                    case 7:
                        $e_details['detailX']['phoneFrom'] = $row['phone_from'];
                        $e_details['detailX']['phoneTo'] = $row['phone_to'];
                        break;
                }

                $events_details[] = $e_details;
            }
            response(200, $events_details);
        }
    }
}
