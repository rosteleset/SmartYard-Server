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
        protected $image_url_prefix;

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
            $this->image_url_prefix = $config["backends"]["events"]["image_url_prefix"];
        }

        public function getEventsDays(int $flat_id, $filter_events)
        {
            if ($filter_events) {
                $query = "select toYYYYMMDD(date) as day, count(date) as events from plog where not hidden and flat_id = $flat_id and event in ($filter_events) group by toYYYYMMDD(date) order by toYYYYMMDD(date) desc";
            } else {
                $query = "select toYYYYMMDD(date) as day, count(date) as events from plog where not hidden and flat_id = $flat_id group by toYYYYMMDD(date) order by toYYYYMMDD(date) desc";
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
            $query = "select date, event_uuid, hidden, image_uuid, flat_id, domophone_id, domophone_output, domophone_output_description, event, opened, toJSONString(face) face, rfid, code, user_phone, gate_phone, preview from plog where not hidden and toYYYYMMDD(date) = '$date' and flat_id = $flat_id order by date desc";
            $resp = mysqli_fetch_all($this->con->query($query), MYSQLI_ASSOC);
            $events_details = [];
            foreach ($resp as &$row) {
                $e_details = [];
                $e_details['date'] = $row['date'];
                $e_details['uuid'] = $row['event_uuid'];
                $e_details['image'] = $row['image_uuid'];
                $e_details['previewType'] = $row['preview'];
                $e_details['objectId'] = $row['domophone_id'];
                $e_details['objectType'] = 0;
                $e_details['objectMechanizma'] = $row['domophone_output'];
                $e_details['mechanizmaDescription'] = $row['domophone_output_description'];
                $e_details['event'] = $row['event'];
                $face = json_decode($row['face']);
                if ($face->width && $face->height) {
                    $e_details['detailX']['face'] = [
                        'left' => $face->left,
                        'top' => $face->top,
                        'width' => $face->width,
                        'height' => $face->height
                    ];
                }
                if ($face->faceId) {
                    $e_details['detailX']['faceId'] = $face->faceId;
                }

                switch ((int)$row['event']) {
                    case 1:
                    case 2:
                        $e_details['detailX']['opened'] = ($row['opened'] == 1) ? 't' : 'f';
                        break;

                    case 3:
                        $e_details['detailX']['key'] = $row['rfid'];
                        break;

                    case 4:
                        $e_details['detailX']['phone'] = $row['user_phone'];
                        break;

                    case 5:
                        break;

                    case 6:
                        $e_details['detailX']['code'] = $row['code'];
                        break;

                    case 7:
                        $e_details['detailX']['phoneFrom'] = $row['user_phone'];
                        $e_details['detailX']['phoneTo'] = $row['gate_phone'];
                        break;
                }
                if ((int)$row['preview']) {
                    $date = explode('-', explode(' ', $row['date'])[0]);
                    $url = "{$this->image_url_prefix}{$date[0]}-{$date[1]}-{$date[2]}/{$row['image_uuid'][0]}/{$row['image_uuid'][1]}/{$row['image_uuid'][2]}/{$row['image_uuid'][3]}/{$row['image_uuid']}.jpg";
                    $e_details['preview'] = $url;
                }

                $events_details[] = $e_details;
            }
            response(200, $events_details);
        }
    }
}
